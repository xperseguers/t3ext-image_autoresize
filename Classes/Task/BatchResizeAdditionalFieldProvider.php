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

use Causal\ImageAutoresize\Utility\FAL;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Additional BE fields for batch resize task.
 *
 * Creates a text area where directories may be excluded from batch processing.
 *
 * @category    Task
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
class BatchResizeAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{

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
    public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $editCommand = (string)$parentObject->getCurrentAction() === Action::EDIT;

        // Initialize selected fields
        if (!isset($taskInfo['scheduler_batchResize_directories'])) {
            $taskInfo['scheduler_batchResize_directories'] = $this->defaultDirectories;
            if ($editCommand) {
                /** @var \Causal\ImageAutoresize\Task\BatchResizeTask $task */
                $taskInfo['scheduler_batchResize_directories'] = $task->directories;
            }
        }
        if (!isset($taskInfo['scheduler_batchResize_excludeDirectories'])) {
            $taskInfo['scheduler_batchResize_excludeDirectories'] = $this->defaultExcludeDirectories;
            if ($editCommand) {
                /** @var \Causal\ImageAutoresize\Task\BatchResizeTask $task */
                $taskInfo['scheduler_batchResize_excludeDirectories'] = $task->excludeDirectories;
            }
        }

        // Create HTML form fields
        $additionalFields = [];

        // Directories to be processed
        $fieldName = 'tx_scheduler[scheduler_batchResize_directories]';
        $fieldId = 'scheduler_batchResize_directories';
        $fieldValue = trim($taskInfo['scheduler_batchResize_directories']);
        $fieldHtml = '<textarea class="form-control" rows="4" name="' . $fieldName . '" id="' . $fieldId . '">' . htmlspecialchars($fieldValue) . '</textarea>';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_mod.xlf:label.batchResize.directories',
        ];

        // Directories to be excluded
        $fieldName = 'tx_scheduler[scheduler_batchResize_excludeDirectories]';
        $fieldId = 'scheduler_batchResize_excludeDirectories';
        $fieldValue = trim($taskInfo['scheduler_batchResize_excludeDirectories']);
        $fieldHtml = '<textarea class="form-control" rows="4" name="' . $fieldName . '" id="' . $fieldId . '">' . htmlspecialchars($fieldValue) . '</textarea>';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:image_autoresize/Resources/Private/Language/locallang_mod.xlf:label.batchResize.excludeDirectories',
        ];

        return $additionalFields;
    }

    /**
     * Checks if the given value is a string
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool true if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $result = true;

        // Check for valid directories
        $directories = GeneralUtility::trimExplode(LF, $submittedData['scheduler_batchResize_directories'], true);
        foreach ($directories as $directory) {
            $directoryConfig = FAL::getDirectoryConfig($directory);
            if (!@is_dir($directoryConfig['basePath'] . $directoryConfig['directory'])) {
                $result = false;

                GeneralUtility::makeInstance(
                    FlashMessage::class,
                    sprintf(
                        $GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang_mod.xlf:msg.invalidDirectories'),
                        $directory
                    ),
                    'EXT:image_autoresize: invalidDirectories error',
                    ContextualFeedbackSeverity::ERROR,
                    true
                );
            }
        }

        $directories = GeneralUtility::trimExplode(LF, $submittedData['scheduler_batchResize_excludeDirectories'], true);
        foreach ($directories as $directory) {
            $directoryConfig = FAL::getDirectoryConfig($directory);
            if (!@is_dir($directoryConfig['basePath'] . $directoryConfig['directory'])) {
                $result = false;


                GeneralUtility::makeInstance(
                    FlashMessage::class,
                    sprintf(
                        $GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang_mod.xlf:msg.invalidExcludeDirectories'),
                        $directory
                    ),
                    'EXT:image_autoresize: invalidExcludeDirectories error',
                    ContextualFeedbackSeverity::ERROR,
                    true
                );
            }
        }

        return $result;
    }

    /**
     * Saves given string value in task object
     *
     * @param array $submittedData Contains data submitted by the user
     * @param \Causal\ImageAutoresize\Task\BatchResizeTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        /** @var \Causal\ImageAutoresize\Task\BatchResizeTask $task */
        $task->directories = trim($submittedData['scheduler_batchResize_directories']);
        $task->excludeDirectories = trim($submittedData['scheduler_batchResize_excludeDirectories']);
    }
}
