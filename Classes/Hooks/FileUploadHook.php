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

namespace Causal\ImageAutoresize\Hooks;

use Causal\ImageAutoresize\Controller\ConfigurationController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\DataHandlerProcessUploadHookInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Causal\ImageAutoresize\Service\ImageResizer;

/**
 * This class extends \TYPO3\CMS\Core\DataHandling\DataHandler and hooks into the
 * upload of old, non-FAL files to uploads/ directory.
 *
 * @category    Hooks
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
class FileUploadHook implements DataHandlerProcessUploadHookInterface
{

    /**
     * @var ImageResizer
     */
    protected static $imageResizer;

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
     * Post-processes a file upload.
     *
     * @param string $filename The uploaded file
     * @param DataHandler $parentObject
     */
    public function processUpload_postProcessAction(&$filename, DataHandler $pObj)
    {
        $filename = static::$imageResizer->processFile(
            $filename,
            '', // Target file name
            '', // Target directory
            null,
            $this->getBackendUser(),
            [$this, 'notify']
        );
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
    public function notify(string $message, int $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK): void
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
