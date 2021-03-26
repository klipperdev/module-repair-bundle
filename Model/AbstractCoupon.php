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

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Klipper\Component\DoctrineChoice\Model\ChoiceInterface;
use Klipper\Component\DoctrineChoice\Validator\Constraints\EntityDoctrineChoice;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\PartnerBundle\Model\PartnerAddressInterface;
use Klipper\Module\PartnerBundle\Model\Traits\AccountableRequiredTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Comment model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractCoupon implements CouponInterface
{
    use AccountableRequiredTrait;
    use OrganizationalRequiredTrait;
    use TimestampableTrait;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=80)
     *
     * @Serializer\Expose
     */
    protected ?string $reference = null;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?string $orderReference = null;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?string $internalContractReference = null;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     *
     * @Serializer\Expose
     */
    protected ?string $supplierReference = null;

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
     *     targetEntity="Klipper\Module\PartnerBundle\Model\AccountInterface",
     *     fetch="EAGER"
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
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="float")
     *
     * @Serializer\Expose
     */
    protected ?float $price = null;

    /**
     * @ORM\ManyToOne(targetEntity="Klipper\Component\DoctrineChoice\Model\ChoiceInterface", fetch="EAGER")
     *
     * @EntityDoctrineChoice("coupon_status")
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?ChoiceInterface $status = null;

    /**
     * @ORM\Column(type="datetime")
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTimeInterface $validUntil = null;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairInterface",
     *     mappedBy="usedCoupon",
     *     fetch="EAGER"
     * )
     * @ORM\JoinColumn(
     *     name="used_by_repair_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     * @Serializer\Groups({"ViewsDetails", "View"})
     */
    protected ?RepairInterface $usedByRepair = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTimeInterface $usedAt = null;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\CouponInterface",
     *     inversedBy="newCreditedCoupon",
     *     cascade={"persist"},
     *     fetch="EAGER"
     * )
     * @ORM\JoinColumn(
     *     name="recredited_coupon_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Assert\Expression(
     *     expression="!(value && !value.getUsedByRepair())",
     *     message="klipper_repair.repair_module.recredited_coupon_must_be_used"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     * @Serializer\Groups({"ViewsDetails", "View"})
     */
    protected ?CouponInterface $recreditedCoupon = null;

    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\CouponInterface",
     *     mappedBy="recreditedCoupon",
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     * @Serializer\Groups({"ViewsDetails", "View"})
     */
    protected ?CouponInterface $newCreditedCoupon = null;

    public function __clone()
    {
        if (property_exists($this, 'id')) {
            $this->id = null;
        }

        $this->createdAt = null;
        $this->updatedAt = null;
        $this->validUntil = null;
        $this->usedByRepair = null;
        $this->usedAt = null;
        $this->recreditedCoupon = null;
        $this->newCreditedCoupon = null;
        $this->reference = null;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setOrderReference(?string $orderReference): self
    {
        $this->orderReference = $orderReference;

        return $this;
    }

    public function getOrderReference(): ?string
    {
        return $this->orderReference;
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

    public function setSupplier(?AccountInterface $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getSupplier(): ?AccountInterface
    {
        return $this->supplier;
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

    public function setStatus(?ChoiceInterface $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?ChoiceInterface
    {
        return $this->status;
    }

    public function setValidUntil(?\DateTimeInterface $validUntil): self
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    public function getValidUntil(): ?\DateTimeInterface
    {
        return $this->validUntil;
    }

    public function setUsedByRepair(?RepairInterface $usedByRepair): self
    {
        $this->usedByRepair = $usedByRepair;

        return $this;
    }

    public function getUsedByRepair(): ?RepairInterface
    {
        return $this->usedByRepair;
    }

    public function setUsedAt(?\DateTimeInterface $usedAt): self
    {
        $this->usedAt = $usedAt;

        return $this;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }

    public function setRecreditedCoupon(?CouponInterface $recreditedCoupon): self
    {
        $this->recreditedCoupon = $recreditedCoupon;

        return $this;
    }

    public function getRecreditedCoupon(): ?CouponInterface
    {
        return $this->recreditedCoupon;
    }

    public function setNewCreditedCoupon(?CouponInterface $newCreditedCoupon): self
    {
        $this->newCreditedCoupon = $newCreditedCoupon;

        return $this;
    }

    public function getNewCreditedCoupon(): ?CouponInterface
    {
        return $this->newCreditedCoupon;
    }

    /**
     * @Serializer\VirtualProperty
     */
    public function isRecredited(): bool
    {
        return null !== $this->getNewCreditedCoupon();
    }
}
