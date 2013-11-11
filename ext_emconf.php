<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "image_autoresize".
 *
 * Auto generated 02-05-2013 15:28
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Resize images automatically',
	'description' => 'Simplify the way your editors may upload their pictures: no complex local procedure needed, let TYPO3 resize down their huge pictures on-the-fly during upload (or using a scheduler task for batch processing) and according to your own business rules (directory/groups).',
	'category' => 'be',
	'author' => 'Xavier Perseguers (Causal)',
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
	'version' => '1.4.0-dev',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.3-5.4.99',
			'typo3' => '4.5.0-6.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:24:{s:9:"ChangeLog";s:4:"7b7a";s:12:"ext_icon.gif";s:4:"5cae";s:17:"ext_localconf.php";s:4:"903e";s:14:"ext_tables.php";s:4:"685f";s:31:"Classes/Hook/FileUploadHook.php";s:4:"c3aa";s:34:"Classes/Utility/JpegExifOrient.php";s:4:"fa8e";s:42:"Classes/v4/class.user_fileupload_hooks.php";s:4:"5619";s:36:"Configuration/FlexForms/Rulesets.xml";s:4:"c52d";s:29:"Configuration/TCA/Options.php";s:4:"34a6";s:40:"Resources/Private/Language/locallang.xlf";s:4:"b58a";s:40:"Resources/Private/Language/locallang.xml";s:4:"3940";s:53:"Resources/Private/Language/locallang_csh_flexform.xlf";s:4:"852f";s:53:"Resources/Private/Language/locallang_csh_flexform.xml";s:4:"ab6b";s:63:"Resources/Private/Language/locallang_csh_tx_imageautoresize.xlf";s:4:"a0e9";s:63:"Resources/Private/Language/locallang_csh_tx_imageautoresize.xml";s:4:"edb3";s:44:"Resources/Private/Language/locallang_tca.xlf";s:4:"d976";s:44:"Resources/Private/Language/locallang_tca.xml";s:4:"b9ff";s:14:"doc/manual.sxw";s:4:"f72f";s:13:"mod1/conf.php";s:4:"198a";s:14:"mod1/index.php";s:4:"6d05";s:18:"mod1/locallang.xlf";s:4:"e3e3";s:18:"mod1/locallang.xml";s:4:"26e3";s:22:"mod1/locallang_mod.xlf";s:4:"b0fa";s:22:"mod1/locallang_mod.xml";s:4:"3bb1";}',
	'suggests' => array(
	),
);

?>