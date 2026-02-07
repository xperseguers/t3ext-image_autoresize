<?php

defined('TYPO3') || die();

if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 14) {
    $GLOBALS['TCA']['tx_imageautoresize'] = include(TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('image_autoresize') . 'Configuration/TCA/Module/Options.php');
}
