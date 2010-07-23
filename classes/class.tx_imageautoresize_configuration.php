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
 * This class provides a wizard used in EM to prepare a configuration
 * based on FlexForms for this extension.
 *
 * @category    Wizard
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <typo3@perseguers.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 * @version     SVN: $Id$
 */
class tx_imageautoresize_configuration {

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
	 * @var string
	 */
	protected $content;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$this->initTCEForms();

		$config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->expertKey];
		$this->config = $config ? unserialize($config) : $this->getDefaultConfiguration();
	}

	/**
	 * Renders a FlexForm configuration form.
	 *
	 * @param array	Parameter array. Contains fieldName and fieldValue.
	 * @param t3lib_tsStyleConfig $pObj Parent object
	 * @return string HTML wizard
	 */
	public function display(array $params, t3lib_tsStyleConfig $pObj) {
		if (t3lib_div::_GP('form_submitted')) {
			$this->processData();
		}

		$row = $this->config;
		if ($row['rulesets']) {
			$flexObj = t3lib_div::makeInstance('t3lib_flexformtools');
			/* @var $flexObj t3lib_flexformtools */
			$row['rulesets'] = $flexObj->flexArray2Xml($row['rulesets'], TRUE);	
		}

		$this->content .= $this->tceforms->printNeededJSFunctions_top();
		$this->content .= $this->buildForm($row);
		$this->content .= $this->tceforms->printNeededJSFunctions();

		return $this->content;
	}

	/**
	 * Returns the default configuration.
	 *
	 * @return array
	 */
	protected function getDefaultConfiguration() {
		return array(
			'directories' => 'fileadmin/',
			'file_types'  => 'jpg,jpeg,png,tif,tiff',
			'threshold'   => '400K',
			'max_width'   => '1024',
			'max_height'  => '768',
			'auto_orient' => '1',
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
		$form .= '<input type="hidden" name="form_submitted" value="1" />';
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
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				/* @var $tce t3lib_TCEmain */
				// Officially internal but not declared as such... 
				$tce->_ACTION_FLEX_FORMdata($ffValue['data'], $actionCMDs[$table][$id][$field]['data']);
			}
				// Renumber all FlexForm temporary ids
			$this->persistFlexForm($ffValue['data']);

				// Keep order of FlexForm elements
			$newConfig[$field] = $ffValue;
		}

			// Write back configuration to localconf.php.
		$key = '$TYPO3_CONF_VARS[\'EXT\'][\'extConf\'][\'' . $this->expertKey . '\']';
		$value = '\'' . serialize($newConfig) . '\'';

		if ($this->writeToLocalconf($key, $value)) {
			$this->config = $newConfig;
			t3lib_extMgm::removeCacheFiles();	
		}
	}

	/**
	 * Writes a configuration line to localconf.php.
	 * We don't use the <code>tx_install</code> methods as they add unneeded
	 * comments at the end of the file.
	 *
	 * @param string $key
	 * @param string $value
	 * @return boolean
	 */
	protected function writeToLocalconf($key, $value) {
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

		return t3lib_div::writeFile($localconfFile, implode("\n", $lines));
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
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/image_autoresize/classes/class.tx_imageautoresize_expertconfiguration.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/image_autoresize/classes/class.tx_imageautoresize_expertconfiguration.php']);
}
?>