<?php
namespace Causal\ImageAutoresize\Slots;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use Causal\ImageAutoresize\Service\ImageResizer;

/**
 * Slot implementation when a file is uploaded but before it is processed
 * by \TYPO3\CMS\Core\Resource\ResourceStorage to automatically resize
 * huge pictures.
 *
 * @category    Slots
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FileUpload
{

    /**
     * @var ImageResizer
     */
    protected static $imageResizer;

    /**
     * @var array|null
     */
    protected static $metadata;

    /**
     * @var string|null
     */
    protected static $originalFileName;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        if (static::$imageResizer === null) {
            static::$imageResizer = GeneralUtility::makeInstance('Causal\\ImageAutoresize\\Service\\ImageResizer');

            $configuration = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['image_autoresize_ff'];
            if (!$configuration) {
                $this->notify(
                    $GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:message.emptyConfiguration'),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
            }
            $configuration = unserialize($configuration);
            if (is_array($configuration)) {
                static::$imageResizer->initializeRulesets($configuration);
            }
        }
    }

    /**
     * Sanitizes the file name.
     *
     * @param string $fileName
     * @param \TYPO3\CMS\Core\Resource\Folder $folder
     * @return void|array
     */
    public function sanitizeFileName($fileName, \TYPO3\CMS\Core\Resource\Folder $folder)
    {
        $slotArguments = func_get_args();
        // Last parameter is the signal name itself and is not actually part of the arguments
        array_pop($slotArguments);

        $storageConfiguration = $folder->getStorage()->getConfiguration();
        $storageRecord = $folder->getStorage()->getStorageRecord();
        if ($storageRecord['driver'] !== 'Local') {
            // Unfortunately unsupported yet
            return;
        }

        $targetDirectory = $storageConfiguration['pathType'] === 'relative' ? PATH_site : '';
        $targetDirectory .= rtrim(rtrim($storageConfiguration['basePath'], '/') . $folder->getIdentifier(), '/');

        $processedFileName = static::$imageResizer->getProcessedFileName(
            $targetDirectory . '/' . $fileName,
            $GLOBALS['BE_USER']
        );
        if ($processedFileName !== null) {
            static::$originalFileName = $fileName;
            $slotArguments[0] = PathUtility::basename($processedFileName);

            return $slotArguments;
        }
    }

    /**
     * Auto-resizes a given source file (possibly converting it as well).
     *
     * @param string $targetFileName
     * @param \TYPO3\CMS\Core\Resource\Folder $folder
     * @param string $sourceFile
     * @return void
     */
    public function autoResize(&$targetFileName, \TYPO3\CMS\Core\Resource\Folder $folder, $sourceFile)
    {
        $storageConfiguration = $folder->getStorage()->getConfiguration();
        $storageRecord = $folder->getStorage()->getStorageRecord();
        if ($storageRecord['driver'] !== 'Local') {
            // Unfortunately unsupported yet
            return;
        }

        if (static::$originalFileName) {
            // Temporarily change back the file name to ensure original format is used
            // when converting from one format to another with IM/GM
            $targetFileName = static::$originalFileName;
            static::$originalFileName = null;
        }

        $targetDirectory = $storageConfiguration['pathType'] === 'relative' ? PATH_site : '';
        $targetDirectory .= rtrim(rtrim($storageConfiguration['basePath'], '/') . $folder->getIdentifier(), '/');

        $extension = strtolower(substr($targetFileName, strrpos($targetFileName, '.') + 1));

        // Various operation (including IM/GM) relies on a file WITH an extension
        $originalSourceFile = $sourceFile;
        $sourceFile .= '.' . $extension;

        if (rename($originalSourceFile, $sourceFile)) {
            $newSourceFile = static::$imageResizer->processFile(
                $sourceFile,
                $targetFileName,
                $targetDirectory,
                null,
                $GLOBALS['BE_USER'],
                array($this, 'notify')
            );

            static::$metadata = static::$imageResizer->getLastMetadata();

            $newExtension = strtolower(substr($newSourceFile, strrpos($newSourceFile, '.') + 1));

            // We must go back to original (temporary) file name
            rename($newSourceFile, $originalSourceFile);

            if ($newExtension !== $extension) {
                $targetFileName = substr($targetFileName, 0, -strlen($extension)) . $newExtension;
            }
        }
    }

    /**
     * Populates the FAL metadata of the resized image.
     *
     * @param \TYPO3\CMS\Core\Resource\FileInterface $file
     * @param \TYPO3\CMS\Core\Resource\Folder $folder
     * @return void
     */
    public function populateMetadata(\TYPO3\CMS\Core\Resource\FileInterface $file, \TYPO3\CMS\Core\Resource\Folder $folder)
    {
        if (is_array(static::$metadata) && count(static::$metadata)) {
            \Causal\ImageAutoresize\Utility\FAL::indexFile(
                $file,
                '', '',
                static::$metadata['COMPUTED']['Width'],
                static::$metadata['COMPUTED']['Height'],
                static::$metadata
            );
        }
    }

    /**
     * Notifies the user using a Flash message.
     *
     * @param string $message The message
     * @param integer $severity Optional severity, must be either of \TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
     *                          \TYPO3\CMS\Core\Messaging\FlashMessage::OK, \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
     *                          or \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR.
     *                          Default is \TYPO3\CMS\Core\Messaging\FlashMessage::OK.
     * @return void
     * @internal This method is public only to be callable from a callback
     */
    public function notify($message, $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK)
    {
        if (TYPO3_MODE !== 'BE') {
            return;
        }
        $flashMessage = GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
            $message,
            '',
            $severity,
            true
        );
        /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

}
