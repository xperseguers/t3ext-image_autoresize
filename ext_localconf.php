<?php
defined('TYPO3_MODE') || die();

(static function (string $_EXTKEY) {
    // Uploads in uploads/ of good old non-FAL files
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'][] = \Causal\ImageAutoresize\Hooks\FileUploadHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Causal\ImageAutoresize\Task\BatchResizeTask::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf:batchResizeTask.name',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf:batchResizeTask.description',
        'additionalFields' => \Causal\ImageAutoresize\Task\BatchResizeAdditionalFieldProvider::class,
    ];
})('image_autoresize');
