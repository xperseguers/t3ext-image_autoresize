<?php
namespace Causal\ImageAutoresize\Slots;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Xavier Perseguers <xavier@causal.ch>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Slot implementation to extend the list of actions in Extension Manager.
 *
 * @category    Slots
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ExtensionManager {

	/**
	 * Extends the list of actions for EXT:image_autoresize to link to
	 * the configuration module.
	 *
	 * @param array $extension
	 * @param array $actions
	 */
	public function processActions(array $extension, array &$actions) {
		if ($extension['key'] === 'image_autoresize') {
			$moduleUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('xMOD_tximageautoresize');

			$extensionName = 'extensionmanager';
			$titleKey = 'extensionList.configure';
			$title = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($titleKey, $extensionName);

			$icon = 'actions-system-extension-configure';
			$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon, array('title' => $title));

			// Configure action comes as first icon
			array_unshift($actions, '<a href="' . $moduleUrl . '">' . $icon . '</a>');
		}
	}

}
