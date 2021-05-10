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

use Klipper\Component\DoctrineChoice\Model\ChoiceInterface;
use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Component\Model\Traits\UserTrackableInterface;
use Klipper\Module\CarrierBundle\Model\ShippingInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;

/**
 * Repair history interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface RepairHistoryInterface extends
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
    public function setPublic(bool $public);

    public function isPublic(): bool;

    /**
     * @return static
     */
    public function setSwap(bool $swap);

    public function isSwap(): bool;

    /**
     * @return static
     */
    public function setPreviousDevice(?DeviceInterface $previousDevice);

    public function getPreviousDevice(): ?DeviceInterface;

    /**
     * @return static
     */
    public function setNewDevice(?DeviceInterface $previousDevice);

    public function getNewDevice(): ?DeviceInterface;

    /**
     * @return static
     */
    public function setPreviousStatus(?ChoiceInterface $previousStatus);

    public function getPreviousStatus(): ?ChoiceInterface;

    /**
     * @return static
     */
    public function setNewStatus(?ChoiceInterface $newStatus);

    public function getNewStatus(): ?ChoiceInterface;

    /**
     * @return static
     */
    public function setShipping(?ShippingInterface $shipping);

    public function getShipping(): ?ShippingInterface;
}
