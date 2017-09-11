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

namespace Causal\ImageAutoresize\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use Causal\ImageAutoresize\Utility\ImageUtility;

/**
 * This is a utility class to resize pictures based on rules.
 *
 * @category    Service
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
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
        $this->signalSlotDispatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    }

    /**
     * Initializes the hook configuration as a meaningful ordered list
     * of rule sets.
     *
     * @param array $configuration
     * @return void
     */
    public function initializeRulesets(array $configuration)
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
            if (count($ruleset['usergroup']) == 0) {
                // Make sure not to try to override general configuration
                // => only keep directories not present in general configuration
                $ruleset['directories'] = array_diff($ruleset['directories'], $general['directories']);
                if (count($ruleset['directories']) == 0) {
                    unset($rulesets[$k]);
                }
            }
        }

        // Use general configuration as very last rule set
        $rulesets[] = $general;
        $this->rulesets = $rulesets;
    }

    /**
     * Returns the resized/converted file name (no actual processing).
     *
     * @param string $fileName
     * @param \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|null $backendUser
     * @param array $ruleset The optional ruleset to use
     * @return string|null Eiter null if no resize/conversion should take place or the resized/converted file name
     */
    public function getProcessedFileName($fileName, \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser = null, array $ruleset = null)
    {
        if ($ruleset === null) {
            $ruleset = $this->getRuleset($fileName, $fileName, $backendUser);
        }

        if (count($ruleset) === 0) {
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

        if ($fileExtension === 'png' && !$ruleset['resize_png_with_alpha']) {
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
     * @param \TYPO3\CMS\Core\Resource\File $file
     * @param \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser
     * @param callback $callbackNotification Callback to send notification
     * @return string File name that was finally written
     */
    public function processFile($fileName, $targetFileName = '', $targetDirectory = '', \TYPO3\CMS\Core\Resource\File $file = null, \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser = null, $callbackNotification = null)
    {
        $this->lastMetadata = null;

        if (!(empty($targetFileName) && empty($targetDirectory))) {
            $targetDirectory = rtrim($targetDirectory, '/') . '/';
            $ruleset = $this->getRuleset($fileName, $targetDirectory . $targetFileName, $backendUser);
        } else {
            $ruleset = $this->getRuleset($fileName, $fileName, $backendUser);
        }

        if (count($ruleset) === 0) {
            // File does not match any rule set
            return $fileName;
        }

        // Make file name relative, store as $targetFileName
        if (empty($targetFileName)) {
            $targetFileName = PathUtility::stripPathSitePrefix($fileName);
        }

        // Extract the extension
        if (($dotPosition = strrpos($fileName, '.')) === false) {
            // File has no extension
            return $fileName;
        }
        $fileExtension = strtolower(substr($fileName, $dotPosition + 1));

        if ($fileExtension === 'png' && !$ruleset['resize_png_with_alpha']) {
            if (ImageUtility::isTransparentPng($fileName)) {
                $message = sprintf(
                    $this->localize('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xlf:message.imageTransparent'),
                    $targetFileName
                );
                $this->notify($callbackNotification, $message, \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
                return $fileName;
            }
        }
        if ($fileExtension === 'gif' && ImageUtility::isAnimatedGif($fileName)) {
            $message = sprintf(
                $this->localize('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xlf:message.imageAnimated'),
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
                $this->localize('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xlf:message.imageNotWritable'),
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
                /* @var $fileFunc \TYPO3\CMS\Core\Utility\File\BasicFileUtility */
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
        /** @var $gifCreator \TYPO3\CMS\Frontend\Imaging\GifBuilder */
        $gifCreator = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Imaging\GifBuilder::class);
        $gifCreator->init();
        $gifCreator->absPrefix = PATH_site;

        $imParams = isset($gifCreator->cmds[$destExtension]) ? $gifCreator->cmds[$destExtension] : '';
        $imParams .= $ruleset['keep_metadata'] === '1' ? ' ###SkipStripProfile###' : '';
        $metadata = ImageUtility::getMetadata($fileName, true);
        $this->lastMetadata = $metadata;
        $isRotated = false;

        if ($ruleset['auto_orient'] === '1') {
            $orientation = ImageUtility::getOrientation($fileName);
            $isRotated = ImageUtility::isRotated($orientation);
            $transformation = ImageUtility::getTransformation($orientation);
            if ($transformation !== '') {
                $imParams .= ' ' . $transformation;
            }
        }

        if ($isRotated) {
            // Invert max_width and max_height as the picture
            // will be automatically rotated
            $options = [
                'maxW' => $ruleset['max_height'],
                'maxH' => $ruleset['max_width'],
            ];
        } else {
            $options = [
                'maxW' => $ruleset['max_width'],
                'maxH' => $ruleset['max_height'],
            ];
        }

        $originalFileSize = filesize($fileName);
        $tempFileInfo = null;
        $tempFileInfo = $gifCreator->imageMagickConvert($fileName, $destExtension, '', '', $imParams, '', $options, true);
        if (filesize($tempFileInfo[3]) >= $originalFileSize - 10240 && $destExtension === $fileExtension) {
            // Conversion leads to same or bigger file (rounded to 10KB to accomodate tiny variations in compression) => skip!
            $tempFileInfo = null;
        }
        if ($tempFileInfo) {
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

            $newFileSize = filesize($tempFileInfo[3]);
            $this->reportAdditionalStorageClaimed($originalFileSize - $newFileSize);

            // Replace original file
            @unlink($fileName);
            @rename($tempFileInfo[3], $destFileName);

            if ($fileName === $destFileName) {
                $message = sprintf(
                    $this->localize('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xlf:message.imageResized'),
                    $targetFileName, $tempFileInfo[0], $tempFileInfo[1]
                );
            } else {
                $message = sprintf(
                    $this->localize('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xlf:message.imageResizedAndRenamed'),
                    $targetFileName, $tempFileInfo[0], $tempFileInfo[1], PathUtility::basename($targetDestFileName)
                );
            }

            // Indexation in TYPO3 6.2 is using another signal, after the file
            // has been actually uploaded
            $this->lastMetadata['COMPUTED']['Width'] = $tempFileInfo[0];
            $this->lastMetadata['COMPUTED']['Height'] = $tempFileInfo[1];

            if ($isRotated && $ruleset['keep_metadata'] === '1' && $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] === 'gm') {
                ImageUtility::resetOrientation($destFileName);
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
    public function getLastMetadata()
    {
        return $this->lastMetadata;
    }

    /**
     * Localizes a label.
     *
     * @param string $input
     * @return string
     */
    protected function localize($input)
    {
        if (TYPO3_MODE === 'FE') {
            $output = is_object($GLOBALS['TSFE']) ? $GLOBALS['TSFE']->sL($input) : $input;
        } else {
            $output = $GLOBALS['LANG']->sL($input);
        }
        return $output;
    }

    /**
     * Sends a notification.
     *
     * @param callback $callbackNotification Callback to send notification
     * @param string $message
     * @param integer $severity
     */
    protected function notify($callbackNotification, $message, $severity)
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
     * @return array
     */
    protected function getRuleset($sourceFileName, $targetFileName, \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser = null)
    {
        $ret = [];

        // Make file name relative and extract the extension
        $relTargetFileName = substr($targetFileName, strlen(PATH_site));
        $fileExtension = strtolower(substr($targetFileName, strrpos($targetFileName, '.') + 1));

        $beGroups = $backendUser !== null ? array_keys($GLOBALS['BE_USER']->userGroups) : [];
        $fileSize = is_file($sourceFileName)
            ? filesize($sourceFileName)
            : -1;    // -1 is a special value so that file size is not taken into account (yet)

        // Try to find a matching ruleset
        foreach ($this->rulesets as $ruleset) {
            if (!is_array($ruleset['file_types'])) {
                // Default general settings do not include any watched image types
                continue;
            }
            if (count($ruleset['usergroup']) > 0 && (
                    $backendUser === null ||
                    count(array_intersect($ruleset['usergroup'], $beGroups)) == 0)
            ) {
                // Backend user is not member of a group configured for the current rule set
                continue;
            }
            $processFile = false;
            foreach ($ruleset['directories'] as $directoryPattern) {
                $processFile |= preg_match($directoryPattern, $relTargetFileName);
            }
            $processFile &= in_array($fileExtension, $ruleset['file_types']);
            $processFile &= $fileSize === -1 || ($fileSize > $ruleset['threshold']);
            if ($processFile) {
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
     */
    public function getAllDirectories()
    {
        $directories = [];
        foreach ($this->rulesets as $ruleset) {
            $dirs = GeneralUtility::trimExplode(',', $ruleset['directories_config'], true);
            $directories = array_merge($directories, $dirs);
        }
        $directories = array_unique($directories);
        asort($directories);
        return $directories;
    }

    /**
     * Returns all file types found in the various rulesets.
     *
     * @return array
     */
    public function getAllFileTypes()
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
    protected function compileRulesets(array $rulesets)
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
    protected function expandValuesInRuleset(array $ruleset)
    {
        $values = [];
        foreach ($ruleset as $key => $value) {
            switch ($key) {
                case 'usergroup':
                    $value = GeneralUtility::trimExplode(',', $value, true);
                    break;
                case 'directories':
                    $values['directories_config'] = '';
                    $value = GeneralUtility::trimExplode(',', $value, true);
                    // Sanitize name of the directories
                    foreach ($value as &$directory) {
                        $directory = rtrim($directory, '/') . '/';
                        if (!empty($values['directories_config'])) {
                            $values['directories_config'] .= ',';
                        }
                        $values['directories_config'] .= $directory;
                        $directory = $this->getDirectoryPattern($directory);
                    }
                    if (count($value) == 0) {
                        // Inherit configuration
                        $value = '';
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
                        $value = intval(trim(substr($value, 0, strlen($value) - 1))) * $factor;
                    }
                // Beware: fall-back to next value processing
                case 'max_width':
                case 'max_height':
                    if ($value <= 0) {
                        // Inherit configuration
                        $value = '';
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
    protected function expandConversionMapping(array $mapping)
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
     * Returns a regular expression pattern to match directories.
     *
     * @param string $directory
     * @return string
     */
    protected function getDirectoryPattern($directory)
    {
        $pattern = '/^' . str_replace('/', '\\/', $directory) . '/';
        $pattern = str_replace('\\/**\\/', '\\/([^\/]+\\/)*', $pattern);
        $pattern = str_replace('\\/*\\/', '\\/[^\/]+\\/', $pattern);

        return $pattern;
    }

    /**
     * Stores how many extra bytes have been freed.
     *
     * @param integer $bytes
     * @return void
     */
    protected function reportAdditionalStorageClaimed($bytes)
    {
        $fileName = PATH_site . 'typo3conf/.tx_imageautoresize';

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

}
