<?php

/**
 * Override core FlexForm AJAX route to use image_autoresize custom controller
 * This fixes the "Undefined array key 'tx_imageautoresize'" error by ensuring
 * the virtual TCA table is available during FlexForm AJAX operations.
 */
return [
    'record_flex_container_add' => [
        'path' => '/record/flex/containeradd',
        'target' => \Causal\ImageAutoresize\Controller\FormFlexAjaxController::class . '::containerAdd'
    ],
];
