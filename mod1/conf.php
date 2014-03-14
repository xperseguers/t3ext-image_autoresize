<?php
if (version_compare(TYPO3_branch, '6.2', '>=')) {
	$MCONF['name'] = 'xMOD_tximageautoresize';
} else {
	$MCONF['name'] = 'tools_tximageautoresizeM1';
	$MCONF['access'] = 'admin';
	$MLANG['default']['tabs_images']['tab'] = '../ext_icon.gif';
	$MLANG['default']['ll_ref'] = 'LLL:EXT:image_autoresize/mod1/locallang_mod.xml';
}
$MCONF['script'] = '_DISPATCH';
