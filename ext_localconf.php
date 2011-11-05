<?php
if (!defined ('TYPO3_MODE')) {
	die('Access denied.');
}

$version = class_exists('t3lib_utility_VersionNumber')
		? t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version)
		: t3lib_div::int_from_ver(TYPO3_version);

// Postprocessing of upload of files for TYPO3 < 4.5.0
// where required hooks do not exist
if ($version < 4005000) {
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_extfilefunc.php'] = t3lib_extMgm::extPath($_EXTKEY) . 'classes/class.ux_t3lib_extfilefunctions.php';
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tcemain.php'] = t3lib_extMgm::extPath($_EXTKEY) . 'classes/class.ux_t3lib_tcemain.php';
}

// Uploads in fileadmin/
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][] = 'EXT:' . $_EXTKEY . '/classes/class.user_fileupload_hooks.php:user_fileUpload_hooks';
// Uploads in uploads/
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'][] = 'EXT:' . $_EXTKEY . '/classes/class.user_fileupload_hooks.php:user_fileUpload_hooks';

?>