<?php
defined('TYPO3_MODE') || die();

if (TYPO3_MODE === 'BE') {
    if (version_compare(TYPO3_version, '7.6', '<')) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
            'xMOD_tximageautoresize',
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/'
        );
    }
}
