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

	/**
	 * @var string
	 */
	protected $extKey = 'image_autoresize';

	/**
	 * @var t3lib_TCEforms
	 */
	protected $tceforms;

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$this->initTCEForms();
	}

	/**
	 * Renders an expert wizard.
	 *
	 * @param array	Parameter array. Contains fieldName and fieldValue.
	 * @param t3lib_tsStyleConfig $pObj Parent object
	 * @return string HTML wizard
	 */
	public function expertWizard(array $params, t3lib_tsStyleConfig $pObj) {
		// Horrible hack to have tceforms work
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] .= '" onsubmit="return TBE_EDITOR.checkSubmit(1);'; 
		//tsStyleConfigForm
		//t3lib_div::debug(t3lib_div::_GP('expertWizardSave'), 'expert');
		//$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$this->extKey . '_expert'] = 'pi_flexform';
		//t3lib_extMgm::addPiFlexFormValue($this->extKey . '_expert', 'FILE:EXT:' . $this->extKey . '/flexform.xml');
		if (t3lib_div::_GP('expert_form_submitted')) {
			$this->processData();
		}

		//
		// page content
		//
		$this->content .= $this->tceforms->printNeededJSFunctions_top();
		$this->content .= $this->buildForm();
		$this->content .= $this->tceforms->printNeededJSFunctions();

		return $this->content;
	}

	/**
	 * Builds the expert configuration form.
	 *
	 * @return string
	 */
	protected function buildForm() {
		$content = '';

			// Load the configuration of virtual table 'tx_imageautoresize_expert' 
		global $TCA;
		include(t3lib_extMgm::extPath($this->extKey) . 'tca.php');

		$trData = t3lib_div::makeInstance('t3lib_transferData');
		$trData->addRawData = TRUE;
		$trData->lockRecords = 0;
		$trData->defVals = t3lib_div::_GP('defVals');
		$trData->disableRTE = 0;
		//$trData->prevPageID = $prevPageID;
		//$trData->fetchRecord($table, $this->workspaceId, '');
		reset($trData->regTableItems_data);
		$rec = current($trData->regTableItems_data);
		$rec = array(
			'uid' => 0,
			'title' => 'test',
		);

			// Setting variables in TCEforms object
		$this->tceforms->hiddenFieldList = '';

		$table = 'tx_imageautoresize_expert';

			// Create form
		$form = '';
		$form .= $this->tceforms->getMainFields($table, $rec);
		$form .= '<input type="hidden" name="expert_form_submitted" value="1" />';
		$form = $this->tceforms->wrapTotal($form, $rec, $table);

			// Remove header and footer
		$form = preg_replace('/<h2>.*<\/h2>/', '', $form);
		$form = preg_replace('/<span class="typo3-TCEforms-recUid">.*<\/span>/', '', $form);

			// Combine it all:
		$content .= $form;
		return $content;
	}

	/**
	 * Processes submitted data.
	 *
	 * @return	void
	 */
	protected function processData() {
		$inputData_tmp = t3lib_div::_GP('data');
		t3lib_div::debug($inputData_tmp, 'data');
	}

	/**
	 * Initializes <code>t3lib_TCEform</code> class for use in this module.
	 *
	 * @return void
	 */
	protected function initTCEForms() {
		$this->tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
		$this->tceforms->initDefaultBEMode();
		$this->tceforms->backPath = $GLOBALS['BACK_PATH'];
		$this->tceforms->doSaveFieldName = 'doSave';
		//$this->tceforms->localizationMode = t3lib_div::inList('text,media', $this->localizationMode) ? $this->localizationMode : '';	// text,media is keywords defined in TYPO3 Core API..., see "l10n_cat"
		//$this->tceforms->returnUrl = $this->R_URI;
		$this->tceforms->palettesCollapsed = 1;
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