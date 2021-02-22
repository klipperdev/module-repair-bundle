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
use Klipper\Component\Model\Traits\UserTrackableTrait;
use Klipper\Module\CarrierBundle\Model\ShippingInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Repair history model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractRepairHistory implements RepairHistoryInterface
{
    use OrganizationalRequiredTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairInterface",
     *     inversedBy="repairHistories",
     *     fetch="EAGER"
     * )
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?RepairInterface $repair = null;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     */
    protected bool $public = false;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     */
    protected bool $swap = false;

    /**
     * @ORM\ManyToOne(targetEntity="Klipper\Component\DoctrineChoice\Model\ChoiceInterface", fetch="EAGER")
     *
     * @EntityDoctrineChoice("repair_status")
     *
     * @Serializer\Expose
     */
    protected ?ChoiceInterface $previousStatus = null;

    /**
     * @ORM\ManyToOne(targetEntity="Klipper\Component\DoctrineChoice\Model\ChoiceInterface", fetch="EAGER")
     *
     * @EntityDoctrineChoice("repair_status")
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?ChoiceInterface $newStatus = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\DeviceBundle\Model\DeviceInterface",
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     */
    protected ?DeviceInterface $previousDevice = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\DeviceBundle\Model\DeviceInterface",
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     */
    protected ?DeviceInterface $newDevice = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\CarrierBundle\Model\ShippingInterface",
     *     fetch="EAGER"
     * )
     *
     * @Serializer\Expose
     */
    protected ?ShippingInterface $shipping = null;

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

    public function setPublic(bool $public): self
    {
        $this->public = $public;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setSwap(bool $swap): self
    {
        $this->swap = $swap;

        return $this;
    }

    public function isSwap(): bool
    {
        return $this->swap;
    }

    public function setPreviousStatus(?ChoiceInterface $previousStatus): self
    {
        $this->previousStatus = $previousStatus;

        return $this;
    }

    public function getPreviousStatus(): ?ChoiceInterface
    {
        return $this->previousStatus;
    }

    public function setNewStatus(?ChoiceInterface $newStatus): self
    {
        $this->newStatus = $newStatus;

        return $this;
    }

    public function getNewStatus(): ?ChoiceInterface
    {
        return $this->newStatus;
    }

    public function setPreviousDevice(?DeviceInterface $previousDevice): self
    {
        $this->previousDevice = $previousDevice;

        return $this;
    }

    public function getPreviousDevice(): ?DeviceInterface
    {
        return $this->previousDevice;
    }

    public function setNewDevice(?DeviceInterface $newDevice): self
    {
        $this->newDevice = $newDevice;

        return $this;
    }

    public function getNewDevice(): ?DeviceInterface
    {
        return $this->newDevice;
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
}
