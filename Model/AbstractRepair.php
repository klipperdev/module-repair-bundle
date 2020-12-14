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
    use ProductableTrait;
    use ProductCombinationableTrait;
    use PriceListableTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\AccountInterface",
     *     fetch="EAGER"
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
     * @Serializer\ReadOnly
     */
    protected ?string $reference = null;

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
     *     targetEntity="Klipper\Component\Security\Model\UserInterface",
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     */
    protected ?UserInterface $repairer = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\DeviceBundle\Model\DeviceInterface",
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     */
    protected ?DeviceInterface $device = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\DeviceBundle\Model\DeviceInterface",
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     */
    protected ?DeviceInterface $swappedToDevice = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairPlaceInterface",
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     */
    protected ?RepairPlaceInterface $repairPlace = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\PartnerAddressInterface",
     *     fetch="EAGER"
     * )
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?PartnerAddressInterface $invoiceAddress = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\PartnerBundle\Model\PartnerAddressInterface",
     *     fetch="EAGER"
     * )
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?PartnerAddressInterface $shippingAddress = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\CarrierBundle\Model\ShippingInterface",
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     */
    protected ?ShippingInterface $shipping = null;

    /**
     * @ORM\ManyToOne(targetEntity="Klipper\Component\DoctrineChoice\Model\ChoiceInterface", fetch="EAGER")
     *
     * @EntityDoctrineChoice("repair_status")
     *
     * @Assert\NotBlank
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
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?CouponInterface $usedCoupon = null;

    /**
     * @var null|Collection|RepairItemInterface[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairItemInterface",
     *     mappedBy="repair",
     *     fetch="EXTRA_LAZY",
     *     cascade={"persist", "remove"}
     * )
     */
    protected ?Collection $repairItems = null;

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
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

    public function setRepairPlace(?RepairPlaceInterface $repairPlace): self
    {
        $this->repairPlace = $repairPlace;

        return $this;
    }

    public function getRepairPlace(): ?RepairPlaceInterface
    {
        return $this->repairPlace;
    }

    public function getRepairPlaceId()
    {
        return null !== $this->getRepairPlace()
            ? $this->getRepairPlace()->getId()
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
        $this->usedCoupon = $usedCoupon;

        return $this;
    }

    public function getUsedCoupon(): ?CouponInterface
    {
        return $this->usedCoupon;
    }

    public function getRepairItems(): Collection
    {
        return $this->repairItems ?: $this->repairItems = new ArrayCollection();
    }
}
