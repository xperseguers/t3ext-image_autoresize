<?php
declare(strict_types = 1);

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

namespace Causal\ImageAutoresize\EventListener;

use Causal\ImageAutoresize\Controller\ConfigurationController;
use Causal\ImageAutoresize\Service\ImageResizer;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Event\SanitizeFileNameEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class CoreResourceStorageEventListener
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
            static::$imageResizer = GeneralUtility::makeInstance(ImageResizer::class);

            $configuration = ConfigurationController::readConfiguration();
            static::$imageResizer->initializeRulesets($configuration);
        }
    }

    /**
     * Sanitizes the file name.
     *
     * @param SanitizeFileNameEvent $event
     */
    public function sanitizeFileName(SanitizeFileNameEvent $event): void
    {
        $driver = $event->getDriver();
        $folder = $event->getTargetFolder();
        $fileName = $event->getFileName();

        if (!($driver instanceof LocalDriver)) {
            // Unfortunately unsupported yet
            return;
        }

        $storageConfiguration = $folder->getStorage()->getConfiguration();
        if (empty($storageConfiguration)) {
            return;
        }
        $pathSite = Environment::getPublicPath() . '/';
        $targetDirectory = $storageConfiguration['pathType'] === 'relative' ? $pathSite : '';
        $targetDirectory .= rtrim(rtrim($storageConfiguration['basePath'], '/') . $folder->getReadablePath(), '/');

        $processedFileName = static::$imageResizer->getProcessedFileName(
            $targetDirectory . '/' . $fileName,
            $this->getBackendUser()
        );
        if ($processedFileName !== null) {
            static::$originalFileName = $fileName;
            $event->setFileName(PathUtility::basename($processedFileName));
        }
    }

    /**
     * A file has been added as a *replacement* of an existing one.
     *
     * @param AfterFileReplacedEvent $event
     */
    public function afterFileReplaced(AfterFileReplacedEvent $event): void
    {
        $file = $event->getFile();
        $folder = $file->getParentFolder();

        if ($folder->getStorage()->getDriverType() !== 'Local') {
            // Unfortunately unsupported yet
            return;
        }

        $storageConfiguration = $folder->getStorage()->getConfiguration();
        if (empty($storageConfiguration)) {
            return;
        }
        $pathSite = Environment::getPublicPath() . '/';
        $targetDirectory = $storageConfiguration['pathType'] === 'relative' ? $pathSite : '';
        $targetDirectory .= rtrim(rtrim($storageConfiguration['basePath'], '/') . $folder->getReadablePath(), '/');
        $targetFileName = $targetDirectory . '/' . $file->getName();

        $targetOnlyFileName = PathUtility::basename($targetFileName);
        $this->processFile($targetFileName, $targetOnlyFileName, $targetDirectory, $file);
        $metadataEvent = new AfterFileAddedEvent($file, $folder);
        $this->populateMetadata($metadataEvent);
    }

    /**
     * Auto-resizes a given source file (possibly converting it as well).
     *
     * @param BeforeFileAddedEvent $event
     */
    public function beforeFileAdded(BeforeFileAddedEvent $event): void
    {
        $driver = $event->getDriver();
        $folder = $event->getTargetFolder();
        $sourceFile = $event->getSourceFilePath();
        $targetFileName = $event->getFileName();

        if (!($driver instanceof LocalDriver)) {
            // Unfortunately unsupported yet
            return;
        }

        $storageConfiguration = $folder->getStorage()->getConfiguration();
        if (empty($storageConfiguration)) {
            return;
        }

        if (static::$originalFileName) {
            // Temporarily change back the file name to ensure original format is used
            // when converting from one format to another with IM/GM
            $targetFileName = static::$originalFileName;
            static::$originalFileName = null;
        }

        $pathSite = Environment::getPublicPath() . '/';
        $targetDirectory = $storageConfiguration['pathType'] === 'relative' ? $pathSite : '';
        $targetDirectory .= rtrim(rtrim($storageConfiguration['basePath'], '/') . $folder->getReadablePath(), '/');

        $extension = strtolower(substr($targetFileName, strrpos($targetFileName, '.') + 1));

        // Various operation (including IM/GM) relies on a file WITH an extension
        $originalSourceFile = $sourceFile;
        $sourceFile .= '.' . $extension;

        if (rename($originalSourceFile, $sourceFile)) {
            $newSourceFile = $this->processFile($sourceFile, $targetFileName, $targetDirectory);
            $newExtension = strtolower(substr($newSourceFile, strrpos($newSourceFile, '.') + 1));

            // We must go back to original (temporary) file name
            rename($newSourceFile, $originalSourceFile);

            if ($newExtension !== $extension) {
                $event->setFileName(substr($targetFileName, 0, -strlen($extension)) . $newExtension);
            }
        }
    }

    /**
     * Populates the FAL metadata of the resized image.
     *
     * @param AfterFileAddedEvent $event
     */
    public function populateMetadata(AfterFileAddedEvent $event): void
    {
        if (is_array(static::$metadata) && !empty(static::$metadata)) {
            \Causal\ImageAutoresize\Utility\FAL::indexFile(
                $event->getFile(),
                '', '',
                (int)static::$metadata['COMPUTED']['Width'],
                (int)static::$metadata['COMPUTED']['Height'],
                static::$metadata
            );
            if (ExtensionManagementUtility::isLoaded('extractor')
                // Class CategoryHelper is available since version 2.1.0
                && class_exists(\Causal\Extractor\Utility\CategoryHelper::class)) {
                \Causal\Extractor\Utility\CategoryHelper::processCategories($event->getFile(), static::$metadata);
            }
        }
    }

    /**
     * @param string $fileName Full path to the file to be processed
     * @param string $targetFileName Target file name if not converted, no path included
     * @param string $targetDirectory
     * @param File|null $file
     * @return string
     */
    protected function processFile(string $fileName, string &$targetFileName, string $targetDirectory, ?File $file = null)
    {
        $newFileName = static::$imageResizer->processFile(
            $fileName,
            $targetFileName,
            $targetDirectory,
            $file,
            $this->getBackendUser(),
            [$this, 'notify']
        );

        static::$metadata = static::$imageResizer->getLastMetadata();

        return $newFileName;
    }

    /**
     * Notifies the user using a Flash message.
     *
     * @param string $message The message
     * @param int $severity Optional severity, must be either of \TYPO3\CMS\Core\Messaging\FlashMessage::INFO,
     *                      \TYPO3\CMS\Core\Messaging\FlashMessage::OK, \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
     *                      or \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR.
     *                      Default is \TYPO3\CMS\Core\Messaging\FlashMessage::OK.
     * @internal This method is public only to be callable from a callback
     */
    public function notify($message, $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK)
    {
        if (TYPO3_MODE !== 'BE' || PHP_SAPI === 'cli') {
            return;
        }
        $flashMessage = GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Messaging\FlashMessage::class,
            $message,
            '',
            $severity,
            true
        );
        /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
        /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Returns the current BE user, if any.
     *
     * @return BackendUserAuthentication|null
     */
    protected function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }

}