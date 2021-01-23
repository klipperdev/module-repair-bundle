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
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\PartnerBundle\Model\PartnerAddressInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableRequiredInterface;

/**
 * Coupon interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface CouponInterface extends
    AccountableRequiredInterface,
    IdInterface,
    OrganizationalRequiredInterface,
    TimestampableInterface
{
    /**
     * @return static
     */
    public function setReference(?string $reference);

    public function getReference(): ?string;

    /**
     * @return static
     */
    public function setInternalContractReference(?string $internalContractReference);

    public function getInternalContractReference(): ?string;

    /**
     * @return static
     */
    public function setCustomerReference(?string $customerReference);

    public function getCustomerReference(): ?string;

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
    public function setSupplier(?AccountInterface $supplier);

    public function getSupplier(): ?AccountInterface;

    /**
     * @return static
     */
    public function setPrice(?float $price);

    public function getPrice(): ?float;

    /**
     * @return static
     */
    public function setStatus(?ChoiceInterface $status);

    public function getStatus(): ?ChoiceInterface;

    /**
     * @return static
     */
    public function setValidUntil(?\DateTimeInterface $validUntil);

    public function getValidUntil(): ?\DateTimeInterface;

    /**
     * @return static
     */
    public function setUsedByRepair(?RepairInterface $usedByRepair);

    public function getUsedByRepair(): ?RepairInterface;

    /**
     * @return static
     */
    public function setUsedAt(?\DateTimeInterface $usedAt);

    public function getUsedAt(): ?\DateTimeInterface;
}
