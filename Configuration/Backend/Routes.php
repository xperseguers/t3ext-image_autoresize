<?php

/**
 * Definitions for routes provided by EXT:image_autoresize
 */
$typo3Branch = class_exists(\TYPO3\CMS\Core\Information\Typo3Version::class)
    ? (new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch()
    : TYPO3_branch;
return [
    // Register configuration module entry point
    'xMOD_tximageautoresize' => [
        'path' => '/image_autoresize/configuration/',
        'target' => \Causal\ImageAutoresize\Controller\ConfigurationController::class . '::mainAction'
    ],
    'TxImageAutoresize::record_flex_container_add' => [
        'path' => '/image_autoresize/record_flex_container_add',
        'target' => version_compare($typo3Branch, '9.4', '>=')
            ? \Causal\ImageAutoresize\Controller\FormFlexAjaxController::class . '::containerAdd'
            : \Causal\ImageAutoresize\Controller\FormFlexAjaxControllerV8::class . '::containerAdd'
    ],
];
