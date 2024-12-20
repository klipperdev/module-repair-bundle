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
use Klipper\Component\Model\Traits\CurrencyableTrait;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\OwnerableOptionalTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Component\Model\Traits\UserTrackableTrait;
use Klipper\Component\Security\Model\UserInterface;
use Klipper\Module\CarrierBundle\Model\ShippingInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\PartnerBundle\Model\PartnerAddressInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableOptionalTrait;
use Klipper\Module\PartnerBundle\Model\Traits\ContactableOptionalTrait;
use Klipper\Module\ProductBundle\Model\Traits\PriceListableTrait;
use Klipper\Module\ProductBundle\Model\Traits\ProductableTrait;
use Klipper\Module\ProductBundle\Model\Traits\ProductCombinationableTrait;
use Klipper\Module\WorkcenterBundle\Model\WorkcenterInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Repair model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractRepair implements RepairInterface
{
    use AccountableOptionalTrait;
    use ContactableOptionalTrait;
    use CurrencyableTrait;
    use OrganizationalRequiredTrait;
    use OwnerableOptionalTrait;
    use PriceListableTrait;
    use ProductableTrait;
    use ProductCombinationableTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\AccountInterface"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(3)
     */
    protected ?AccountInterface $account = null;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=80)
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected ?string $reference = null;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=80)
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected ?string $batchReference = null;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=80)
     *
     * @Serializer\Expose
     */
    protected ?string $customerReference = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=65535)
     *
     * @Serializer\Expose
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=80)
     *
     * @Serializer\Expose
     */
    protected ?string $trayReference = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Component\Security\Model\UserInterface"
     * )
     *
     * @Serializer\Expose
     */
    protected ?UserInterface $repairer = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\DeviceBundle\Model\DeviceInterface",
     *     inversedBy="repairs"
     * )
     *
     * @Serializer\Expose
     */
    protected ?DeviceInterface $device = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\DeviceBundle\Model\DeviceInterface"
     * )
     *
     * @Assert\Expression(
     *     expression="!(value && value === this.getDevice())",
     *     message="klipper_repair.repair.swapped_to_device.same_device"
     * )
     *
     * @Serializer\Expose
     */
    protected ?DeviceInterface $swappedToDevice = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\WorkcenterBundle\Model\WorkcenterInterface"
     * )
     *
     * @Serializer\Expose
     */
    protected ?WorkcenterInterface $workcenter = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\PartnerAddressInterface"
     * )
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?PartnerAddressInterface $invoiceAddress = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\PartnerAddressInterface"
     * )
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?PartnerAddressInterface $shippingAddress = null;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\CarrierBundle\Model\ShippingInterface",
     *     inversedBy="repair",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\JoinColumn(
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     */
    protected ?ShippingInterface $shipping = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Component\DoctrineChoice\Model\ChoiceInterface"
     * )
     *
     * @EntityDoctrineChoice("repair_status")
     *
     * @Serializer\Expose
     */
    protected ?ChoiceInterface $status = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTimeInterface $warrantyEndDate = null;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     */
    protected bool $warrantyApplied = false;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=65535)
     *
     * @Serializer\Expose
     */
    protected ?string $warrantyComment = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="float")
     *
     * @Serializer\Expose
     */
    protected ?float $price = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTimeInterface $receiptedAt = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTimeInterface $repairedAt = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=65535)
     *
     * @Serializer\Expose
     */
    protected ?string $declaredBreakdownByCustomer = null;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\CouponInterface",
     *     inversedBy="usedByRepair",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(
     *     name="used_coupon_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     */
    protected ?CouponInterface $usedCoupon = null;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     */
    protected bool $underContract = false;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected bool $closed = false;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     */
    protected bool $unrepairable = false;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairInterface",
     *     cascade={"persist"},
     *     fetch="EAGER"
     * )
     * @ORM\JoinColumn(
     *     name="previous_repair_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     * @Serializer\Groups({"ViewsDetails", "View"})
     */
    protected ?RepairInterface $previousRepair = null;

    /**
     * @var null|Collection|RepairItemInterface[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairItemInterface",
     *     fetch="EXTRA_LAZY",
     *     mappedBy="repair",
     *     cascade={"persist", "remove"}
     * )
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     * @Serializer\MaxDepth(3)
     * @Serializer\Groups({"ViewsDetails", "View"})
     */
    protected ?Collection $repairItems = null;

    /**
     * @var null|Collection|RepairBreakdownInterface[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairBreakdownInterface",
     *     fetch="EXTRA_LAZY",
     *     mappedBy="repair",
     *     cascade={"persist", "remove"}
     * )
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     * @Serializer\MaxDepth(3)
     * @Serializer\Groups({"ViewsDetails", "View"})
     */
    protected ?Collection $repairBreakdowns = null;

    /**
     * @var null|Collection|RepairHistoryInterface[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairHistoryInterface",
     *     fetch="EXTRA_LAZY",
     *     mappedBy="repair",
     *     cascade={"persist", "remove"}
     * )
     * @ORM\OrderBy({
     *     "createdAt": "DESC"
     * })
     */
    protected ?Collection $repairHistories = null;

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setBatchReference(?string $batchReference): self
    {
        $this->batchReference = $batchReference;

        return $this;
    }

    public function getBatchReference(): ?string
    {
        return $this->batchReference;
    }

    public function setCustomerReference(?string $customerReference): self
    {
        $this->customerReference = $customerReference;

        return $this;
    }

    public function getCustomerReference(): ?string
    {
        return $this->customerReference;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setTrayReference(?string $trayReference): self
    {
        $this->trayReference = $trayReference;

        return $this;
    }

    public function getTrayReference(): ?string
    {
        return $this->trayReference;
    }

    public function setRepairer(?UserInterface $repairer): self
    {
        $this->repairer = $repairer;

        return $this;
    }

    public function getRepairer(): ?UserInterface
    {
        return $this->repairer;
    }

    public function getRepairerId()
    {
        return null !== $this->getRepairer()
            ? $this->getRepairer()->getId()
            : null;
    }

    public function setDevice(?DeviceInterface $device): self
    {
        $this->device = $device;

        return $this;
    }

    public function getDevice(): ?DeviceInterface
    {
        return $this->device;
    }

    public function getDeviceId()
    {
        return null !== $this->getDevice()
            ? $this->getDevice()->getId()
            : null;
    }

    public function setSwappedToDevice(?DeviceInterface $swappedToDevice): self
    {
        $this->swappedToDevice = $swappedToDevice;

        return $this;
    }

    public function getSwappedToDevice(): ?DeviceInterface
    {
        return $this->swappedToDevice;
    }

    public function getSwappedToDeviceId()
    {
        return null !== $this->getSwappedToDevice()
            ? $this->getSwappedToDevice()->getId()
            : null;
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

    public function getWorkcenterId()
    {
        return null !== $this->getWorkcenter()
            ? $this->getWorkcenter()->getId()
            : null;
    }

    public function setInvoiceAddress(?PartnerAddressInterface $invoiceAddress): self
    {
        $this->invoiceAddress = $invoiceAddress;

        return $this;
    }

    public function getInvoiceAddress(): ?PartnerAddressInterface
    {
        return $this->invoiceAddress;
    }

    public function setShippingAddress(?PartnerAddressInterface $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getShippingAddress(): ?PartnerAddressInterface
    {
        return $this->shippingAddress;
    }

    public function setShipping(?ShippingInterface $shipping): self
    {
        $this->shipping = $shipping;

        return $this;
    }

    public function getShipping(): ?ShippingInterface
    {
        return $this->shipping;
    }

    public function getShippingId()
    {
        return null !== $this->getShipping()
            ? $this->getShipping()->getId()
            : null;
    }

    public function setStatus(?ChoiceInterface $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?ChoiceInterface
    {
        return $this->status;
    }

    public function setWarrantyEndDate(?\DateTimeInterface $warrantyEndDate): self
    {
        $this->warrantyEndDate = $warrantyEndDate;

        return $this;
    }

    public function getWarrantyEndDate(): ?\DateTimeInterface
    {
        return $this->warrantyEndDate;
    }

    public function setWarrantyApplied(bool $warrantyApplied): self
    {
        $this->warrantyApplied = $warrantyApplied;

        return $this;
    }

    public function hasWarrantyApplied(): bool
    {
        return $this->warrantyApplied;
    }

    public function setWarrantyComment(?string $warrantyComment): self
    {
        $this->warrantyComment = $warrantyComment;

        return $this;
    }

    public function getWarrantyComment(): ?string
    {
        return $this->warrantyComment;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setReceiptedAt(?\DateTimeInterface $receiptedAt): self
    {
        $this->receiptedAt = $receiptedAt;

        return $this;
    }

    public function getReceiptedAt(): ?\DateTimeInterface
    {
        return $this->receiptedAt;
    }

    public function setRepairedAt(?\DateTimeInterface $repairedAtAt): self
    {
        $this->repairedAt = $repairedAtAt;

        return $this;
    }

    public function getRepairedAt(): ?\DateTimeInterface
    {
        return $this->repairedAt;
    }

    public function setDeclaredBreakdownByCustomer(?string $declaredBreakdownByCustomer): self
    {
        $this->declaredBreakdownByCustomer = $declaredBreakdownByCustomer;

        return $this;
    }

    public function getDeclaredBreakdownByCustomer(): ?string
    {
        return $this->declaredBreakdownByCustomer;
    }

    public function setUsedCoupon(?CouponInterface $usedCoupon): self
    {
        if (null !== $this->usedCoupon) {
            $this->usedCoupon->setUsedByRepair(null);
        }

        $this->usedCoupon = $usedCoupon;

        if (null !== $usedCoupon) {
            $usedCoupon->setUsedByRepair($this);
        }

        return $this;
    }

    public function getUsedCoupon(): ?CouponInterface
    {
        return $this->usedCoupon;
    }

    public function setUnderContract(bool $underContract): self
    {
        $this->underContract = $underContract;

        return $this;
    }

    public function isUnderContract(): bool
    {
        return $this->underContract;
    }

    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;

        return $this;
    }

    public function isUnrepairable(): bool
    {
        return $this->unrepairable;
    }

    public function setUnrepairable(bool $unrepairable): self
    {
        $this->unrepairable = $unrepairable;

        return $this;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function setPreviousRepair(?RepairInterface $previousRepair): self
    {
        $this->previousRepair = $previousRepair;

        return $this;
    }

    public function getPreviousRepair(): ?RepairInterface
    {
        return $this->previousRepair;
    }

    public function getRepairItems(): Collection
    {
        return $this->repairItems ?: $this->repairItems = new ArrayCollection();
    }

    public function getRepairBreakdowns(): Collection
    {
        return $this->repairBreakdowns ?: $this->repairBreakdowns = new ArrayCollection();
    }

    public function getRepairHistories(): Collection
    {
        return $this->repairHistories ?: $this->repairHistories = new ArrayCollection();
    }
}
