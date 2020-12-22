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
use Klipper\Module\ProductBundle\Model\Traits\ProductableOptionalInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductCombinationableOptionalInterface;

/**
 * Repair item interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface RepairItemInterface extends
    IdInterface,
    OrganizationalRequiredInterface,
    ProductableOptionalInterface,
    ProductCombinationableOptionalInterface,
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
    public function setType(?string $type);

    public function getType(): ?string;

    /**
     * @return static
     */
    public function setPrice(?float $price);

    public function getPrice(): ?float;

    /**
     * @return static
     */
    public function setFinalPrice(?float $finalPrice);

    public function getFinalPrice(): ?float;

    /**
     * @return static
     */
    public function setInternalComment(?string $internalComment);

    public function getInternalComment(): ?string;

    /**
     * @return static
     */
    public function setPublicComment(?string $publicComment);

    public function getPublicComment(): ?string;
}
