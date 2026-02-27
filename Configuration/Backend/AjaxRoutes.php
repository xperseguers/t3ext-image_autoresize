<?php

/**
 * Override core FlexForm AJAX route to use image_autoresize custom controller
 * This fixes the "Undefined array key 'tx_imageautoresize'" error by ensuring
 * the virtual TCA table is available during FlexForm AJAX operations.
 */
return [
    'record_flex_container_add' => [
        'path' => '/record/flex/containeradd',
        'target' => (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 14
            ? \Causal\ImageAutoresize\Controller\V14\FormFlexAjaxController::class . '::containerAdd'
            : \Causal\ImageAutoresize\Controller\V10\FormFlexAjaxController::class . '::containerAdd'
    ],
];
