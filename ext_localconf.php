<?php
defined('TYPO3_MODE') || die();

(static function (string $_EXTKEY) {
    $typo3Branch = class_exists(\TYPO3\CMS\Core\Information\Typo3Version::class)
        ? (new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch()
        : TYPO3_branch;
    if (version_compare($typo3Branch, '10.2', '<')) {
        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

        // Hook into \TYPO3\CMS\Core\Resource\ResourceStorage
        $signalSlotDispatcher->connect(
            'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_SanitizeFileName,
            \Causal\ImageAutoresize\Slots\FileUpload::class,
            \Causal\ImageAutoresize\Slots\FileUpload::SIGNAL_SanitizeFileName
        );
        $signalSlotDispatcher->connect(
            'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileReplace,
            \Causal\ImageAutoresize\Slots\FileUpload::class,
            \Causal\ImageAutoresize\Slots\FileUpload::SIGNAL_PostFileReplace
        );
        $signalSlotDispatcher->connect(
            'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileAdd,
            \Causal\ImageAutoresize\Slots\FileUpload::class,
            \Causal\ImageAutoresize\Slots\FileUpload::SIGNAL_PreFileAdd
        );
        $signalSlotDispatcher->connect(
            'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd,
            \Causal\ImageAutoresize\Slots\FileUpload::class,
            \Causal\ImageAutoresize\Slots\FileUpload::SIGNAL_PopulateMetadata
        );
        $signalSlotDispatcher->connect(
            'TYPO3\\CMS\\Extensionmanager\\ViewHelpers\\ProcessAvailableActionsViewHelper',
            \TYPO3\CMS\Extensionmanager\ViewHelpers\ProcessAvailableActionsViewHelper::SIGNAL_ProcessActions,
            \Causal\ImageAutoresize\Slots\ExtensionManager::class,
            \Causal\ImageAutoresize\Slots\ExtensionManager::SIGNAL_ProcessActions
        );
    }

    // Uploads in uploads/ of good old non-FAL files
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'][] = \Causal\ImageAutoresize\Hooks\FileUploadHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Causal\ImageAutoresize\Task\BatchResizeTask::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf:batchResizeTask.name',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf:batchResizeTask.description',
        'additionalFields' => \Causal\ImageAutoresize\Task\BatchResizeAdditionalFieldProvider::class,
    ];
})('image_autoresize');
