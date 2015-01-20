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
class FileUpload {

	/**
	 * @var ImageResizer
	 */
	static protected $imageResizer;

	/**
	 * @var array|NULL
	 */
	static protected $metadata;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		if (static::$imageResizer === NULL) {
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
	 * Auto-resizes a given source file (possibly converting it as well).
	 *
	 * @param string $targetFileName
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @param string $sourceFile
	 * @return void
	 */
	public function autoResize(&$targetFileName, \TYPO3\CMS\Core\Resource\Folder $folder, $sourceFile) {
		$storageConfiguration = $folder->getStorage()->getConfiguration();
		$storageRecord = $folder->getStorage()->getStorageRecord();
		if ($storageRecord['driver'] === 'Local') {
			$targetDirectory = $storageConfiguration['pathType'] === 'relative' ? PATH_site : '';
			$targetDirectory .= rtrim($storageConfiguration['basePath'], '/') . $folder->getIdentifier();

			$extension = strtolower(substr($targetFileName, strrpos($targetFileName, '.') + 1));

			// Various operation (including IM/GM) relies on a file WITH an extension
			$originalSourceFile = $sourceFile;
			$sourceFile .= '.' . $extension;

			if (rename($originalSourceFile, $sourceFile)) {
				$newSourceFile = static::$imageResizer->processFile(
					$sourceFile,
					$targetFileName,
					$targetDirectory,
					NULL,
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
	}

	/**
	 * Populates the FAL metadata of the resized image.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileInterface $file
	 * @param \TYPO3\CMS\Core\Resource\Folder $folder
	 * @return void
	 */
	public function populateMetadata(\TYPO3\CMS\Core\Resource\FileInterface $file, \TYPO3\CMS\Core\Resource\Folder $folder) {
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
	public function notify($message, $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK) {
		if (TYPO3_MODE !== 'BE') {
			return;
		}
		$flashMessage = GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			$message,
			'',
			$severity,
			TRUE
		);
		/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
		$flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
		/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
		$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
		$defaultFlashMessageQueue->enqueue($flashMessage);
	}

}
