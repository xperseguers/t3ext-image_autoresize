<?php

########################################################################
# Extension Manager/Repository config file for ext "image_autoresize".
#
# Auto generated 21-07-2010 21:13
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
	'author_email' => 'typo3@perseguers.ch',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.1',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.3.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:8:{s:9:"ChangeLog";s:4:"ca41";s:21:"ext_conf_template.txt";s:4:"be72";s:12:"ext_icon.gif";s:4:"5cae";s:17:"ext_localconf.php";s:4:"3dd4";s:13:"locallang.xml";s:4:"adf0";s:50:"classes/class.user_t3lib_extfilefunctions_hook.php";s:4:"9ed5";s:43:"classes/class.ux_t3lib_extfilefunctions.php";s:4:"1810";s:63:"interfaces/interface.t3lib_extfilefunctions_processdatahook.php";s:4:"fc80";}',
	'suggests' => array(
	),
);

?>