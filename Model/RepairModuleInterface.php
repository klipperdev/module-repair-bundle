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
use Klipper\Component\Model\Traits\EnableInterface;
use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Module\CarrierBundle\Model\CarrierInterface;
use Klipper\Module\PartnerBundle\Model\PartnerAddressInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableRequiredInterface;

/**
 * Repair module interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface RepairModuleInterface extends
    AccountableRequiredInterface,
    EnableInterface,
    IdInterface,
    OrganizationalRequiredInterface,
    TimestampableInterface
{
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
    public function setType(?string $type);

    public function getType(): ?string;

    /**
     * @return static
     */
    public function setSwap(?string $swap);

    public function getSwap(): ?string;

    /**
     * @return static
     */
    public function setIdentifierType(?string $identifierType);

    public function getIdentifierType(): ?string;

    /**
     * @return static
     */
    public function setPriceCalculation(?string $priceCalculation);

    public function getPriceCalculation(): ?string;

    /**
     * @return static
     */
    public function setDefaultPrice(?float $defaultPrice);

    public function getDefaultPrice(): ?float;

    /**
     * @return static
     */
    public function setRepairPlace(?RepairPlaceInterface $repairPlace);

    public function getRepairPlace(): ?RepairPlaceInterface;

    /**
     * @return static
     */
    public function setDefaultInvoiceAddress(?PartnerAddressInterface $defaultInvoiceAddress);

    public function getDefaultInvoiceAddress(): ?PartnerAddressInterface;

    /**
     * @return static
     */
    public function setDefaultShippingAddress(?PartnerAddressInterface $defaultShippingAddress);

    public function getDefaultShippingAddress(): ?PartnerAddressInterface;

    /**
     * @return static
     */
    public function setDefaultCarrier(?CarrierInterface $defaultCarrier);

    public function getDefaultCarrier(): ?CarrierInterface;

    /**
     * @return static
     */
    public function setDefaultStatus(?ChoiceInterface $defaultStatus);

    public function getDefaultStatus(): ?ChoiceInterface;

    /**
     * @return static
     */
    public function setComment(?string $comment);

    public function getComment(): ?string;

    /**
     * @return static
     */
    public function setExcludedScope(?string $excludedScope);

    public function getExcludedScope(): ?string;

    /**
     * @return static
     */
    public function setRepairTimeInDay(?int $repairTimeInDay);

    public function getRepairTimeInDay(): ?int;

    /**
     * @return static
     */
    public function setWarrantyLengthInMonth(?int $warrantyLengthInMonth);

    public function getWarrantyLengthInMonth(): ?int;

    /**
     * @return static
     */
    public function setDefaultCouponValidityInMonth(?int $defaultCouponValidityInMonth);

    public function getDefaultCouponValidityInMonth(): ?int;
}
