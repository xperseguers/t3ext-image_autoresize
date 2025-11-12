<?php

/**
 * Definitions for routes provided by EXT:image_autoresize
 */
return [
    // Register configuration module entry point
    'xMOD_tximageautoresize' => [
        'path' => '/image_autoresize/configuration',
        'target' => \Causal\ImageAutoresize\Controller\ConfigurationController::class . '::mainAction'
    ],
    'TxImageAutoresize::record_flex_container_add' => [
        'path' => '/image_autoresize/record_flex_container_add',
        'target' => (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 14
            ? \Causal\ImageAutoresize\Controller\V14\FormFlexAjaxController::class . '::containerAdd'
            : \Causal\ImageAutoresize\Controller\V10\FormFlexAjaxController::class . '::containerAdd'
    ],
];
