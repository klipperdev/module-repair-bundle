<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\RepairBundle\Model\Traits;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
trait DeviceRepairableTrait
{
    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairInterface",
     *     fetch="EAGER"
     * )
     * @ORM\JoinColumn(
     *     name="last_repair_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     * @Serializer\ReadOnlyProperty
     */
    protected ?RepairInterface $lastRepair = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @Assert\Type(type="datetime")
     *
     * @Serializer\Expose
     */
    protected ?\DateTimeInterface $warrantyEndDate = null;

    public function setLastRepair(?RepairInterface $lastRepair): self
    {
        $this->lastRepair = $lastRepair;

        return $this;
    }

    public function getLastRepair(): ?RepairInterface
    {
        return $this->lastRepair;
    }

    /**
     * @Serializer\VirtualProperty
     *
     * @return null|int|string
     */
    public function getLastRepairId()
    {
        return null !== $this->getLastRepair() ? $this->getLastRepair()->getId() : null;
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
}
