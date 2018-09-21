<?php

/**
 * Definitions for routes provided by EXT:image_autoresize
 */
return [
    // Register configuration module entry point
    'xMOD_tximageautoresize' => [
        'path' => '/image_autoresize/configuration/',
        'target' => \Causal\ImageAutoresize\Controller\ConfigurationController::class . '::mainAction'
    ],
    'TxImageAutoresize::record_flex_container_add' => [
        'path' => '/image_autoresize/record_flex_container_add',
        'target' => version_compare(TYPO3_branch, '9.4', '>=')
            ? \Causal\ImageAutoresize\Controller\FormFlexAjaxController::class . '::containerAdd'
            : \Causal\ImageAutoresize\Controller\FormFlexAjaxControllerV8::class . '::containerAdd'
    ],
];
