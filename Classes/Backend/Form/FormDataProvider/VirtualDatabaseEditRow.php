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

namespace Causal\ImageAutoresize\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Form\FormDataProvider\AbstractDatabaseRecordProvider;

/**
 * Virtual form data provider.
 *
 * @category    Form\FormDataProvider
 * @package     TYPO3
 * @subpackage  tx_imageautoresize
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
class VirtualDatabaseEditRow extends AbstractDatabaseRecordProvider implements FormDataProviderInterface
{

    /**
     * @var array
     */
    static $row;

    /**
     * Initializes the virtual configuration.
     *
     * @param array $row
     */
    public static function initialize(array $row)
    {
        static::$row = $row;
    }

    /**
     * Injects the virtual configuration.
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        $result['databaseRow'] = static::$row;
        return $result;
    }

}
