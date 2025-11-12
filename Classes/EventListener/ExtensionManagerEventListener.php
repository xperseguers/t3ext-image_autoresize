<?php
declare(strict_types=1);

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

namespace Causal\ImageAutoresize\EventListener;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Event\AvailableActionsForExtensionEvent;

class ExtensionManagerEventListener
{

    /**
     * Extends the list of actions for EXT:image_autoresize to link to
     * the configuration module.
     *
     * @param AvailableActionsForExtensionEvent $event
     */
    public function processActions(AvailableActionsForExtensionEvent $event): void
    {
        if ($event->getPackageKey() !== 'image_autoresize')
            return;

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $moduleUrl = (string)$uriBuilder->buildUriFromRoute('xMOD_tximageautoresize');
        $title = 'Configure';   // TODO: make translatable

        $icon = 'actions-system-extension-configure';
        /** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
        $typo3Version = (new Typo3Version())->getMajorVersion();
        $iconSize = $typo3Version >= 13
            ? \TYPO3\CMS\Core\Imaging\IconSize::SMALL
            : \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL;
        $icon = (string)$iconFactory->getIcon($icon, $iconSize);

        $actions = $event->getActions();

        // Configure action comes as first icon
        $configureAction = sprintf('<a class="btn btn-default" title="%s" href="%s">%s</a>', htmlspecialchars($title), htmlspecialchars($moduleUrl), $icon);
        $actions[0] = $configureAction;
        unset($actions[1], $actions[2], $actions[3], $actions[4]);

        if ((new Typo3Version())->getMajorVersion() < 12) {
            // Starting from TYPO3 v12, we do not expect the extension title to be a link
            // and this prevents a possible CSP violation in the Backend
            $title = htmlspecialchars($event->getPackageData()['title']);
            $titleAction = htmlspecialchars($moduleUrl);
            $pattern = "/>$title</";
            $replacement = "'><a href=\"$titleAction\">$title</a><'";
            $actions[] = "<script type=\"text/javascript\">
                var titleCell = document.getElementById('image_autoresize').getElementsByTagName('td')[2];
                titleCell.innerHTML = titleCell.innerHTML.replace($pattern, $replacement);
            </script>";
        }

        $event->setActions($actions);
    }

}
