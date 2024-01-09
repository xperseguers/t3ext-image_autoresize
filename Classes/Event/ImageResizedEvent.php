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

namespace Causal\ImageAutoresize\Event;

final class ImageResizedEvent
{
    /**
     * @param string
     */
    private $operation;

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var int
     */
    private $newWidth;

    /**
     * @var int
     */
    private $newHeight;

    public function __construct(
        string $operation,
        string $source,
        string $destination,
        int $newWidth,
        int $newHeight
    )
    {
        $this->operation = $operation;
        $this->source = $source;
        $this->destination = $destination;
        $this->newWidth = $newWidth;
        $this->newHeight = $newHeight;
    }

    /**
     * @return string
     */
    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @return int
     */
    public function getNewWidth(): int
    {
        return $this->newWidth;
    }

    /**
     * @param int $newWidth
     * @return $this
     */
    public function setNewWidth(int $newWidth): self
    {
        $this->newWidth = $newWidth;
        return $this;
    }

    /**
     * @return int
     */
    public function getNewHeight(): int
    {
        return $this->newHeight;
    }

    /**
     * @param int $newHeight
     * @return $this
     */
    public function setNewHeight(int $newHeight): self
    {
        $this->newHeight = $newHeight;
        return $this;
    }
}
