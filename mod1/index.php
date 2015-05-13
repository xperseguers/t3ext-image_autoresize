<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$GLOBALS['LANG']->includeLLFile('EXT:image_autoresize/mod1/locallang.xml');
if (!$GLOBALS['BE_USER']->isAdmin()) {
	throw new \RuntimeException('Access Error: You don\'t have access to this module.', 1294586448);
}

/**
 * Configuration module based on FlexForms.
 *
 * @category    Module
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class tx_imageautoresize_module1 extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	const virtualTable    = 'tx_imageautoresize';
	const virtualRecordId = 1;

	/**
	 * @var string
	 */
	protected $extKey = 'image_autoresize';

	/**
	 * @var array
	 */
	protected $expertKey = 'image_autoresize_ff';

	/**
	 * @var \TYPO3\CMS\Backend\Form\FormEngine
	 */
	protected $tceforms;

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->expertKey];
		$this->config = $config ? unserialize($config) : $this->getDefaultConfiguration();
		$this->config['conversion_mapping'] = implode(LF, explode(',', $this->config['conversion_mapping']));
	}

	/**
	 * Renders a FlexForm configuration form.
	 *
	 * @return void
	 */
	public function main() {
		if (GeneralUtility::_GP('form_submitted')) {
			$this->processData();
		}

		$this->initTCEForms();
		$this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->setModuleTemplate(ExtensionManagementUtility::extPath($this->extKey) . 'mod1/mod_template.html');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$docHeaderButtons = $this->getButtons();

		$this->doc->form = '<form action="" method="post" name="editform">';

		// Render content:
		$this->moduleContent();

		// Compile document
		$markers['FUNC_MENU'] = '';
		$markers['CONTENT'] = $this->content;
		$this->content = '';

		// Build the <body> for the module
		$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Generates the module content.
	 *
	 * @return void
	 */
	protected function moduleContent() {
		$row = $this->config;
		if ($row['rulesets']) {
			/** @var $flexObj \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools */
			$flexObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
			$row['rulesets'] = $flexObj->flexArray2Xml($row['rulesets'], TRUE);
		}

		// TCE forms methods *must* be invoked before $this->doc->startPage()
		$wizard = $this->tceforms->printNeededJSFunctions_top();
		$wizard .= $this->buildForm($row);
		$wizard .= $this->tceforms->printNeededJSFunctions();

		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
		$this->addStatisticsAndSocialLink();
		$this->content .= $this->doc->spacer(5);
		$this->content .= $wizard;
	}

	/**
	 * Creates the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc.
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'shortcut' => '',
			'close' => '',
			'save' => '',
			'save_close' => '',
		);

		// CSH
		$buttons['csh'] = BackendUtility::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);

		// CLOSE button
		if (version_compare(TYPO3_version, '6.99.99', '<=')) {
			$closeLink = IconUtility::getSpriteIcon('actions-document-close', array('html' => '<input type="image" name="_close" class="c-inputButton" src="clear.gif" title="' . $GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:closeConfiguration', TRUE) . '" />'));
		} else {
			$closeUrl = BackendUtility::getModuleUrl('tools_ExtensionmanagerExtensionmanager');
			$closeLink = '<a href="#" onclick="document.location=\'' . htmlspecialchars($closeUrl) . '\'" title="' . $GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:closeConfiguration', TRUE) . '">' .
					IconUtility::getSpriteIcon('actions-document-close') .
				'</a>';
		}
		$buttons['close'] = $closeLink;

		// SAVE button
		$buttons['save'] = IconUtility::getSpriteIcon('actions-document-save', array('html' => '<input type="image" name="_savedok" class="c-inputButton" src="clear.gif" title="' . $GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:saveConfiguration', TRUE) . '" />'));

		// SAVE_CLOSE button
		$buttons['save_close'] = IconUtility::getSpriteIcon('actions-document-save-close', array('html' => '<input type="image" name="_saveandclosedok" class="c-inputButton" src="clear.gif" title="' . $GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:saveCloseConfiguration', TRUE) . '" />'));

		// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}

		return $buttons;
	}

	/**
	 * Prints out the module HTML.
	 *
	 * @return string HTML output
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Returns the default configuration.
	 *
	 * @return array
	 */
	protected function getDefaultConfiguration() {
		return array(
			'directories' => 'fileadmin/,uploads/',
			'file_types'  => 'jpg,jpeg,png',
			'threshold'   => '400K',
			'max_width'   => '1024',
			'max_height'  => '768',
			'auto_orient' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] === 'gm' ? '0' : '1',
			'conversion_mapping' => implode(',', array(
				'ai => jpg',
				'bmp => jpg',
				'pcx => jpg',
				'tga => jpg',
				'tif => jpg',
				'tiff => jpg',
			)),
		);
	}

	/**
	 * Builds the expert configuration form.
	 *
	 * @param array $row
	 * @return string
	 */
	protected function buildForm(array $row) {
		$content = '';

		// Load the configuration of virtual table 'tx_imageautoresize'
		global $TCA;
		include(ExtensionManagementUtility::extPath($this->extKey) . 'Configuration/TCA/Module/Options.php');
		ExtensionManagementUtility::addLLrefForTCAdescr(self::virtualTable, 'EXT:' . $this->extKey . '/Resource/Private/Language/locallang_csh_' . self::virtualTable . '.xml');

		$rec['uid'] = self::virtualRecordId;
		$rec['pid'] = 0;
		$rec = array_merge($rec, $row);

		// Setting variables in TCEforms object
		$this->tceforms->hiddenFieldList = '';

		// Create form
		$form = '';
		$form .= $this->tceforms->getMainFields(self::virtualTable, $rec);
		$form .= '<input type="hidden" name="form_submitted" value="1" />';
		$form = $this->tceforms->wrapTotal($form, $rec, self::virtualTable);

		// Remove header and footer
		$form = preg_replace('/<h[12]>.*<\/h[12]>/', '', $form);

		$startFooter = strrpos($form, '<div class="typo3-TCEforms-recHeaderRow">');
		$endTag = '</div>';

		if ($startFooter !== FALSE) {
			$endFooter = strpos($form, $endTag, $startFooter);
			$form = substr($form, 0, $startFooter) . substr($form, $endFooter + strlen($endTag));
		}

		// Combine it all:
		$content .= $form;
		return $content;
	}

	/**
	 * Processes submitted data and stores it to localconf.php.
	 *
	 * @return void
	 */
	protected function processData() {
		$closeUrl = BackendUtility::getModuleUrl('tools_ExtensionmanagerExtensionmanager');

		$close = GeneralUtility::_GP('_close_x');
		$saveCloseDok = GeneralUtility::_GP('_saveandclosedok_x');

		if ($close) {
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($closeUrl);
		}

		$table = self::virtualTable;
		$id    = self::virtualRecordId;
		$field = 'rulesets';

		$inputData_tmp = GeneralUtility::_GP('data');
		$data = $inputData_tmp[$table][$id];
		$newConfig = $this->config;
		\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($newConfig, $data);

		// Action commands (sorting order and removals of FlexForm elements)
		$ffValue = &$data[$field];
		if ($ffValue) {
			$actionCMDs = GeneralUtility::_GP('_ACTION_FLEX_FORMdata');
			if (is_array($actionCMDs[$table][$id][$field]['data']))	{
				$dataHandler = new CustomDataHandler();
				$dataHandler->_ACTION_FLEX_FORMdata($ffValue['data'], $actionCMDs[$table][$id][$field]['data']);
			}
			// Renumber all FlexForm temporary ids
			$this->persistFlexForm($ffValue['data']);

			// Keep order of FlexForm elements
			$newConfig[$field] = $ffValue;
		}

		// Write back configuration to localconf.php
		$localconfConfig = $newConfig;
		$localconfConfig['conversion_mapping'] = implode(',', GeneralUtility::trimExplode(LF, $localconfConfig['conversion_mapping'], TRUE));

		if ($this->writeToLocalconf($this->expertKey, $localconfConfig)) {
			$this->config = $newConfig;
		}

		if ($saveCloseDok) {
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($closeUrl);
		}
	}

	/**
	 * Writes a configuration line to AdditionalConfiguration.php.
	 * We don't use the <code>tx_install</code> methods as they add unneeded
	 * comments at the end of the file.
	 *
	 * @param string $key
	 * @param array $config
	 * @return boolean
	 */
	protected function writeToLocalconf($key, array $config) {
		/** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
		$configurationManager = $objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		return $configurationManager->setLocalConfigurationValueByPath('EXT/extConf/' . $key, serialize($config));
	}

	/**
	 * Initializes <code>\TYPO3\CMS\Backend\Form\FormEngine</code> class for use in this module.
	 *
	 * @return void
	 */
	protected function initTCEForms() {
		$this->tceforms = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormEngine');
		if (version_compare(TYPO3_version, '7.2.99', '<=')) {
			$this->tceforms->initDefaultBEMode();
		}
		$this->tceforms->doSaveFieldName = 'doSave';
		$this->tceforms->localizationMode = '';
		$this->tceforms->palettesCollapsed = 0;
		if (version_compare(TYPO3_version, '6.99.99', '<=')) {
			$this->tceforms->enableTabMenu = TRUE;
		}
	}

	/**
	 * Persists FlexForm items by removing 'ID-' in front of new
	 * items.
	 *
	 * @param array &$valueArray: by reference
	 * @return void
	 */
	protected function persistFlexForm(array &$valueArray) {
		foreach ($valueArray as $key => $value) {
			if ($key === 'el') {
				foreach ($value as $idx => $v) {
					if ($v && substr($idx, 0, 3) === 'ID-') {
						$valueArray[$key][substr($idx, 3)] = $v;
						unset($valueArray[$key][$idx]);
					}
				}
			} elseif (isset($valueArray[$key])) {
				$this->persistFlexForm($valueArray[$key]);
			}
		}
	}

	/**
	 * Prepares a list of image file extensions supported by the current
	 * TYPO3 install.
	 * Used in tca and FlexForm for the list of file types.
	 *
	 * @param array $settings content element configuration
	 * @return array content element configuration with dynamically added items
	 */
	public function getImageFileExtensions(array $settings) {
		$extensions = GeneralUtility::trimExplode(',', strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']), TRUE);
		// We don't consider PDF being an image
		if ($key = array_search('pdf', $extensions)) {
			unset($extensions[$key]);
		}
		asort($extensions);

		$elements = array();
		foreach ($extensions as $extension) {
			$label = $GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:extension.' . $extension);
			$label = $label ? $label : '.' . $extension;
			$elements[] = array($label, $extension);
		}

		$settings['items'] = array_merge($settings['items'], $elements);
	}

	/**
	 * Returns some statistics and a social link to Twitter.
	 *
	 * @return void
	 */
	protected function addStatisticsAndSocialLink() {
		$fileName = PATH_site . 'typo3conf/.tx_imageautoresize';

		if (!is_file($fileName)) {
			return;
		}

		$data = json_decode(file_get_contents($fileName), TRUE);
		if (!is_array($data) || !(isset($data['images']) && isset($data['bytes']))) {
			return;
		}

		$resourcesPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($this->extKey) . 'Resources/Public/';
		$this->doc->getPageRenderer()->addCssFile($resourcesPath . 'Css/twitter.css');
		$this->doc->getPageRenderer()->addJsFile($resourcesPath . 'JavaScript/popup.js');

		$totalSpaceClaimed = GeneralUtility::formatSize((int)$data['bytes']);
		$messagePattern = $GLOBALS['LANG']->getLL('storage.claimed');
		$message = sprintf($messagePattern, $totalSpaceClaimed, (int)$data['images']);

		$flashMessage = htmlspecialchars($message);

		$twitterMessagePattern = $GLOBALS['LANG']->getLL('social.twitter');
		$message = sprintf($twitterMessagePattern, $totalSpaceClaimed);
		$url = 'http://typo3.org/extensions/repository/view/image_autoresize';

		$twitterLink = 'https://twitter.com/intent/tweet?text=' . urlencode($message) . '&url=' . urlencode($url);
		$twitterLink = GeneralUtility::quoteJSvalue($twitterLink);
		$flashMessage .= '
			<div class="custom-tweet-button">
				<a href="#" onclick="popitup(' . $twitterLink . ',\'twitter\')" title="' . $GLOBALS['LANG']->getLL('social.share', TRUE) . '">
					<i class="btn-icon"></i>
					<span class="btn-text">Tweet</span>
				</a>
			</div>';

		$this->content .= '
			<div id="typo3-messages">
				<div class="typo3-message message-information">
					<div class="message-body">
						' . $flashMessage . '
					</div>
				</div>
			</div>';
	}

}

// ReflectionMethod does not work properly with arguments passed as reference thus
// using a trick here
class CustomDataHandler extends \TYPO3\CMS\Core\DataHandling\DataHandler {

	/**
	 * Actions for flex form element (move, delete)
	 * allows to remove and move flexform sections
	 *
	 * @param array &$valueArray by reference
	 * @param array $actionCMDs
	 * @return void
	 */
	public function _ACTION_FLEX_FORMdata(&$valueArray, $actionCMDs) {
		parent::_ACTION_FLEX_FORMdata($valueArray, $actionCMDs);
	}

}

// Make instance:
/** @var $SOBE tx_imageautoresize_module1 */
$SOBE = GeneralUtility::makeInstance('tx_imageautoresize_module1');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
