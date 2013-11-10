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

	$extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY);
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Causal\\ImageAutoresize\\Task\\BatchResizeTask'] = array(
		'extension' => $_EXTKEY,
		'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf:batchResizeTask.name',
		'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf:batchResizeTask.description',
	);

} else {
	// Uploads in fileadmin/
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'][] = 'EXT:' . $_EXTKEY . '/Classes/v4/class.user_fileupload_hooks.php:user_fileUpload_hooks';
	// Uploads in uploads/
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processUpload'][] = 'EXT:' . $_EXTKEY . '/Classes/v4/class.user_fileupload_hooks.php:user_fileUpload_hooks';
	// Uploads when using DAM
	$TYPO3_CONF_VARS['EXTCONF']['dam']['fileTriggerClasses'][] = 'EXT:' . $_EXTKEY . '/Classes/v4/class.user_fileupload_hooks.php:user_fileUpload_hooks';
}
