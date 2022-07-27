<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\RepairBundle\Model\Traits;

use Doctrine\Common\Collections\Collection;
use Klipper\Module\RepairBundle\Model\RepairInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface DeviceRepairableInterface
{
    /**
     * @return static
     */
    public function setLastRepair(?RepairInterface $lastRepair);

    public function getLastRepair(): ?RepairInterface;

    /**
     * @return null|int|string
     */
    public function getLastRepairId();

    /**
     * @return static
     */
    public function setWarrantyEndDate(?\DateTimeInterface $warrantyEndDate);

    public function getWarrantyEndDate(): ?\DateTimeInterface;

    /**
     * @return RepairInterface[]
     */
    public function getRepairs(): Collection;
}
