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
use Klipper\Module\RepairBundle\Model\RepairModuleInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
trait RepairModuleableTrait
{
    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairModuleInterface",
     *     mappedBy="account",
     *     fetch="EXTRA_LAZY"
     * )
     * @ORM\JoinColumn(
     *     name="repair_module_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Serializer\Expose
     * @Serializer\ReadOnlyProperty
     * @Serializer\MaxDepth(3)
     * @Serializer\Groups({"View", "ViewsDetails"})
     */
    protected ?RepairModuleInterface $repairModule = null;

    /**
     * @see RepairModuleableInterface::setOperationBreakdown()
     */
    public function setRepairModule(?RepairModuleInterface $repairModule): self
    {
        $this->repairModule = $repairModule;

        return $this;
    }

    /**
     * @see RepairModuleableInterface::getBreakdown()
     */
    public function getRepairModule(): ?RepairModuleInterface
    {
        return $this->repairModule;
    }
}
