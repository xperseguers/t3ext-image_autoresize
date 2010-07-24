<?php
if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

// Postprocessing of upload of files
if (!version_compare(TYPO3_version, '4.4.99', '>')) {
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_extfilefunc.php'] = t3lib_extMgm::extPath($_EXTKEY) . 'classes/class.ux_t3lib_extfilefunctions.php';
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][] = 'EXT:' . $_EXTKEY . '/classes/class.user_fileupload_hooks.php:user_fileUpload_hooks';

if (version_compare(TYPO3_version, '4.4.99', '>')) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'][] = 'EXT:' . $_EXTKEY . '/classes/class.user_fileupload_hooks.php:user_fileUpload_hooks';
}
?>