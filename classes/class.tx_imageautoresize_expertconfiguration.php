<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Xavier Perseguers (typo3@perseguers.ch)
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

//include(t3lib_extMgm::extPath('install') . 'mod/class.tx_install.php');

/**
 * This class provides a wizard used in EM to prepare an expert configuration
 * for this extension.
 *
 * @category    Wizard
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <typo3@perseguers.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class tx_imageautoresize_expertConfiguration {

	const virtualTable = 'tx_imageautoresize_expert';
	const virtualRecordId = 1;

	/**
	 * @var string
	 */
	protected $extKey = 'image_autoresize';

	/**
	 * @var array
	 */
	protected $expertKey = 'image_autoresize_expert';

	/**
	 * @var t3lib_TCEforms
	 */
	protected $tceforms;

	/**
	 * @var array
	 */
	protected $extConf;

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$this->initTCEForms();

		$this->extConf = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey];
		$config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->expertKey];
		$this->config = $config ? unserialize($config) : array();
	}

	/**
	 * Renders an expert wizard.
	 *
	 * @param array	Parameter array. Contains fieldName and fieldValue.
	 * @param t3lib_tsStyleConfig $pObj Parent object
	 * @return string HTML wizard
	 */
	public function expertWizard(array $params, t3lib_tsStyleConfig $pObj) {
		if (t3lib_div::_GP('expert_form_submitted')) {
			$this->processData();
		}

		//
		// page content
		//
		$row = $this->config;
		if ($row['rulesets']) {
			$flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
			/* @var $flexObj t3lib_flexformtools */
			$row['rulesets'] = $flexObj->flexArray2Xml($row['rulesets'], TRUE);	
		}

		if (!$this->extConf['expert']) {
			// TODO: do that better
			//$this->content .= '<div style="display:none;">';
		}
		$this->content .= $this->tceforms->printNeededJSFunctions_top();
		$this->content .= $this->buildForm($row);
		$this->content .= $this->tceforms->printNeededJSFunctions();
		$this->content .= '<input type="hidden" name="' . $params['fieldName'] . '" value="' . urlencode($params['fieldValue']) . '" />';
		if (!$this->extConf['expert']) {
			//$this->content .= '</div>';
		}

		return $this->content;
	}

	/**
	 * Builds the expert configuration form.
	 *
	 * @param array $row
	 * @return string
	 */
	protected function buildForm(array $row) {
		$content = '';

			// Load the configuration of virtual table 'tx_imageautoresize_expert' 
		global $TCA;
		include(t3lib_extMgm::extPath($this->extKey) . 'tca.php');
		t3lib_extMgm::addLLrefForTCAdescr(self::virtualTable, 'EXT:' . $this->extKey . '/locallang_csh_' . self::virtualTable . '.xml');

		$rec['uid'] = self::virtualRecordId;
		$rec['pid'] = 0;
		$rec = array_merge($rec, $row);

			// Setting variables in TCEforms object
		$this->tceforms->hiddenFieldList = '';

			// Create form
		$form = '';
		$form .= $this->tceforms->getMainFields(self::virtualTable, $rec);
		$form .= '<input type="hidden" name="expert_form_submitted" value="1" />';
		$form = $this->tceforms->wrapTotal($form, $rec, self::virtualTable);

			// Remove header and footer
		$form = preg_replace('/<h2>.*<\/h2>/', '', $form);
		$startFooter = strrpos($form, '<tr class="typo3-TCEforms-recHeaderRow">');
		$endFooter = strpos($form, '</tr>', $startFooter);
		$form = substr($form, 0, $startFooter) . substr($form, $endFooter + 5);

			// Combine it all:
		$content .= $form;
		return $content;
	}

	/**
	 * Processes submitted data and stores it to localconf.php.
	 */
	protected function processData() {
		$inputData_tmp = t3lib_div::_GP('data');
		$data = $inputData_tmp[self::virtualTable][self::virtualRecordId];

		$newConfig = t3lib_div::array_merge_recursive_overrule($this->config, $data);
			// Keep order of FlexForm elements
		if ($data['rulesets']) {
			$newConfig['rulesets'] = $data['rulesets'];
		}

			// Write back configuration to localconf.php. Don't use the tx_install methods
			// as they add unneeded comments at the end of the file
		$key = '$TYPO3_CONF_VARS[\'EXT\'][\'extConf\'][\'' . $this->expertKey . '\']';
		$value = '\'' . serialize($newConfig) . '\'';

		//$instObj = t3lib_div::makeInstance('tx_install');
		//$instObj->allowUpdateLocalConf = 1;
		//$instObj->updateIdentity = 'TYPO3 Core Update Manager';
		//$lines = $instObj->writeToLocalconf_control(); 
		//$instObj->setValueInLocalconfFile($lines, $key, $value, FALSE);
		//$result = $instObj->writeToLocalconf_control($lines);
		//if ($result !== 'nochange') {
		//	$this->config = $newConfig;
		//	t3lib_extMgm::removeCacheFiles();
		//}
		//$instObj = null;

		$localconfFile = PATH_site . 'typo3conf/localconf.php';
		$lines = explode("\n", file_get_contents($localconfFile));
		$marker = '## INSTALL SCRIPT EDIT POINT TOKEN';
		$format = "%s = %s;\t// Modified or inserted by TYPO3 Core Update Manager.";
		$insertPos = count($lines);
		$pos = 0;
		for ($i = count($lines) - 1; $i > 0 && !t3lib_div::isFirstPartOfStr($lines[$i], $marker); $i--) {
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
			$lines[] = '?>';
		}
		t3lib_div::writeFile($localconfFile, implode("\n", $lines));
		$this->config = $newConfig;
		t3lib_extMgm::removeCacheFiles();
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

			// Setting external variables:
		if ($GLOBALS['BE_USER']->uc['edit_showFieldHelp']!='text' && $this->MOD_SETTINGS['showDescriptions'])	$this->tceforms->edit_showFieldHelp='text';
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/image_autoresize/classes/class.tx_imageautoresize_expertconfiguration.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/image_autoresize/classes/class.tx_imageautoresize_expertconfiguration.php']);
}
?>