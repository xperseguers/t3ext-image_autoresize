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

namespace Causal\ImageAutoresize\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class FormFlexAjaxController extends \TYPO3\CMS\Backend\Controller\FormFlexAjaxController
{

    /**
     * Render a single flex form section container to add it to the DOM
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function containerAdd(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $GLOBALS['TCA']['tx_imageautoresize'] = include(ExtensionManagementUtility::extPath('image_autoresize') . 'Configuration/TCA/Module/Options.php');
        $GLOBALS['TCA']['tx_imageautoresize']['ajax'] = true;

        $response = parent::containerAdd($request, $response);
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

}
