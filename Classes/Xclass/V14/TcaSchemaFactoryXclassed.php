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

use TYPO3\CMS\Core\Schema\SchemaCollection;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca;

class TcaSchemaFactoryXclassed extends TcaSchemaFactory
{
    private const VIRTUAL_TABLE = 'tx_imageautoresize';

    /**
     * Returns all main schemata. The virtual "tx_imageautoresize" table has no database
     * table, so it is hidden from everything except FormEngine, which needs it to render
     * the configuration module.
     *
     * @return SchemaCollection<string, TcaSchema>
     */
    public function all(): SchemaCollection
    {
        $callerClass = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? '';
        if (is_a($callerClass, InitializeProcessedTca::class, true)) {
            return $this->schemata;
        }

        $schemata = iterator_to_array($this->schemata);
        unset($schemata[self::VIRTUAL_TABLE]);
        return new SchemaCollection($schemata);
    }
}
