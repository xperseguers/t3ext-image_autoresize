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

namespace Causal\ImageAutoresize\Slots;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Slot implementation to extend the list of actions in Extension Manager.
 *
 * @category    Slots
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ExtensionManager
{

    const SIGNAL_ProcessActions = 'processActions';

    /**
     * Extends the list of actions for EXT:image_autoresize to link to
     * the configuration module.
     *
     * @param array $extension
     * @param array $actions
     */
    public function processActions(array $extension, array &$actions)
    {
        if ($extension['key'] === 'image_autoresize') {
            $moduleUrl = BackendUtility::getModuleUrl('xMOD_tximageautoresize');

            $extensionName = 'extensionmanager';
            $titleKey = 'extensionList.configure';
            $title = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($titleKey, $extensionName);

            $icon = 'actions-system-extension-configure';
            /** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory */
            $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
            $icon = (string)$iconFactory->getIcon($icon, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL);

            // Configure action comes as first icon
            $configureAction = sprintf('<a class="btn btn-default" title="%s" href="%s">%s</a>', htmlspecialchars($title), htmlspecialchars($moduleUrl), $icon);
            $actions[0] = $configureAction;
            unset($actions[1], $actions[2], $actions[3], $actions[4]);

            $title = htmlspecialchars($extension['title']);
            $titleAction = htmlspecialchars($moduleUrl);
            $pattern = "/>$title</";
            $replacement = "'><a href=\"$titleAction\">$title</a><'";
            $actions[] = "<script type=\"text/javascript\">
                var titleCell = document.getElementById('image_autoresize').getElementsByTagName('td')[2];
                titleCell.innerHTML = titleCell.innerHTML.replace($pattern, $replacement);
            </script>";
        }
    }

}
