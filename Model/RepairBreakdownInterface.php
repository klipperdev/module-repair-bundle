<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\RepairBundle\Model;

use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Component\Model\Traits\UserTrackableInterface;

/**
 * Repair breakdown interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface RepairBreakdownInterface extends
    IdInterface,
    OrganizationalRequiredInterface,
    TimestampableInterface,
    UserTrackableInterface
{
    /**
     * @return static
     */
    public function setRepair(?RepairInterface $repair);

    public function getRepair(): ?RepairInterface;

    /**
     * @return null|int|string
     */
    public function getRepairId();

    /**
     * @return static
     */
    public function setBreakdown(?BreakdownInterface $breakdown);

    public function getBreakdown(): ?BreakdownInterface;

    /**
     * @return static
     */
    public function setRepairImpossible(?bool $repairImpossible);

    public function isRepairImpossible(): bool;

    public function isRepairImpossibleInitialized(): bool;
}
