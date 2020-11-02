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
use Klipper\Component\Model\Traits\CurrencyableInterface;
use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Component\Model\Traits\UserTrackableInterface;
use Klipper\Component\Security\Model\UserInterface;
use Klipper\Module\CarrierBundle\Model\ShippingInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableOptionalInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductableOptionalInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductCombinationableOptionalInterface;
use Klipper\Module\ProductBundle\Model\Traits\SelectPriceListableInterface;

/**
 * Repair interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface RepairInterface extends
    AccountableOptionalInterface,
    CurrencyableInterface,
    IdInterface,
    OrganizationalRequiredInterface,
    ProductableOptionalInterface,
    ProductCombinationableOptionalInterface,
    SelectPriceListableInterface,
    TimestampableInterface,
    UserTrackableInterface
{
    /**
     * @return static
     */
    public function setReference(?string $reference);

    public function getReference(): ?string;

    /**
     * @return static
     */
    public function setDescription(?string $description);

    public function getDescription(): ?string;

    /**
     * @return static
     */
    public function setTrayReference(?string $trayReference);

    public function getTrayReference(): ?string;

    /**
     * @return static
     */
    public function setRepairer(?UserInterface $repairer);

    public function getRepairer(): ?UserInterface;

    /**
     * @return null|int|string
     */
    public function getRepairerId();

    /**
     * @return static
     */
    public function setDevice(?DeviceInterface $device);

    public function getDevice(): ?DeviceInterface;

    /**
     * @return null|int|string
     */
    public function getDeviceId();

    /**
     * @return static
     */
    public function setSwappedToDevice(?DeviceInterface $swappedToDevice);

    public function getSwappedToDevice(): ?DeviceInterface;

    /**
     * @return null|int|string
     */
    public function getSwappedToDeviceId();

    /**
     * @return static
     */
    public function setRepairPlace(?RepairPlaceInterface $repairPlace);

    public function getRepairPlace(): ?RepairPlaceInterface;

    /**
     * @return null|int|string
     */
    public function getRepairPlaceId();

    /**
     * @return static
     */
    public function setShipping(?ShippingInterface $shipping);

    public function getShipping(): ?ShippingInterface;

    /**
     * @return null|int|string
     */
    public function getShippingId();

    /**
     * @return static
     */
    public function setStatus(?ChoiceInterface $status);

    public function getStatus(): ?ChoiceInterface;

    /**
     * @return static
     */
    public function setWarrantyEndDate(?\DateTimeInterface $warrantyEndDate);

    public function getWarrantyEndDate(): ?\DateTimeInterface;

    /**
     * @return static
     */
    public function setPrice(?float $price);

    public function getPrice(): ?float;
}