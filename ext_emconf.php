<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "image_autoresize".
 *
 * Auto generated 29-11-2014 17:27
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Resize images automatically',
	'description' => 'Simplify the way your editors may upload their images: no complex local procedure needed, let TYPO3 automatically resize down their huge images/pictures on-the-fly during upload (or using a scheduler task for batch processing) and according to your own business rules (directory/groups). This will highly reduce the footprint on your server and speed-up response time if lots of images are rendered (e.g., in a gallery). Features an EXIF/IPTC extractor to ensure metadata may be used by the FAL indexer even if not preserved upon resizing.',
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
	'version' => '1.6.1-dev',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.3-5.6.99',
			'typo3' => '6.2.0-7.99.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:59:{s:13:"composer.json";s:4:"3a98";s:12:"ext_icon.gif";s:4:"7241";s:12:"ext_icon.png";s:4:"cd93";s:17:"ext_localconf.php";s:4:"43c1";s:14:"ext_tables.php";s:4:"2824";s:32:"Classes/Service/ImageResizer.php";s:4:"f1fc";s:34:"Classes/Slots/ExtensionManager.php";s:4:"8d22";s:28:"Classes/Slots/FileUpload.php";s:4:"122a";s:51:"Classes/Task/BatchResizeAdditionalFieldProvider.php";s:4:"283d";s:32:"Classes/Task/BatchResizeTask.php";s:4:"2626";s:23:"Classes/Utility/FAL.php";s:4:"9285";s:32:"Classes/Utility/ImageUtility.php";s:4:"c40a";s:34:"Classes/Utility/JpegExifOrient.php";s:4:"5364";s:36:"Configuration/FlexForms/Rulesets.xml";s:4:"c52d";s:36:"Configuration/TCA/Module/Options.php";s:4:"c6bb";s:26:"Documentation/Includes.txt";s:4:"656f";s:23:"Documentation/Index.rst";s:4:"6cd5";s:23:"Documentation/Links.rst";s:4:"349a";s:26:"Documentation/Settings.yml";s:4:"26aa";s:25:"Documentation/Targets.rst";s:4:"94c2";s:43:"Documentation/AdministratorManual/Index.rst";s:4:"f27b";s:59:"Documentation/AdministratorManual/BatchProcessing/Index.rst";s:4:"c74d";s:74:"Documentation/AdministratorManual/ConfiguringExtension/GeneralSettings.rst";s:4:"11b4";s:66:"Documentation/AdministratorManual/ConfiguringExtension/Options.rst";s:4:"9fc1";s:67:"Documentation/AdministratorManual/ConfiguringExtension/RuleSets.rst";s:4:"7728";s:63:"Documentation/AdministratorManual/InstallingExtension/Index.rst";s:4:"85ea";s:33:"Documentation/ChangeLog/Index.rst";s:4:"70c4";s:39:"Documentation/DeveloperManual/Index.rst";s:4:"a424";s:54:"Documentation/DeveloperManual/PostProcessingImages.rst";s:4:"f0be";s:42:"Documentation/FurtherInformation/Index.rst";s:4:"dc2a";s:43:"Documentation/Images/example-autoresize.png";s:4:"746a";s:42:"Documentation/Images/example-converted.png";s:4:"1612";s:41:"Documentation/Images/exif-orientation.png";s:4:"7063";s:56:"Documentation/Images/extension-manager-configuration.png";s:4:"447a";s:34:"Documentation/Images/footprint.png";s:4:"a098";s:46:"Documentation/Images/general-configuration.png";s:4:"5165";s:40:"Documentation/Images/general-options.png";s:4:"51e1";s:33:"Documentation/Images/metadata.png";s:4:"2bf8";s:42:"Documentation/Images/ruleset-usergroup.png";s:4:"a16c";s:33:"Documentation/Images/rulesets.png";s:4:"793e";s:39:"Documentation/Images/scheduler-task.png";s:4:"6f49";s:44:"Documentation/Images/screencast-jweiland.jpg";s:4:"1def";s:36:"Documentation/Introduction/Index.rst";s:4:"164c";s:37:"Documentation/KnownProblems/Index.rst";s:4:"fa77";s:32:"Documentation/ToDoList/Index.rst";s:4:"ac08";s:35:"Documentation/UsersManual/Index.rst";s:4:"50e4";s:40:"Resources/Private/Language/locallang.xlf";s:4:"6a72";s:53:"Resources/Private/Language/locallang_csh_flexform.xlf";s:4:"852f";s:63:"Resources/Private/Language/locallang_csh_tx_imageautoresize.xlf";s:4:"a0e9";s:44:"Resources/Private/Language/locallang_mod.xlf";s:4:"17b3";s:44:"Resources/Private/Language/locallang_tca.xlf";s:4:"d976";s:32:"Resources/Public/Css/twitter.css";s:4:"0663";s:35:"Resources/Public/Images/twitter.ico";s:4:"030d";s:36:"Resources/Public/JavaScript/popup.js";s:4:"3a29";s:13:"mod1/conf.php";s:4:"71df";s:14:"mod1/index.php";s:4:"a15f";s:18:"mod1/locallang.xlf";s:4:"a203";s:22:"mod1/locallang_mod.xlf";s:4:"e99e";s:22:"mod1/mod_template.html";s:4:"cdfa";}',
	'suggests' => array(
	),
);

?>