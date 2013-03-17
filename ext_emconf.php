<?php

########################################################################
# Extension Manager/Repository config file for ext "image_autoresize".
#
# Auto generated 24-09-2012 14:04
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Resize images automatically',
	'description' => 'This extension allows you to automatically resize down huge pictures uploaded by your editors.',
	'category' => 'be',
	'author' => 'Xavier Perseguers',
	'author_email' => 'xavier@causal.ch',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1',
	'doNotLoadInFE' => 1,
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'Causal Sàrl',
	'version' => '1.3.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.5.0-6.0.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:20:{s:9:"ChangeLog";s:4:"524b";s:12:"ext_icon.gif";s:4:"5cae";s:17:"ext_localconf.php";s:4:"3afb";s:14:"ext_tables.php";s:4:"685f";s:12:"flexform.xml";s:4:"f9e9";s:13:"locallang.xml";s:4:"8333";s:26:"locallang_csh_flexform.xml";s:4:"f16f";s:36:"locallang_csh_tx_imageautoresize.xml";s:4:"197d";s:17:"locallang_tca.xml";s:4:"2ab2";s:7:"tca.php";s:4:"ef6b";s:39:"classes/class.user_fileupload_hooks.php";s:4:"0801";s:43:"classes/class.ux_t3lib_extfilefunctions.php";s:4:"fe40";s:34:"classes/class.ux_t3lib_tcemain.php";s:4:"d7be";s:14:"doc/manual.sxw";s:4:"1262";s:63:"interfaces/interface.t3lib_extfilefunctions_processdatahook.php";s:4:"eb05";s:56:"interfaces/interface.t3lib_tcemain_processuploadhook.php";s:4:"3ae1";s:13:"mod1/conf.php";s:4:"198a";s:14:"mod1/index.php";s:4:"2009";s:18:"mod1/locallang.xml";s:4:"26e3";s:22:"mod1/locallang_mod.xml";s:4:"705f";}',
	'suggests' => array(
	),
);

?>