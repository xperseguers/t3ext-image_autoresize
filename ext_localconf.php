<?php
if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

// Uploads in fileadmin/
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][] = 'EXT:' . $_EXTKEY . '/Classes/v4/class.user_fileupload_hooks.php:user_fileUpload_hooks';
// Uploads in uploads/
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'][] = 'EXT:' . $_EXTKEY . 'Classes/v4/class.user_fileupload_hooks.php:user_fileUpload_hooks';
// Uploads when using DAM
$TYPO3_CONF_VARS['EXTCONF']['dam']['fileTriggerClasses'][] = 'EXT:' . $_EXTKEY . 'Classes/v4/class.user_fileupload_hooks.php:user_fileUpload_hooks';

?>