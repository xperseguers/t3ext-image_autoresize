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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\SchemaCollection;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

class TcaSchemaFactoryXclassed extends TcaSchemaFactory
{
    /**
     * Returns all main schemata
     *
     * @return SchemaCollection<string, TcaSchema>
     */
    public function all(): SchemaCollection
    {
        $callStack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $callerClass = $callStack[1]['class'] ?? null;

        if ($callerClass !== DataHandler::class) {
            // If the caller is not DataHandler, we can safely return the schemata as they are.
             return $this->schemata;
        }

        $items = [];
        foreach ($this->schemata as $name => $schema) {
            // Never ever return "tx_imageautoresize" virtual table
            if ($name === 'tx_imageautoresize') {
                continue;
            }
            $items[$name] = $schema;
        }

        return new SchemaCollection($items);
    }
}
