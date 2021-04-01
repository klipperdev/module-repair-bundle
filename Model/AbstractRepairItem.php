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
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Component\Model\Traits\UserTrackableTrait;
use Klipper\Module\ProductBundle\Model\ProductInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductableTrait;
use Klipper\Module\ProductBundle\Model\Traits\ProductCombinationableTrait;
use Klipper\Module\RepairBundle\Validator\Constraints as KlipperRepairAssert;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Repair item model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractRepairItem implements RepairItemInterface
{
    use OrganizationalRequiredTrait;
    use ProductableTrait;
    use ProductCombinationableTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\ProductBundle\Model\ProductInterface",
     *     fetch="EAGER"
     * )
     *
     * @Assert\Expression(
     *     expression="!('operation' == this.getType() && (null == this.getProduct() || null == this.getProduct().getProductType() || 'operation' != this.getProduct().getProductType().getValue()))"
     * )
     *
     * @Serializer\Expose
     */
    protected ?ProductInterface $product = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairInterface",
     *     fetch="EAGER",
     *     inversedBy="repairItems"
     * )
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?RepairInterface $repair = null;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @KlipperRepairAssert\RepairItemTypeChoice
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?string $type = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="float")
     *
     * @Serializer\Expose
     */
    protected ?float $price = null;

    /**
     * @ORM\Column(type="float", nullable=true)
     *
     * @Assert\Type(type="float")
     *
     * @Serializer\Expose
     * @Serializer\ReadOnly
     */
    protected ?float $finalPrice = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=65535)
     *
     * @Serializer\Expose
     */
    protected ?string $internalComment = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=65535)
     *
     * @Serializer\Expose
     */
    protected ?string $publicComment = null;

    public function setRepair(?RepairInterface $repair): self
    {
        $this->repair = $repair;

        return $this;
    }

    public function getRepair(): ?RepairInterface
    {
        return $this->repair;
    }

    public function getRepairId()
    {
        return null !== $this->getRepair()
            ? $this->getRepair()->getId()
            : null;
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

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setFinalPrice(?float $finalPrice): self
    {
        $this->finalPrice = $finalPrice;

        return $this;
    }

    public function getFinalPrice(): ?float
    {
        return $this->finalPrice;
    }

    public function setInternalComment(?string $internalComment): self
    {
        $this->internalComment = $internalComment;

        return $this;
    }

    public function getInternalComment(): ?string
    {
        return $this->internalComment;
    }

    public function setPublicComment(?string $publicComment): self
    {
        $this->publicComment = $publicComment;

        return $this;
    }

    public function getPublicComment(): ?string
    {
        return $this->publicComment;
    }
}
