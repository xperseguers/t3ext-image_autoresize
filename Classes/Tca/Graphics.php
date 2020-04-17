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

namespace Causal\ImageAutoresize\Tca;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TCA Helper class.
 *
 * @category    TCA
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
class Graphics
{

    /**
     * Prepares a list of image file extensions supported by the
     * current TYPO3 install.
     *
     * @param array $settings content element configuration
     */
    public function getImageFileExtensions(array $settings): void
    {
        $languageService = $this->getLanguageService();

        $extensions = GeneralUtility::trimExplode(',', strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']), true);
        // We don't consider PDF being an image...
        if ($key = array_search('pdf', $extensions)) {
            unset($extensions[$key]);
        }
        // ... neither SVG since vectorial
        if ($key = array_search('svg', $extensions)) {
            unset($extensions[$key]);
        }
        asort($extensions);

        $elements = [];
        foreach ($extensions as $extension) {
            $label = $languageService->sL('LLL:EXT:image_autoresize/Resources/Private/Language/locallang.xlf:extension.' . $extension);
            $label = $label ? $label : '.' . $extension;
            $elements[] = [$label, $extension];
        }

        $settings['items'] = array_merge($settings['items'], $elements);
    }

    /**
     * Returns the language service.
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

}
