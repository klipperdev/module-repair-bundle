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
use Klipper\Module\CarrierBundle\Model\ShippingInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
trait ShippingRepairableTrait
{
    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairInterface",
     *     mappedBy="shipping"
     * )
     * @ORM\JoinColumn(
     *     name="repair_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     * @Serializer\Groups({"View"})
     */
    protected ?RepairInterface $repair = null;

    public function setRepair(?RepairInterface $repair): self
    {
        /** @var ShippingInterface $shipping */
        $shipping = $this;
        $this->repair = $repair;

        $repair->setShipping($shipping);

        return $this;
    }

    public function getRepair(): ?RepairInterface
    {
        return $this->repair;
    }

    /**
     * @return null|int|string
     */
    public function getRepairId()
    {
        return null !== $this->getRepair() ? $this->getRepair()->getId() : null;
    }
}
