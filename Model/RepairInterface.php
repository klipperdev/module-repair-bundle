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

use Doctrine\Common\Collections\Collection;
use Klipper\Component\DoctrineChoice\Model\ChoiceInterface;
use Klipper\Component\Model\Traits\CurrencyableInterface;
use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Component\Model\Traits\UserTrackableInterface;
use Klipper\Component\Security\Model\UserInterface;
use Klipper\Module\CarrierBundle\Model\ShippingInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\PartnerBundle\Model\PartnerAddressInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableOptionalInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountOwnerableInterface;
use Klipper\Module\PartnerBundle\Model\Traits\ContactableOptionalInterface;
use Klipper\Module\ProductBundle\Model\Traits\PriceListableInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductableOptionalInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductCombinationableOptionalInterface;

/**
 * Repair interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface RepairInterface extends
    AccountOwnerableInterface,
    AccountableOptionalInterface,
    ContactableOptionalInterface,
    CurrencyableInterface,
    IdInterface,
    OrganizationalRequiredInterface,
    ProductableOptionalInterface,
    ProductCombinationableOptionalInterface,
    PriceListableInterface,
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
    public function setInvoiceAddress(?PartnerAddressInterface $invoiceAddress);

    public function getInvoiceAddress(): ?PartnerAddressInterface;

    /**
     * @return static
     */
    public function setShippingAddress(?PartnerAddressInterface $shippingAddress);

    public function getShippingAddress(): ?PartnerAddressInterface;

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
    public function setWarrantyApplied(bool $warrantyApplied);

    public function hasWarrantyApplied(): bool;

    /**
     * @return static
     */
    public function setWarrantyComment(?string $warrantyComment);

    public function getWarrantyComment(): ?string;

    /**
     * @return static
     */
    public function setPrice(?float $price);

    public function getPrice(): ?float;

    /**
     * @return static
     */
    public function setReceiptedAt(?\DateTimeInterface $receiptedAt);

    public function getReceiptedAt(): ?\DateTimeInterface;

    /**
     * @return static
     */
    public function setDeclaredBreakdownByCustomer(?string $declaredBreakdownByCustomer);

    public function getDeclaredBreakdownByCustomer(): ?string;

    /**
     * @return static
     */
    public function setUsedCoupon(?CouponInterface $usedCoupon);

    public function getUsedCoupon(): ?CouponInterface;

    /**
     * @return static
     */
    public function setUnderContract(bool $underContract);

    public function isUnderContract(): bool;

    /**
     * @return static
     */
    public function setClosed(bool $closed);

    public function isClosed(): bool;

    /**
     * @return Collection|RepairItemInterface[]
     */
    public function getRepairItems(): Collection;

    /**
     * @return Collection|RepairBreakdownInterface[]
     */
    public function getRepairBreakdowns(): Collection;

    /**
     * @return Collection|RepairHistoryInterface[]
     */
    public function getRepairHistories(): Collection;
}
