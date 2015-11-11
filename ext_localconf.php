<?php
defined('TYPO3_MODE') || die();

/** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
$signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');

// Hook into \TYPO3\CMS\Core\Resource\ResourceStorage
if (version_compare(TYPO3_version, '7.4.0', '>=')) {
    $signalSlotDispatcher->connect(
        'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
        \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_SanitizeFileName,
        'Causal\\ImageAutoresize\\Slots\\FileUpload',
        \Causal\ImageAutoresize\Slots\FileUpload::SIGNAL_SanitizeFileName
    );
    $signalSlotDispatcher->connect(
        'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
        \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileReplace,
        'Causal\\ImageAutoresize\\Slots\\FileUpload',
        \Causal\ImageAutoresize\Slots\FileUpload::SIGNAL_PostFileReplace
    );
}
$signalSlotDispatcher->connect(
    'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFileAdd,
    'Causal\\ImageAutoresize\\Slots\\FileUpload',
    \Causal\ImageAutoresize\Slots\FileUpload::SIGNAL_PreFileAdd
);
$signalSlotDispatcher->connect(
    'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
    \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFileAdd,
    'Causal\\ImageAutoresize\\Slots\\FileUpload',
    \Causal\ImageAutoresize\Slots\FileUpload::SIGNAL_PopulateMetadata
);
$signalSlotDispatcher->connect(
    'TYPO3\\CMS\\Extensionmanager\\ViewHelpers\\ProcessAvailableActionsViewHelper',
    \TYPO3\CMS\Extensionmanager\ViewHelpers\ProcessAvailableActionsViewHelper::SIGNAL_ProcessActions,
    'Causal\\ImageAutoresize\\Slots\\ExtensionManager',
    \Causal\ImageAutoresize\Slots\ExtensionManager::SIGNAL_ProcessActions
);

$extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Causal\\ImageAutoresize\\Task\\BatchResizeTask'] = array(
    'extension' => $_EXTKEY,
    'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf:batchResizeTask.name',
    'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf:batchResizeTask.description',
    'additionalFields' => 'Causal\\ImageAutoresize\\Task\\BatchResizeAdditionalFieldProvider',
);
