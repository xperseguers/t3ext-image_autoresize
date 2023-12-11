<?php
defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    // Uploads in uploads/ of good old non-FAL files
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'][] = \Causal\ImageAutoresize\Hooks\FileUploadHook::class;
})('image_autoresize');
