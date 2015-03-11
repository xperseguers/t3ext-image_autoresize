<?php
namespace Causal\ImageAutoresize\Slots;

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
			$configureAction = sprintf('<a class="btn btn-default" title="%s" href="%s">%s</a>', htmlspecialchars($title), htmlspecialchars($moduleUrl), $icon);
			if (version_compare(TYPO3_version, '6.99.99', '<=')) {
				array_unshift($actions, $configureAction);
			} else {
				$actions[0] = $configureAction;
			}

			$title = htmlspecialchars($extension['title']);
			$titleAction = htmlspecialchars($moduleUrl);
			$actions[] = "<script type=\"text/javascript\">
				var titleCell = document.getElementById('image_autoresize').getElementsByTagName('td')[2];
				titleCell.innerHTML = titleCell.innerHTML.replace(/$title\\s*$/, '<a href=\"$titleAction\">$title</a>');
			</script>";
		}
	}

}
