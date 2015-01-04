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

/**
 * Additional BE fields for batch resize task.
 *
 * Creates a text area where directories may be excluded from batch processing.
 *
 * @category    Task
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class BatchResizeAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {

	/**
	 * Default directories (to be processed)
	 *
	 * @var string
	 */
	protected $defaultDirectories = '';

	/**
	 * Default exclude directories
	 *
	 * @var string
	 */
	protected $defaultExcludeDirectories = '';

	/**
	 * Adds text area input fields for choosing directories to be processed and excluding directories.
	 *
	 * @param array $taskInfo Reference to the array containing the info used in the add/edit form
	 * @param object $task When editing, reference to the current task object. Null when adding.
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return array Array containing all the information pertaining to the additional fields
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		// Initialize selected fields
		if (!isset($taskInfo['scheduler_batchResize_directories'])) {
			$taskInfo['scheduler_batchResize_directories'] = $this->defaultDirectories;
			if ($parentObject->CMD === 'edit') {
				/** @var $task \Causal\ImageAutoresize\Task\BatchResizeTask */
				$taskInfo['scheduler_batchResize_directories'] = $task->directories;
			}
		}
		if (!isset($taskInfo['scheduler_batchResize_excludeDirectories'])) {
			$taskInfo['scheduler_batchResize_excludeDirectories'] = $this->defaultExcludeDirectories;
			if ($parentObject->CMD === 'edit') {
				/** @var $task \Causal\ImageAutoresize\Task\BatchResizeTask */
				$taskInfo['scheduler_batchResize_excludeDirectories'] = $task->excludeDirectories;
			}
		}

		// Create HTML form fields
		$additionalFields = array();

		// Directories to be processed
		$fieldName = 'tx_scheduler[scheduler_batchResize_directories]';
		$fieldId = 'scheduler_batchResize_directories';
		$fieldValue = trim($taskInfo['scheduler_batchResize_directories']);
		$fieldHtml = '<textarea rows="4" cols="30" name="' . $fieldName . '" id="' . $fieldId . '">' . htmlspecialchars($fieldValue) . '</textarea>';
		$additionalFields[$fieldId] = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_mod.xlf:label.batchResize.directories',
		);

		// Directories to be excluded
		$fieldName = 'tx_scheduler[scheduler_batchResize_excludeDirectories]';
		$fieldId = 'scheduler_batchResize_excludeDirectories';
		$fieldValue = trim($taskInfo['scheduler_batchResize_excludeDirectories']);
		$fieldHtml = '<textarea rows="4" cols="30" name="' . $fieldName . '" id="' . $fieldId . '">' . htmlspecialchars($fieldValue) . '</textarea>';
		$additionalFields[$fieldId] = array(
			'code' => $fieldHtml,
			'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_mod.xlf:label.batchResize.excludeDirectories',
		);

		return $additionalFields;
	}

	/**
	 * Checks if the given value is a string
	 *
	 * @param array $submittedData Reference to the array containing the data submitted by the user
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject) {
		$result = TRUE;

		// Check for valid directories
		$directories = GeneralUtility::trimExplode(LF, $submittedData['scheduler_batchResize_directories'], TRUE);
		foreach ($directories as $directory) {
			$absoluteDirectory = GeneralUtility::getFileAbsFileName($directory);
			if (!@is_dir($absoluteDirectory)) {
				$result = FALSE;
				$parentObject->addMessage(
					sprintf(
						$GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang_mod.xlf:msg.invalidDirectories'),
						$directory
					),
					\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
				);
			}
		}

		$directories = GeneralUtility::trimExplode(LF, $submittedData['scheduler_batchResize_excludeDirectories'], TRUE);
		foreach ($directories as $directory) {
			$absoluteDirectory = GeneralUtility::getFileAbsFileName($directory);
			if (!@is_dir($absoluteDirectory)) {
				$result = FALSE;
				$parentObject->addMessage(
					sprintf(
						$GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang_mod.xlf:msg.invalidExcludeDirectories'),
						$directory
					),
					\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			}
		}

		return $result;
	}

	/**
	 * Saves given string value in task object
	 *
	 * @param array $submittedData Contains data submitted by the user
	 * @param \Causal\ImageAutoresize\Task\BatchResizeTask $task Reference to the current task object
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		/** @var $task \Causal\ImageAutoresize\Task\BatchResizeTask */
		$task->directories = trim($submittedData['scheduler_batchResize_directories']);
		$task->excludeDirectories = trim($submittedData['scheduler_batchResize_excludeDirectories']);
	}

}
