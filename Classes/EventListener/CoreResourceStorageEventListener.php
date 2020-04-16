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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Event\SanitizeFileNameEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
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
        // TODO
    }

    /**
     * Auto-resizes a given source file (possibly converting it as well).
     *
     * @param BeforeFileAddedEvent $event
     */
    public function beforeFileAdded(BeforeFileAddedEvent $event): void
    {
        // TODO
    }

    /**
     * Populates the FAL metadata of the resized image.
     *
     * @param AfterFileAddedEvent $event
     */
    public function populateMetadata(AfterFileAddedEvent $event): void
    {
        // TODO
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

}