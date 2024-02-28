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

namespace Causal\ImageAutoresize\Tca;

use TYPO3\CMS\Core\Database\ConnectionPool;
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
class BackendUserGroups
{
    /**
     * @param array $settings content element configuration
     */
    public function getAll(array $settings): void
    {
        $userGroups = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_groups')
            ->select('uid', 'title')
            ->from('be_groups')
            ->orderBy('title', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $elements = [];
        foreach ($userGroups as $userGroup) {
            $elements[] = [$userGroup['title'], $userGroup['uid']];
        }

        $settings['items'] = array_merge($settings['items'], $elements);
    }
}
