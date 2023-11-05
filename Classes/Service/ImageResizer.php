<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

declare(strict_types=1);

namespace Causal\ImageAutoresize\Service;

use Causal\ImageAutoresize\Event\ImageResizedEvent;
use Causal\ImageAutoresize\Utility\FAL;
use Causal\ImageAutoresize\Utility\ImageUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * This is a utility class to resize pictures based on rules.
 *
 * @category    Service
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
class ImageResizer
{

    /**
     * @var array
     */
    protected $rulesets = [];

    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var array|null
     */
    protected $lastMetadata = null;

    /**
     * Default constructor
     */
    public function __construct()
    {
        if (version_compare((string)GeneralUtility::makeInstance(Typo3Version::class), '12.0', '<')) {
            $this->signalSlotDispatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        }
    }

    /**
     * Initializes the hook configuration as a meaningful ordered list
     * of rule sets.
     *
     * @param array $configuration
     */
    public function initializeRulesets(array $configuration): void
    {
        $general = $configuration;
        $general['usergroup'] = '';
        unset($general['rulesets']);
        $general = $this->expandValuesInRuleset($general);
        if ($general['conversion_mapping'] === '') {
            $general['conversion_mapping'] = [];
        }

        if (isset($configuration['rulesets'])) {
            $rulesets = $this->compileRuleSets($configuration['rulesets']);
        } else {
            $rulesets = [];
        }

        // Inherit values from general configuration in rule sets if needed
        foreach ($rulesets as $k => &$ruleset) {
            foreach ($general as $key => $value) {
                if (!isset($ruleset[$key])) {
                    $ruleset[$key] = $value;
                } elseif ($ruleset[$key] === '') {
                    $ruleset[$key] = $value;
                }
            }
            if (empty($ruleset['usergroup'])) {
                // Make sure not to try to override general configuration
                // => only keep directories not present in general configuration
                $generalDirectories = $this->associateDirectoryConfigs($general['directories']);
                $rulesetDirectories = $this->associateDirectoryConfigs($ruleset['directories']);
                $ruleset['directories'] = array_values(array_diff_key($rulesetDirectories, $generalDirectories));
                if (empty($ruleset['directories'])) {
                    unset($rulesets[$k]);
                }
            }
        }

        // Use general configuration as very last rule set
        $rulesets[] = $general;
        $this->rulesets = $rulesets;
    }

    /**
     * @param array $directoryConfigs
     * @return array
     */
    protected function associateDirectoryConfigs(array $directoryConfigs): array
    {
        $out = [];
        foreach ($directoryConfigs as $directoryConfig) {
            $key = $directoryConfig['basePath'] . $directoryConfig['pattern'];
            $out[$key] = $directoryConfig;
        }
        return $out;
    }

    /**
     * Returns the resized/converted file name (no actual processing).
     *
     * @param string $fileName
     * @param \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|null $backendUser
     * @param array $ruleset The optional ruleset to use
     * @return string|null Either null if no resize/conversion should take place or the resized/converted file name
     */
    public function getProcessedFileName(
        string $fileName,
        ?\TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser = null,
        ?array $ruleset = null
    ): ?string
    {
        if ($ruleset === null) {
            $ruleset = $this->getRuleset($fileName, $fileName, $backendUser);
        }

        if ($ruleset === null) {
            // File does not match any rule set
            return null;
        }

        if ($backendUser === null && count($ruleset['usergroup']) > 0) {
            // Rule set is targeting some user group but we have no backend user (scheduler task)
            // so we should skip this file altogether
            return null;
        }

        // Extract the extension
        if (($dotPosition = strrpos($fileName, '.')) === false) {
            // File has no extension
            return null;
        }
        $fileExtension = strtolower(substr($fileName, $dotPosition + 1));

        if ($fileExtension === 'png' && !($ruleset['resize_png_with_alpha'] ?? false)) {
            if (file_exists($fileName) && ImageUtility::isTransparentPng($fileName)) {
                return null;
            }
        }

        if ($fileExtension === 'gif' && file_exists($fileName) && ImageUtility::isAnimatedGif($fileName)) {
            return null;
        }

        if (isset($ruleset['conversion_mapping'][$fileExtension])) {
            // File format will be converted
            $destExtension = $ruleset['conversion_mapping'][$fileExtension];
            $destDirectory = PathUtility::dirname($fileName);
            $destFileName = PathUtility::basename(substr($fileName, 0, strlen($fileName) - strlen($fileExtension)) . $destExtension);

            $fileName = $destDirectory . '/' . $destFileName;
        }

        return $fileName;
    }

    /**
     * Processes upload of a file.
     *
     * @param string $fileName Full path to the file to be processed
     * @param string $targetFileName Expected target file name, if not converted (only file name, no path)
     * @param string $targetDirectory
     * @param File $file
     * @param \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser
     * @param callback $callbackNotification Callback to send notification
     * @return string File name that was finally written
     */
    public function processFile(
        string $fileName,
        string $targetFileName = '',
        string $targetDirectory = '',
        ?File $file = null,
        ?\TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser = null,
        $callbackNotification = null
    ): string
    {
        $this->lastMetadata = null;

        if (!(empty($targetFileName) && empty($targetDirectory))) {
            $targetDirectory = rtrim($targetDirectory, '/') . '/';
            $ruleset = $this->getRuleset($fileName, $targetDirectory . $targetFileName, $backendUser);
        } else {
            $ruleset = $this->getRuleset($fileName, $fileName, $backendUser);
        }

        $fileSize = is_file($fileName)
            ? filesize($fileName)
            : -1;    // -1 is a special value so that file size is not taken into account (yet)
        if ($ruleset === null || ($fileSize === -1 || ($fileSize < $ruleset['threshold']))) {
            // File does not match any rule set
            return $fileName;
        }

        // Make file name relative, store as $targetFileName
        // This happens in scheduler task or when uploading to "uploads/"
        if (empty($targetFileName)) {
            $targetFileName = PathUtility::basename($fileName);
        }

        // Extract the extension
        if (($dotPosition = strrpos($fileName, '.')) === false) {
            // File has no extension
            return $fileName;
        }
        $fileExtension = strtolower(substr($fileName, $dotPosition + 1));

        if ($fileExtension === 'png' && !($ruleset['resize_png_with_alpha'] ?? false)) {
            if (ImageUtility::isTransparentPng($fileName)) {
                $message = sprintf(
                    LocalizationUtility::translate('message.imageTransparent', 'image_autoresize'),
                    $targetFileName
                );
                $this->notify($callbackNotification, $message, \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
                return $fileName;
            }
        }
        if ($fileExtension === 'gif' && ImageUtility::isAnimatedGif($fileName)) {
            $message = sprintf(
                LocalizationUtility::translate('message.imageAnimated', 'image_autoresize'),
                $targetFileName
            );
            $this->notify($callbackNotification, $message, \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
            return $fileName;
        }

        $processedFileName = $this->getProcessedFileName($fileName, $backendUser, $ruleset);
        if ($processedFileName === null) {
            // No processing to do
            return $fileName;
        }

        if (!is_writable($fileName)) {
            $message = sprintf(
                LocalizationUtility::translate('message.imageNotWritable', 'image_autoresize'),
                $targetFileName
            );
            $this->notify($callbackNotification, $message, \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
            return $fileName;
        }

        $targetDestFileName = $fileName;
        if (isset($ruleset['conversion_mapping'][$fileExtension])) {
            // File format will be converted
            $destExtension = $ruleset['conversion_mapping'][$fileExtension];
            $destDirectory = PathUtility::dirname($fileName);
            $destFileName = PathUtility::basename(substr($fileName, 0, strlen($fileName) - strlen($fileExtension)) . $destExtension);

            if (empty($targetDirectory)) {
                // Ensures $destFileName does not yet exist, otherwise make it unique!
                /* @var \TYPO3\CMS\Core\Utility\File\BasicFileUtility $fileFunc */
                $fileFunc = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\File\BasicFileUtility::class);
                $destFileName = $fileFunc->getUniqueName($destFileName, $destDirectory);
                $targetDestFileName = $destFileName;
            } else {
                $destFileName = $destDirectory . '/' . $destFileName;
                $targetDestFileName = $targetDirectory . PathUtility::basename(substr($targetFileName, 0, strlen($targetFileName) - strlen($fileExtension)) . $destExtension);
            }
        } else {
            // File format stays the same
            $destExtension = $fileExtension;
            $destFileName = $fileName;
        }

        // Image is bigger than allowed, will now resize it to (hopefully) make it lighter
        /** @var \TYPO3\CMS\Frontend\Imaging\GifBuilder $gifCreator */
        $gifCreator = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Imaging\GifBuilder::class);
        // We want to respect what the user chose with its ruleset and not blindly auto-rotate!
        $gifCreator->scalecmd = trim(str_replace('-auto-orient', '', $gifCreator->scalecmd));

        $imParams = isset($gifCreator->cmds[$destExtension]) ? $gifCreator->cmds[$destExtension] : '';
        $imParams .= (bool)($ruleset['keep_metadata'] ?? false) === true ? ' ###SkipStripProfile###' : '';
        $metadata = ImageUtility::getMetadata($fileName, true);
        $this->lastMetadata = $metadata;
        $isRotated = false;

        if ((bool)$ruleset['auto_orient'] === true) {
            $orientation = ImageUtility::getOrientation($fileName);
            $isRotated = ImageUtility::isRotated($orientation);
            $gifCreator->scalecmd = '-auto-orient ' . $gifCreator->scalecmd;
        }

        if (
            isset($ruleset['max_size'])
            && $ruleset['max_size'] > 0
            && isset($metadata['width'])
            && $metadata['width'] > 0
            && isset($metadata['height'])
            && $metadata['height'] > 0
            && $metadata['width'] * $metadata['height'] > $ruleset['max_size']
        ) {
            $factor = sqrt($ruleset['max_size'] / ($metadata['width'] * $metadata['height']));

            $ruleset['max_width'] = min($ruleset['max_width'], floor($metadata['width'] * $factor));
            $ruleset['max_height'] = min($ruleset['max_height'], floor($metadata['height'] * $factor));
        }

        if ($isRotated) {
            // Invert max_width and max_height as the picture
            // will be automatically rotated
            $options = [
                'maxW' => (int) $ruleset['max_height'],
                'maxH' => (int) $ruleset['max_width'],
            ];
        } else {
            $options = [
                'maxW' => (int) $ruleset['max_width'],
                'maxH' => (int) $ruleset['max_height'],
            ];
        }

        $originalFileSize = filesize($fileName);
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'])) {
            $currentLocale = (string)setlocale(LC_CTYPE, '0');
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'] = $currentLocale;
        }
        $tempFileInfo = $gifCreator->imageMagickConvert($fileName, $destExtension, '', '', $imParams, '', $options, true);
        if ($tempFileInfo === null) {
            $message = LocalizationUtility::translate('message.cannotResize', 'image_autoresize');
            $this->notify($callbackNotification, $message, \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
        } elseif (!$isRotated && filesize($tempFileInfo[3]) >= $originalFileSize - 10240 && $destExtension === $fileExtension) {
            // Conversion leads to same or bigger file (rounded to 10KB to accomodate tiny variations in compression) => skip!
            @unlink($tempFileInfo[3]);
            $tempFileInfo = null;
        }
        if ($tempFileInfo) {
            if (version_compare((string)GeneralUtility::makeInstance(Typo3Version::class), '12.0', '<')) {
                // Signal to post-process the image
                $this->signalSlotDispatcher->dispatch(
                    __CLASS__,
                    'afterImageResize',
                    [
                        'operation' => ($fileName === $destFileName) ? 'RESIZE' : 'RESIZE_CONVERT',
                        'source' => $fileName,
                        'destination' => $tempFileInfo[3],
                        'newWidth' => &$tempFileInfo[0],
                        'newHeight' => &$tempFileInfo[1],
                    ]
                );
            }
            $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
            /** @var ImageResizedEvent $event */
            $event = $eventDispatcher->dispatch(new ImageResizedEvent(
                ($fileName === $destFileName) ? 'RESIZE' : 'RESIZE_CONVERT',
                $fileName,
                $tempFileInfo[3],
                (int)$tempFileInfo[0],
                (int)$tempFileInfo[1]
            ));
            $tempFileInfo[0] = $event->getNewWidth();
            $tempFileInfo[1] = $event->getNewHeight();

            $newFileSize = filesize($tempFileInfo[3]);
            $this->reportAdditionalStorageClaimed($originalFileSize - $newFileSize);

            // Replace original file
            @unlink($fileName);
            @rename($tempFileInfo[3], $destFileName);

            if ($fileName === $destFileName) {
                $message = sprintf(
                    LocalizationUtility::translate('message.imageResized', 'image_autoresize'),
                    $targetFileName, $tempFileInfo[0], $tempFileInfo[1]
                );
            } else {
                $message = sprintf(
                    LocalizationUtility::translate('message.imageResizedAndRenamed', 'image_autoresize'),
                    $targetFileName, $tempFileInfo[0], $tempFileInfo[1], PathUtility::basename($targetDestFileName)
                );
            }

            // Indexation in TYPO3 6.2 is using another signal, after the file
            // has been actually uploaded
            $this->lastMetadata['COMPUTED']['Width'] = $tempFileInfo[0];
            $this->lastMetadata['COMPUTED']['Height'] = $tempFileInfo[1];

            if ($isRotated && (bool)$ruleset['keep_metadata'] === true && $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] === 'GraphicsMagick') {
                ImageUtility::resetOrientation($destFileName);
            }

            // Inform FAL about new image size and dimensions
            try {
                $resourceFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
                $destinationFile = $resourceFactory->retrieveFileOrFolderObject($destFileName);
                if ($destinationFile instanceof File) {
                    $indexer = $this->getIndexer($destinationFile->getStorage());
                    $indexer->updateIndexEntry($destinationFile);
                }
            } catch (FolderDoesNotExistException $e) {
                // We are in upload process. Do nothing
            }

            $this->notify($callbackNotification, $message, \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
        } else {
            // Destination file was not written
            $destFileName = $fileName;
        }
        return $destFileName;
    }

    /**
     * Returns the last extracted metadata.
     *
     * @return array|null
     */
    public function getLastMetadata(): ?array
    {
        return $this->lastMetadata;
    }

    /**
     * Sends a notification.
     *
     * @param callback $callbackNotification Callback to send notification
     * @param string $message
     * @param int $severity
     */
    protected function notify($callbackNotification, string $message, int $severity): void
    {
        $callableName = '';
        if (is_callable($callbackNotification, false, $callableName)) {
            call_user_func($callbackNotification, $message, $severity);
        }
    }

    /**
     * Returns the rule set that applies to a given file for a given backend user (or null
     * if using scheduler task).
     *
     * @param string $sourceFileName
     * @param string $targetFileName
     * @param \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser
     * @return array|null
     */
    protected function getRuleset(string $sourceFileName, string $targetFileName, \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser = null): ?array
    {
        $ret = null;

        // Extract the extension
        $fileExtension = strtolower(substr($targetFileName, strrpos($targetFileName, '.') + 1));

        $beGroups = $backendUser !== null ? array_keys($backendUser->userGroups) : [];

        // Try to find a matching ruleset
        foreach ($this->rulesets as $ruleset) {
            if (!is_array($ruleset['file_types'])) {
                // Default general settings do not include any watched image types
                continue;
            }
            if (count($ruleset['usergroup']) > 0 && (
                    $backendUser === null ||
                    count(array_intersect($ruleset['usergroup'], $beGroups)) === 0)
            ) {
                // Backend user is not member of a group configured for the current rule set
                continue;
            }
            $processFile = false;
            foreach ($ruleset['directories'] as $directoryConfig) {
                $IsInBasePath = PHP_VERSION_ID >= 80000
                    ? str_starts_with($targetFileName, $directoryConfig['basePath'])
                    : GeneralUtility::isFirstPartOfStr($targetFileName, $directoryConfig['basePath']);
                if ($IsInBasePath) {
                    $relTargetFileName = substr($targetFileName, strlen($directoryConfig['basePath']));
                    $processFile |= empty($directoryConfig['pattern']) || preg_match($directoryConfig['pattern'], $relTargetFileName);
                }
                if ((bool)$processFile) {
                    break;  // No need to test other directories
                }
            }
            $processFile &= in_array($fileExtension, $ruleset['file_types']);
            if ((bool)$processFile) {
                // We found the ruleset to use!
                $ret = $ruleset;
                break;
            }
        }

        return $ret;
    }

    /**
     * Returns all directories found in the various rulesets.
     *
     * @return array
     * @internal For use in \Causal\ImageAutoresize\Task\BatchResizeTask::execute()
     */
    public function getAllDirectories(): array
    {
        $directories = [];
        foreach ($this->rulesets as $ruleset) {
            $directories = array_merge($directories, $ruleset['directories']);
        }
        return $directories;
    }

    /**
     * Returns all file types found in the various rulesets.
     *
     * @return array
     */
    public function getAllFileTypes(): array
    {
        $fileTypes = [];
        foreach ($this->rulesets as $ruleset) {
            if (is_array($ruleset['file_types'])) {
                $fileTypes = array_merge($fileTypes, $ruleset['file_types']);
            }
        }
        $fileTypes = array_unique($fileTypes);
        return $fileTypes;
    }

    /**
     * Compiles all FlexForm rule sets.
     *
     * @param array $rulesets
     * @return array
     */
    protected function compileRulesets(array $rulesets): array
    {
        $out = [];

        $elements = $rulesets['data']['sDEF']['lDEF']['ruleset']['el'];
        foreach ($elements as $container) {
            if (isset($container['container']['el'])) {
                $values = [];
                foreach ($container['container']['el'] as $key => $value) {
                    if ($key === 'title') {
                        continue;
                    }
                    $values[$key] = $value['vDEF'];
                }
                $out[] = $this->expandValuesInRuleset($values);
            }
        }

        return $out;
    }

    /**
     * Expands values of a rule set.
     *
     * @param array $ruleset
     * @return array
     */
    protected function expandValuesInRuleset(array $ruleset): array
    {
        $values = [];
        foreach ($ruleset as $key => $value) {
            switch ($key) {
                case 'usergroup':
                    $value = GeneralUtility::trimExplode(',', $value, true);
                    break;
                case 'directories':
                    $directories = GeneralUtility::trimExplode(',', $value, true);
                    $value = [];
                    // Sanitize name of the directories
                    foreach ($directories as $directory) {
                        $directory = rtrim($directory, '/') . '/';
                        $directoryConfig = FAL::getDirectoryConfig($directory);
                        if ($directoryConfig === null) {
                            // Either invalid storage or non local driver
                            continue;
                        }
                        $value[] = $directoryConfig;
                    }
                    break;
                case 'file_types':
                    $value = GeneralUtility::trimExplode(',', $value, true);
                    if (count($value) == 0) {
                        // Inherit configuration
                        $value = '';
                    }
                    break;
                case 'threshold':
                    if (!is_numeric($value)) {
                        $unit = strtoupper(substr($value, -1));
                        $factor = 1 * ($unit === 'K' ? 1024 : ($unit === 'M' ? 1024 * 1024 : 0));
                        $value = intval(trim((string)substr($value, 0, strlen($value) - 1))) * $factor;
                    }
                // Beware: fall-back to next value processing
                case 'max_width':
                case 'max_height':
                    if ($value <= 0) {
                        // Inherit configuration
                        $value = '';
                    }
                    break;
                case 'max_size':
                    if (!is_numeric($value)) {
                        $unit = strtoupper(substr($value, -1));
                        $factor = 1 * ($unit === 'M' ? 1000000 : 1);
                        $value = intval(trim((string)substr($value, 0, strlen($value) - 1))) * $factor;
                    }
                    break;
                case 'conversion_mapping':
                    if (strpos($value, CRLF) !== false) {
                        $mapping = GeneralUtility::trimExplode(CRLF, $value, true);
                    } else {
                        $mapping = GeneralUtility::trimExplode(',', $value, true);
                    }
                    if (count($mapping) > 0) {
                        $value = $this->expandConversionMapping($mapping);
                    } else {
                        // Inherit configuration
                        $value = '';
                    }
                    break;
            }
            $values[$key] = $value;
        }

        return $values;
    }

    /**
     * Expands the image type conversion mapping.
     *
     * @param array $mapping Array of lines similar to "bmp => jpg", "tif => jpg"
     * @return array Key/Value pairs of mapping: array('bmp' => 'jpg', 'tif' => 'jpg')
     */
    protected function expandConversionMapping(array $mapping): array
    {
        $ret = [];
        $matches = [];
        foreach ($mapping as $m) {
            if (preg_match('/^(.*)\s*=>\s*(.*)/', $m, $matches)) {
                $ret[trim($matches[1])] = trim($matches[2]);
            }
        }
        return $ret;
    }

    /**
     * Stores how many extra bytes have been freed.
     *
     * @param int $bytes
     */
    protected function reportAdditionalStorageClaimed(int $bytes): void
    {
        $fileName = Environment::getPublicPath() . '/typo3temp/.tx_imageautoresize';

        $data = [];
        if (file_exists($fileName)) {
            $data = json_decode(file_get_contents($fileName), true);
            if (!is_array($data)) {
                $data = [];
            }
        }

        $data['bytes'] = $bytes + (isset($data['bytes']) ? (int)$data['bytes'] : 0);
        $data['images'] = 1 + (isset($data['images']) ? (int)$data['images'] : 0);

        GeneralUtility::writeFile($fileName, json_encode($data));
    }

    /**
     * Gets the indexer
     *
     * @param ResourceStorage $storage
     * @return Indexer
     */
    protected function getIndexer(\TYPO3\CMS\Core\Resource\ResourceStorage $storage)
    {
        return GeneralUtility::makeInstance(Indexer::class, $storage);
    }

}
