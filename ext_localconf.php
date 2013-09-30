<?php
if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

if (version_compare(TYPO3_version, '6.0.0', '>=')) {
	// Uploads in fileadmin/
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][] = 'Causal\\ImageAutoresize\\Hook\\FileUploadHook';
	// Uploads in uploads/
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'][] = 'Causal\\ImageAutoresize\\Hook\\FileUploadHook';
	// Uploads when using DAM
	$TYPO3_CONF_VARS['EXTCONF']['dam']['fileTriggerClasses'][] = 'Causal\\ImageAutoresize\\Hook\\FileUploadHook';
} else {
	// Uploads in fileadmin/
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][] = 'EXT:' . $_EXTKEY . '/Classes/v4/class.user_fileupload_hooks.php:user_fileUpload_hooks';
	// Uploads in uploads/
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'][] = 'EXT:' . $_EXTKEY . '/Classes/v4/class.user_fileupload_hooks.php:user_fileUpload_hooks';
	// Uploads when using DAM
	$TYPO3_CONF_VARS['EXTCONF']['dam']['fileTriggerClasses'][] = 'EXT:' . $_EXTKEY . '/Classes/v4/class.user_fileupload_hooks.php:user_fileUpload_hooks';
}
