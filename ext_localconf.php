<?php

defined('TYPO3') or die();

$boot = static function () {
    $typo3Version = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();
    if ($typo3Version >= 14) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Core\Database\Schema\SchemaMigrator::class] = [
            'className' => \Causal\ImageAutoresize\Xclass\V14\SchemaMigratorXclassed::class,
        ];
    }
};

$boot();
unset($boot);
