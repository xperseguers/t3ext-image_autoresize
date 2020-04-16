<?php
declare(strict_types = 1);

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

use TYPO3\CMS\Core\Resource\Event\SanitizeFileNameEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileReplacedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFileAddedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;

class CoreResourceStorageEventListener
{

    /**
     * Sanitizes the file name.
     *
     * @param SanitizeFileNameEvent $event
     */
    public function sanitizeFileName(SanitizeFileNameEvent $event): void
    {
        // TODO
    }

    /**
     * A file has been added as a *replacement* of an existing one.
     *
     * @param AfterFileReplacedEvent $event
     */
    public function afterFileReplaced(AfterFileReplacedEvent $event): void
    {
        // TODO
    }

    /**
     * Auto-resizes a given source file (possibly converting it as well).
     *
     * @param BeforeFileAddedEvent $event
     */
    public function beforeFileAdded(BeforeFileAddedEvent $event): void
    {
        // TODO
    }

    /**
     * Populates the FAL metadata of the resized image.
     *
     * @param AfterFileAddedEvent $event
     */
    public function populateMetadata(AfterFileAddedEvent $event): void
    {
        // TODO
    }

}