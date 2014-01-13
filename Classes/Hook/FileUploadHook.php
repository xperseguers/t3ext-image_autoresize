<?php
namespace Causal\ImageAutoresize\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2014 Xavier Perseguers <xavier@causal.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility as CoreGeneralUtility;
use Causal\ImageAutoresize\Service\ImageResizer;

/**
 * This class extends t3lib_extFileFunctions and hooks into DAM to
 * automatically resize huge pictures upon upload.
 *
 * @category    Hook
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class FileUploadHook implements
	\TYPO3\CMS\Core\Utility\File\ExtendedFileUtilityProcessDataHookInterface,
	\TYPO3\CMS\Core\DataHandling\DataHandlerProcessUploadHookInterface {

	/**
	 * @var ImageResizer
	 */
	protected $imageResizer;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->imageResizer = CoreGeneralUtility::makeInstance('Causal\\ImageAutoresize\\Service\\ImageResizer');

		$configuration = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['image_autoresize_ff'];
		if (!$configuration) {
			$this->notify(
				$GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:message.emptyConfiguration'),
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
			);
		}
		$configuration = unserialize($configuration);
		if (is_array($configuration)) {
			$this->imageResizer->initializeRulesets($configuration);
		}
	}

	/**
	 * Post processes upload of a picture and makes sure it is not too big.
	 *
	 * @param string $filename The uploaded file
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObject The parent object
	 * @return void
	 */
	public function processUpload_postProcessAction(&$filename, \TYPO3\CMS\Core\DataHandling\DataHandler $parentObject) {
		$filename = $this->imageResizer->processFile(
			$filename,
			NULL,
			$GLOBALS['BE_USER'],
			array($this, 'notify')
		);
	}

	/**
	 * Post processes upload of a picture and makes sure it is not too big.
	 *
	 * @param string $action The action
	 * @param array $cmdArr The parameter sent to the action handler
	 * @param array $result The results of all calls to the action handler
	 * @param \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility $pObj The parent object
	 * @return void
	 */
	public function processData_postProcessAction($action, array $cmdArr, array $result, \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility $pObj) {
		if ($action === 'upload') {
			// Extract references to the uploaded files
			$files = array_pop($result);
			if (!is_array($files)) {
				return;
			}
			foreach ($files as $file) {
				/** @var $file \TYPO3\CMS\Core\Resource\File */
				$storageConfiguration = $file->getStorage()->getConfiguration();
				$storageRecord = $file->getStorage()->getStorageRecord();
				if ($storageRecord['driver'] === 'Local') {
					$filename = $storageConfiguration['pathType'] === 'relative' ? PATH_site : '';
					$filename .= rtrim($storageConfiguration['basePath'], '/') . $file->getIdentifier();
					$this->imageResizer->processFile(
						$filename,
						$file,
						$GLOBALS['BE_USER'],
						array($this, 'notify')
					);
				}
			}
		}
	}

	/**
	 * Post-processes a file operation that has already been handled by DAM.
	 *
	 * @param string $action
	 * @param array|NULL $data
	 * @return void
	 * @todo Test this hook with TYPO3 6.x
	 */
	public function filePostTrigger($action, $data) {
		if ($action === 'upload' && is_array($data)) {
			$filename = $data['target_file'];
			if (is_file($filename)) {
				$this->imageResizer->processFile(
					$filename,
					NULL,
					$GLOBALS['BE_USER'],
					array($this, 'notify')
				);
			}
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
		$flashMessage = CoreGeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			$message,
			'',
			$severity,
			TRUE
		);
		\TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($flashMessage);
	}

}
