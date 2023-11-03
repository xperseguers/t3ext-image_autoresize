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

namespace Causal\ImageAutoresize\Task;

use Causal\ImageAutoresize\Controller\ConfigurationController;
use Causal\ImageAutoresize\Utility\FAL;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Causal\ImageAutoresize\Service\ImageResizer;

/**
 * Scheduler task to batch resize pictures.
 *
 * @category    Task
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
class BatchResizeTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * @var string
     * @additionalField
     */
    public $directories = '';

    /**
     * @var string
     * @additionalField
     */
    public $excludeDirectories = '';

    /**
     * @var ImageResizer
     */
    protected $imageResizer;

    /**
     * Batch resize pictures, called by scheduler.
     *
     * @return bool true if task run was successful
     */
    public function execute()
    {
        $configuration = ConfigurationController::readConfiguration();
        $pathSite = Environment::getPublicPath() . '/';

        $this->imageResizer = GeneralUtility::makeInstance(\Causal\ImageAutoresize\Service\ImageResizer::class);
        $this->imageResizer->initializeRulesets($configuration);

        if (empty($this->directories)) {
            // Process watched directories
            $directories = $this->imageResizer->getAllDirectories();
        } else {
            $dirs = GeneralUtility::trimExplode(LF, $this->directories, true);
            $directories = [];
            foreach ($dirs as $directory) {
                $directoryConfig = FAL::getDirectoryConfig($directory);
                if ($directoryConfig !== null) {
                    $directories[] = $directoryConfig;
                }
            }
        }
        $processedDirectories = [];

        // Expand watched directories if they contain wildcard characters
        $expandedDirectories = [];
        foreach ($directories as $directoryConfig) {
            if (($pos = strpos($directoryConfig['directory'], '/*')) !== false) {
                $pattern = $directoryConfig['pattern'];
                $basePath = $directoryConfig['basePath'] . substr($directoryConfig['directory'], 0, $pos + 1);

                $objects = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($basePath),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($objects as $name => $object) {
                    $relativePath = substr($name, strlen($pathSite));
                    if (substr($relativePath, -2) === DIRECTORY_SEPARATOR . '.') {
                        if (preg_match($pattern, $relativePath)) {
                            $expandedDirectories[] = substr($relativePath, 0, -1);
                        }
                    }
                }
            } else {
                $expandedDirectories[] = $directoryConfig['basePath'] . $directoryConfig['directory'];
            }
        }
        $directories = $expandedDirectories;
        sort($directories);

        $success = true;
        foreach ($directories as $directory) {
            foreach ($processedDirectories as $processedDirectory) {
                $isInProcessedDirectory = PHP_VERSION_ID >= 80000
                    ? str_starts_with($directory, $processedDirectory)
                    : GeneralUtility::isFirstPartOfStr($directory, $processedDirectory);
                if ($isInProcessedDirectory) {
                    continue 2;
                }
            }

            // Execute bach resize
            if (is_dir($directory)) {
                $success |= $this->batchResizePictures($directory);
            }
            $processedDirectories[] = $directory;
        }

        return $success;
    }

    /**
     * Batch resizes pictures in a given parent directory (including all subdirectories
     * recursively).
     *
     * @param string $absolutePath
     * @return bool true if run was successful
     * @throws \RuntimeException
     */
    protected function batchResizePictures(string $absolutePath): bool
    {
        // Check if given directory exists
        if (!@is_dir($absolutePath)) {
            throw new \RuntimeException('Given directory "' . $absolutePath . '" does not exist', 1384102984);
        }

        $allFileTypes = $this->imageResizer->getAllFileTypes();

        // We do not want to pass any backend user, even if manually running the task as administrator from
        // the Backend as images may be resized based on usergroup rule sets and this should only happen when
        // actually resizing the image while uploading, not during a batch processing (it's simply "too late").
        $backendUser = null;

        if ((($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof \Psr\Http\Message\ServerRequestInterface
                && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            ) || Environment::isCli()) {
            $callbackNotification = [$this, 'syslog'];
        } else {
            $callbackNotification = [$this, 'notify'];
        }

        $dirs = GeneralUtility::trimExplode(LF, $this->excludeDirectories, true);
        $excludeDirectories = [];
        foreach ($dirs as $directory) {
            $directoryConfig = FAL::getDirectoryConfig($directory);
            if ($directoryConfig !== null) {
                $excludeDirectories[] = $directoryConfig['basePath'] . $directoryConfig['directory'];
            }
        }

        $directoryContent = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($absolutePath));
        foreach ($directoryContent as $fileName => $file) {
            $filePath = $file->getPath();
            $name = substr($fileName, strlen($filePath) + 1);

            // Skip files in recycler directory or whose type should not be processed
            $skip = substr($name, 0, 1) === '.' || substr($filePath, -10) === '_recycler_';
            if (!$skip) {
                // Check if we should skip since in one of the exclude directories
                foreach ($excludeDirectories as $excludeDirectory) {
                    $isInExcludeDirectory = PHP_VERSION_ID >= 80000
                        ? str_starts_with($filePath, $excludeDirectory)
                        : GeneralUtility::isFirstPartOfStr($filePath, $excludeDirectory);
                    if ($isInExcludeDirectory || rtrim($excludeDirectory, '/') === $filePath
                    ) {
                        $skip = true;
                        break;
                    }
                }
            }

            if (!$skip) {
                if (($dotPosition = strrpos($name, '.')) !== false) {
                    $fileExtension = strtolower(substr($name, $dotPosition + 1));
                    if (in_array($fileExtension, $allFileTypes)) {
                        $this->imageResizer->processFile(
                            $fileName,
                            '',    // target file name
                            '',    // target directory
                            null,
                            $backendUser,
                            $callbackNotification
                        );
                    }
                }
            }
        }

        return true;
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
    public function notify(string $message, int $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK)
    {
        static $numberOfValidNotifications = 0;

        if ($severity <= \TYPO3\CMS\Core\Messaging\FlashMessage::OK || \TYPO3\CMS\Core\Messaging\FlashMessage::OK) {
            $numberOfValidNotifications++;
            if ($numberOfValidNotifications > 20) {
                // Do not show more "ok" messages
                return;
            }
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
     * Creates an entry in syslog.
     *
     * @param string $message
     * @param int $severity
     */
    public function syslog($message, $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK)
    {
        /** @var LoggerInterface $logger */
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        switch ($severity) {
            case \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE:
                $logger->notice($message);
                break;
            case \TYPO3\CMS\Core\Messaging\FlashMessage::INFO:
                $logger->info($message);
                break;
            case \TYPO3\CMS\Core\Messaging\FlashMessage::OK:
                $logger->log(\Psr\Log\LogLevel::INFO, $message);
                break;
            case \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING:
                $logger->warning($message);
                break;
            case \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR:
                $logger->error($message);
                break;
        }
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

}
