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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Klipper\Component\DoctrineChoice\Model\ChoiceInterface;
use Klipper\Component\DoctrineChoice\Validator\Constraints\EntityDoctrineChoice;
use Klipper\Component\Model\Traits\EnableTrait;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Module\CarrierBundle\Model\CarrierInterface;
use Klipper\Module\DeviceBundle\Validator\Constraints as KlipperDeviceAssert;
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\PartnerBundle\Model\PartnerAddressInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableTrait;
use Klipper\Module\RepairBundle\Model\Traits\RepairModuleableInterface;
use Klipper\Module\RepairBundle\Validator\Constraints as KlipperRepairAssert;
use Klipper\Module\WorkcenterBundle\Model\WorkcenterInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Repair module model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractRepairModule implements RepairModuleInterface
{
    use AccountableTrait;
    use EnableTrait;
    use OrganizationalRequiredTrait;
    use TimestampableTrait;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\AccountInterface",
     *     inversedBy="repairModule"
     * )
     *
     * @Assert\NotNull
     *
     * @Serializer\Type("AssociationId")
     * @Serializer\Expose
     */
    protected ?AccountInterface $account = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\AccountInterface"
     * )
     *
     * @Assert\NotNull
     * @Assert\Expression(
     *     expression="!(!value || !value.isSupplier())",
     *     message="klipper_repair.repair_module.invalid_supplier"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?AccountInterface $supplier = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=255)
     *
     * @Serializer\Expose
     */
    protected ?string $internalContractReference = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=255)
     *
     * @Serializer\Expose
     */
    protected ?string $supplierReference = null;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @KlipperRepairAssert\RepairModuleTypeChoice
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?string $type = null;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @KlipperRepairAssert\RepairModuleSwapChoice
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?string $swap = null;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @KlipperDeviceAssert\DeviceIdentifierTypeChoice
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?string $identifierType = null;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @KlipperRepairAssert\RepairModulePriceCalculationChoice
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?string $priceCalculation = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="float")
     * @Assert\Expression(
     *     expression="!(this.getType() in ['annual_flat_rate', 'fix_price', 'coupon'] && null === value)",
     *     message="This value should not be blank."
     * )
     *
     * @Serializer\Expose
     */
    protected ?float $defaultPrice = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\WorkcenterBundle\Model\WorkcenterInterface"
     * )
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?WorkcenterInterface $workcenter = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\PartnerAddressInterface"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     */
    protected ?PartnerAddressInterface $defaultInvoiceAddress = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\PartnerAddressInterface"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     */
    protected ?PartnerAddressInterface $defaultShippingAddress = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\CarrierBundle\Model\CarrierInterface"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?CarrierInterface $defaultCarrier = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Component\DoctrineChoice\Model\ChoiceInterface"
     * )
     *
     * @EntityDoctrineChoice("repair_status")
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?ChoiceInterface $defaultStatus = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Component\DoctrineChoice\Model\ChoiceInterface"
     * )
     *
     * @EntityDoctrineChoice("repair_status")
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?ChoiceInterface $defaultStatusForNoUnderContract = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=65535)
     *
     * @Serializer\Expose
     */
    protected ?string $comment = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=65535)
     *
     * @Serializer\Expose
     */
    protected ?string $excludedScope = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?int $repairTimeInDay = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="integer")
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?int $warrantyLengthInMonth = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Type(type="integer")
     *
     * @Serializer\Expose
     */
    protected ?int $defaultCouponValidityInMonth = null;

    /**
     * @var null|Collection|RepairModuleProductInterface[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairModuleProductInterface",
     *     fetch="EXTRA_LAZY",
     *     mappedBy="repairModule",
     *     cascade={"persist", "remove"}
     * )
     */
    protected ?Collection $repairModuleProducts = null;

    public function setAccount(?AccountInterface $account): self
    {
        $this->account = $account;

        if ($account instanceof RepairModuleableInterface) {
            $account->setRepairModule($this);
        }

        return $this;
    }

    public function setSupplier(?AccountInterface $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getSupplier(): ?AccountInterface
    {
        return $this->supplier;
    }

    public function setInternalContractReference(?string $internalContractReference): self
    {
        $this->internalContractReference = $internalContractReference;

        return $this;
    }

    public function getInternalContractReference(): ?string
    {
        return $this->internalContractReference;
    }

    public function setSupplierReference(?string $supplierReference): self
    {
        $this->supplierReference = $supplierReference;

        return $this;
    }

    public function getSupplierReference(): ?string
    {
        return $this->supplierReference;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setSwap(?string $swap): self
    {
        $this->swap = $swap;

        return $this;
    }

    public function getSwap(): ?string
    {
        return $this->swap;
    }

    public function setIdentifierType(?string $identifierType): self
    {
        $this->identifierType = $identifierType;

        return $this;
    }

    public function getIdentifierType(): ?string
    {
        return $this->identifierType;
    }

    public function setPriceCalculation(?string $priceCalculation): self
    {
        $this->priceCalculation = $priceCalculation;

        return $this;
    }

    public function getPriceCalculation(): ?string
    {
        return $this->priceCalculation;
    }

    public function setDefaultPrice(?float $defaultPrice): self
    {
        $this->defaultPrice = $defaultPrice;

        return $this;
    }

    public function getDefaultPrice(): ?float
    {
        return $this->defaultPrice;
    }

    public function setWorkcenter(?WorkcenterInterface $workcenter): self
    {
        $this->workcenter = $workcenter;

        return $this;
    }

    public function getWorkcenter(): ?WorkcenterInterface
    {
        return $this->workcenter;
    }

    public function setDefaultInvoiceAddress(?PartnerAddressInterface $defaultInvoiceAddress): self
    {
        $this->defaultInvoiceAddress = $defaultInvoiceAddress;

        return $this;
    }

    public function getDefaultInvoiceAddress(): ?PartnerAddressInterface
    {
        return $this->defaultInvoiceAddress;
    }

    public function setDefaultShippingAddress(?PartnerAddressInterface $defaultShippingAddress): self
    {
        $this->defaultShippingAddress = $defaultShippingAddress;

        return $this;
    }

    public function getDefaultShippingAddress(): ?PartnerAddressInterface
    {
        return $this->defaultShippingAddress;
    }

    public function setDefaultCarrier(?CarrierInterface $defaultCarrier): self
    {
        $this->defaultCarrier = $defaultCarrier;

        return $this;
    }

    public function getDefaultCarrier(): ?CarrierInterface
    {
        return $this->defaultCarrier;
    }

    public function setDefaultStatus(?ChoiceInterface $defaultStatus): self
    {
        $this->defaultStatus = $defaultStatus;

        return $this;
    }

    public function getDefaultStatus(): ?ChoiceInterface
    {
        return $this->defaultStatus;
    }

    public function setDefaultStatusForNoUnderContract(?ChoiceInterface $defaultStatusForNoUnderContract): self
    {
        $this->defaultStatusForNoUnderContract = $defaultStatusForNoUnderContract;

        return $this;
    }

    public function getDefaultStatusForNoUnderContract(): ?ChoiceInterface
    {
        return $this->defaultStatusForNoUnderContract;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setExcludedScope(?string $excludedScope): self
    {
        $this->excludedScope = $excludedScope;

        return $this;
    }

    public function getExcludedScope(): ?string
    {
        return $this->excludedScope;
    }

    public function setRepairTimeInDay(?int $repairTimeInDay): self
    {
        $this->repairTimeInDay = $repairTimeInDay;

        return $this;
    }

    public function getRepairTimeInDay(): ?int
    {
        return $this->repairTimeInDay;
    }

    public function setWarrantyLengthInMonth(?int $warrantyLengthInMonth): self
    {
        $this->warrantyLengthInMonth = $warrantyLengthInMonth;

        return $this;
    }

    public function getWarrantyLengthInMonth(): ?int
    {
        return $this->warrantyLengthInMonth;
    }

    public function setDefaultCouponValidityInMonth(?int $defaultCouponValidityInMonth): self
    {
        $this->defaultCouponValidityInMonth = $defaultCouponValidityInMonth;

        return $this;
    }

    public function getDefaultCouponValidityInMonth(): ?int
    {
        return $this->defaultCouponValidityInMonth;
    }

    public function getRepairModuleProducts(): Collection
    {
        return $this->repairModuleProducts ?: $this->repairModuleProducts = new ArrayCollection();
    }
}
