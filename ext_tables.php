<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	if (version_compare(TYPO3_branch, '6.2', '>=')) {
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath('xMOD_tximageautoresize', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/');
	} else {
		t3lib_extMgm::addModule('tools', 'tximageautoresizeM1', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
	}
}
