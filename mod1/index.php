<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Xavier Perseguers <xavier@causal.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

$GLOBALS['LANG']->includeLLFile('EXT:image_autoresize/mod1/locallang.xml');
$GLOBALS['BE_USER']->modAccess($MCONF, 1);

/**
 * Configuration module based on FlexForms.
 *
 * @category    Module
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class tx_imageautoresize_module1 extends t3lib_SCbase {

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
	 * @var t3lib_TCEforms
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
	 * @return string HTML wizard
	 */
	public function main() {
		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo) ? TRUE : FALSE;

		if (t3lib_div::_GP('form_submitted')) {
			$this->processData();
		}

		if (!version_compare(TYPO3_version, '4.5.99', '>')) {
			// See bug http://forge.typo3.org/issues/31697
			$GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = 1;
		}
		$this->initTCEForms();
		if (version_compare(TYPO3_version, '6.1.0', '>=')) {
			$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		} else {
			$this->doc = t3lib_div::makeInstance('template');
		}
		$this->doc->setModuleTemplate(t3lib_extMgm::extPath($this->extKey) . 'mod1/mod_template.html');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$docHeaderButtons = $this->getButtons();

		if (($this->id && $access) || ($GLOBALS['BE_USER']->user['admin'] && !$this->id)) {
			$this->doc->form = '<form action="" method="post" name="' . $this->tceforms->formName . '">';

			// Render content:
			$this->moduleContent();
		} else {
			// If no access or if ID == zero
			$docHeaderButtons['save'] = '';
			$this->content .= $this->doc->spacer(10);
		}

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
			/** @var $flexObj t3lib_flexformtools */
			$flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
			$row['rulesets'] = $flexObj->flexArray2Xml($row['rulesets'], TRUE);
		}

		// TCE forms methods *must* be invoked before $this->doc->startPage()
		$wizard = $this->tceforms->printNeededJSFunctions_top();
		$wizard .= $this->buildForm($row);
		$wizard .= $this->tceforms->printNeededJSFunctions();

		$additionalConfigurationFileName = PATH_site . 'typo3conf/AdditionalConfiguration.php';
		if (version_compare(TYPO3_version, '6.0.0', '>=') && file_exists($additionalConfigurationFileName)) {
			// Compatibility code for people upgrading from version 1.3.0
			$backupConfig = $GLOBALS['TYPO3_CONF_VARS'];
			$GLOBALS['TYPO3_CONF_VARS'] = array();
			include($additionalConfigurationFileName);
			if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->expertKey])) {
				$this->content .= '
					<div id="typo3-messages">
						<div class="typo3-message message-error">
							<div class="message-body">
								Please remove configuration line $GLOBALS[\'TYPO3_CONF_VARS\'][\'EXT\'][\'extConf\'][\'image_autoresize_ff\'] from typo3conf/AdditionalConfiguration.php
							</div>
						</div>
					</div>';
			}
			$GLOBALS['TYPO3_CONF_VARS'] = $backupConfig;
		}

		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));
		$this->content .= $this->doc->spacer(5);
		$this->content .= $wizard;
		$this->content .= $this->doc->spacer(5);
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
			'save' => ''
		);

		// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);

		// SAVE button
		$buttons['save'] = '<input type="image" class="c-inputButton" name="submit" value="Update"' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/savedok.gif', '') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xml:saveConfiguration', TRUE) . '" />';

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
		include(t3lib_extMgm::extPath($this->extKey) . 'Configuration/TCA/Options.php');
		t3lib_extMgm::addLLrefForTCAdescr(self::virtualTable, 'EXT:' . $this->extKey . '/Resource/Private/Language/locallang_csh_' . self::virtualTable . '.xml');

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

		if (version_compare(TYPO3_version, '6.1.99', '>')) {
			$startFooter = strrpos($form, '<div class="typo3-TCEforms-recHeaderRow">');
			$endTag = '</div>';
		} else {
			$startFooter = strrpos($form, '<tr class="typo3-TCEforms-recHeaderRow">');
			$endTag = '</tr>';
		}

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
		$table = self::virtualTable;
		$id    = self::virtualRecordId;
		$field = 'rulesets';

		$inputData_tmp = t3lib_div::_GP('data');
		$data = $inputData_tmp[$table][$id];
		$newConfig = t3lib_div::array_merge_recursive_overrule($this->config, $data);

		// Action commands (sorting order and removals of FlexForm elements)
		$ffValue =& $data[$field];
		if ($ffValue) {
			$actionCMDs = t3lib_div::_GP('_ACTION_FLEX_FORMdata');
			if (is_array($actionCMDs[$table][$id][$field]['data']))	{
				/* @var $tce t3lib_TCEmain */
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				// Officially internal but not declared as such...
				$tce->_ACTION_FLEX_FORMdata($ffValue['data'], $actionCMDs[$table][$id][$field]['data']);
			}
			// Renumber all FlexForm temporary ids
			$this->persistFlexForm($ffValue['data']);

			// Keep order of FlexForm elements
			$newConfig[$field] = $ffValue;
		}

		// Write back configuration to localconf.php
		$localconfConfig = $newConfig;
		$localconfConfig['conversion_mapping'] = implode(',', t3lib_div::trimExplode("\n", $localconfConfig['conversion_mapping'], TRUE));

		if ($this->writeToLocalconf($this->expertKey, $localconfConfig)) {
			$this->config = $newConfig;
		}
	}

	/**
	 * Writes a configuration line to localconf.php (TYPO3 4.x) or AdditionalConfiguration.php (TYPO3 6.x).
	 * We don't use the <code>tx_install</code> methods as they add unneeded
	 * comments at the end of the file.
	 *
	 * @param string $key
	 * @param array $config
	 * @return boolean
	 */
	protected function writeToLocalconf($key, array $config) {
		if (version_compare(TYPO3_version, '6.0.0', '>=')) {
			/** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
			$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
			/** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
			$configurationManager = $objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
			return $configurationManager->setLocalConfigurationValueByPath('EXT/extConf/' . $key, serialize($config));
		}

		// ---------------------------------
		// PRE-TYPO3 6.0 code
		// ---------------------------------

		$key = '$TYPO3_CONF_VARS[\'EXT\'][\'extConf\'][\'' . $key . '\']';
		$value = '\'' . serialize($config) . '\'';
		$localconfFile = PATH_site . 'typo3conf/localconf.php';
		$marker = '## INSTALL SCRIPT EDIT POINT TOKEN';
		$format = "%s = %s;\t// Modified or inserted by TYPO3 Extension Manager.";

		$lines = explode(LF, file_get_contents($localconfFile));
		$insertPos = count($lines);
		$pos = 0;
		for ($i = count($lines) - 1; $i > 0 && !t3lib_div::isFirstPartOfStr($lines[$i], $marker); $i--) {
			// TODO: Compatibility check as closing PHP tag is no more part of the TYPO3 CGL
			if (t3lib_div::isFirstPartOfStr($lines[$i], '?>')) {
				$insertPos = $i;
			}
			if (t3lib_div::isFirstPartOfStr($lines[$i], $key)) {
				$pos = $i;
				break;
			}
		}
		if ($pos) {
			$lines[$pos] = sprintf($format, $key, $value);
		} else {
			$lines[$insertPos] = sprintf($format, $key, $value);
			// TODO: Compatibility check as closing PHP tag is no more part of the TYPO3 CGL
			$lines[] = '?>';
		}

		$result = t3lib_div::writeFile($localconfFile, implode("\n", $lines));
		if ($result) {
			t3lib_extMgm::removeCacheFiles();
		}
		return $result;
	}

	/**
	 * Initializes <code>t3lib_TCEform</code> class for use in this module.
	 *
	 * @return void
	 */
	protected function initTCEForms() {
		$this->tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
		$this->tceforms->initDefaultBEMode();
		$this->tceforms->formName = 'tsStyleConfigForm';
		$this->tceforms->backPath = $GLOBALS['BACK_PATH'];
		$this->tceforms->doSaveFieldName = 'doSave';
		$this->tceforms->localizationMode = '';
		$this->tceforms->palettesCollapsed = 0;
		$this->tceforms->disableRTE = 0;
		$this->tceforms->enableClickMenu = TRUE;
		$this->tceforms->enableTabMenu = TRUE;
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
		$extensions = t3lib_div::trimExplode(',', strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']), TRUE);
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

}

// Make instance:
/** @var $SOBE tx_imageautoresize_module1 */
$SOBE = t3lib_div::makeInstance('tx_imageautoresize_module1');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
