<?php

########################################################################
# Extension Manager/Repository config file for ext "image_autoresize".
#
# Auto generated 21-08-2010 01:35
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
	'version' => '1.1.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.3.0-4.6.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:17:{s:9:"ChangeLog";s:4:"ce92";s:21:"ext_conf_template.txt";s:4:"5d39";s:12:"ext_icon.gif";s:4:"5cae";s:17:"ext_localconf.php";s:4:"d0dd";s:12:"flexform.xml";s:4:"b5f4";s:13:"locallang.xml";s:4:"8333";s:26:"locallang_csh_flexform.xml";s:4:"f16f";s:36:"locallang_csh_tx_imageautoresize.xml";s:4:"364e";s:17:"locallang_tca.xml";s:4:"2ab2";s:7:"tca.php";s:4:"bba3";s:50:"classes/class.tx_imageautoresize_configuration.php";s:4:"0608";s:39:"classes/class.user_fileupload_hooks.php";s:4:"ad8e";s:43:"classes/class.ux_t3lib_extfilefunctions.php";s:4:"cf71";s:34:"classes/class.ux_t3lib_tcemain.php";s:4:"0118";s:14:"doc/manual.sxw";s:4:"da4e";s:63:"interfaces/interface.t3lib_extfilefunctions_processdatahook.php";s:4:"fc80";s:56:"interfaces/interface.t3lib_tcemain_processuploadhook.php";s:4:"a065";}',
	'suggests' => array(
	),
);

?>