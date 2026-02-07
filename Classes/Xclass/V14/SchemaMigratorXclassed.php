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

namespace Causal\ImageAutoresize\Xclass\V14;

use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;

class SchemaMigratorXclassed extends SchemaMigrator
{
    protected function parseCreateTableStatements(array $statements): array
    {
        $tables = parent::parseCreateTableStatements($statements);

        // Never ever try to create the "tx_imageautoresize" table, as it is a virtual table
        // and not really needed in the database.
        unset($tables['tx_imageautoresize']);

        return $tables;
    }
}
