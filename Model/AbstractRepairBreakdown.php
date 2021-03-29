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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Repair breakdown model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractRepairBreakdown implements RepairBreakdownInterface
{
    use OrganizationalRequiredTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairInterface",
     *     fetch="EAGER",
     *     inversedBy="repairBreakdowns"
     * )
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=false)
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?RepairInterface $repair = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\BreakdownInterface",
     *     fetch="EAGER"
     * )
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?BreakdownInterface $breakdown = null;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     */
    protected ?bool $repairImpossible = null;

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

    public function setBreakdown(?BreakdownInterface $breakdown): self
    {
        $this->breakdown = $breakdown;

        return $this;
    }

    public function getBreakdown(): ?BreakdownInterface
    {
        return $this->breakdown;
    }

    public function setRepairImpossible(?bool $repairImpossible): self
    {
        $this->repairImpossible = $repairImpossible;

        return $this;
    }

    public function isRepairImpossible(): bool
    {
        return true === $this->repairImpossible;
    }

    public function isRepairImpossibleInitialized(): bool
    {
        return null !== $this->repairImpossible;
    }
}
