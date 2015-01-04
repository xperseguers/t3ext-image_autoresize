<?php
namespace Causal\ImageAutoresize\Task;

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
 * Scheduler task to batch resize pictures.
 *
 * @category    Task
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class BatchResizeTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

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
	 * @return boolean TRUE if task run was successful
	 */
	public function execute() {
		$configuration = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['image_autoresize_ff'];
		if (!empty($configuration)) {
			$configuration = unserialize($configuration);
		}
		if (!is_array($configuration)) {
			throw new \RuntimeException('No configuration found', 1384103174);
		}

		$this->imageResizer = GeneralUtility::makeInstance('Causal\\ImageAutoresize\\Service\\ImageResizer');
		$this->imageResizer->initializeRulesets($configuration);

		if (empty($this->directories)) {
			// Process watched directories
			$directories = $this->imageResizer->getAllDirectories();
		} else {
			$directories = GeneralUtility::trimExplode(LF, $this->directories, TRUE);
		}
		$processedDirectories = array();

		$success = TRUE;
		foreach ($directories as $directory) {
			$skip = FALSE;
			foreach ($processedDirectories as $processedDirectory) {
				if (GeneralUtility::isFirstPartOfStr($directory, $processedDirectory)) {
					continue 2;
				}
			}

			// Execute bach resize
			$success |= $this->batchResizePictures($directory);
			$processedDirectories[] = $directory;
		}

		return $success;
	}

	/**
	 * Batch resizes pictures in a given parent directory (including all subdirectories
	 * recursively).
	 *
	 * @param string $directory
	 * @return boolean TRUE if run was successful
	 * @throws \RuntimeException
	 */
	protected function batchResizePictures($directory) {
		$directory = GeneralUtility::getFileAbsFileName($directory);
		// Check if given directory exists
		if (!@is_dir($directory)) {
			throw new \RuntimeException('Given directory "' . $directory . '" does not exist', 1384102984);
		}

		$allFileTypes = $this->imageResizer->getAllFileTypes();

		// We do not want to pass any backend user, even if manually running the task as administrator from
		// the Backend as images may be resized based on usergroup rule sets and this should only happen when
		// actually resizing the image while uploading, not during a batch processing (it's simply "too late").
		$backendUser = NULL;

		if ($GLOBALS['BE_USER']->isAdmin()) {
			// As the scheduler user should never be an administrator, if current user is an administrator
			// the task is most probably run manually from the Scheduler module, so just show notifications
			$callbackNotification = array($this, 'notify');
		} else {
			$callbackNotification = array($this, 'syslog');
		}

		$excludeDirectories = GeneralUtility::trimExplode(LF, $this->excludeDirectories, TRUE);

		$directoryContent = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
		foreach ($directoryContent as $fileName => $file) {
			$filePath = $file->getPath();
			$name = substr($fileName, strlen($filePath) + 1);

			// Skip files in recycler directory or whose type should not be processed
			$skip = $name{0} === '.' || substr($filePath, -10) === '_recycler_';
			// Skip exclude directories
			foreach ($excludeDirectories as $excludeDirectory) {
				$excludeDirectory = GeneralUtility::getFileAbsFileName($excludeDirectory);
				if (GeneralUtility::isFirstPartOfStr($filePath, $excludeDirectory) ||
					rtrim($excludeDirectory, '/') === $filePath) {
					$skip = TRUE;
					continue;
				}
			}

			if (!$skip) {
				if (($dotPosition = strrpos($name, '.')) !== FALSE) {
					$fileExtension = strtolower(substr($name, $dotPosition + 1));
					if (in_array($fileExtension, $allFileTypes)) {
						$this->imageResizer->processFile(
							$fileName,
							'',	// target file name
							'',	// target directory
							NULL,
							$backendUser,
							$callbackNotification
						);
					}
				}
			}
		}

		return TRUE;
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
		static $numberOfValidNotifications = 0;

		if ($severity <= \TYPO3\CMS\Core\Messaging\FlashMessage::OK || \TYPO3\CMS\Core\Messaging\FlashMessage::OK) {
			$numberOfValidNotifications++;
			if ($numberOfValidNotifications > 20) {
				// Do not show more "ok" messages
				return;
			}
		}

		$flashMessage = GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			$message,
			'',
			$severity,
			TRUE
		);
		\TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($flashMessage);
	}

	/**
	 * Creates an entry in syslog.
	 *
	 * @param string $message
	 * @param integer $severity
	 * @return void
	 */
	public function syslog($message, $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK) {
		switch ($severity) {
			case \TYPO3\CMS\Core\Messaging\FlashMessage::NOTICE:
				$severity = GeneralUtility::SYSLOG_SEVERITY_NOTICE;
			break;
			case \TYPO3\CMS\Core\Messaging\FlashMessage::INFO:
				$severity = GeneralUtility::SYSLOG_SEVERITY_INFO;
			break;
			case \TYPO3\CMS\Core\Messaging\FlashMessage::OK:
				$severity = GeneralUtility::SYSLOG_SEVERITY_INFO;
			break;
			case \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING:
				$severity = GeneralUtility::SYSLOG_SEVERITY_WARNING;
			break;
			case \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR:
				$severity = GeneralUtility::SYSLOG_SEVERITY_ERROR;
			break;
		}

		GeneralUtility::sysLog($message, 'image_autoresize', $severity);
	}

}
