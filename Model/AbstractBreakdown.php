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
use Klipper\Component\Model\Traits\LabelableTrait;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Component\Model\Traits\UserTrackableTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Breakdown model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractBreakdown implements BreakdownInterface
{
    use LabelableTrait;
    use OrganizationalRequiredTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\Type(type="boolean")
     *
     * @Serializer\Expose
     */
    protected bool $repairImpossible = false;

    public function setRepairImpossible(bool $repairImpossible): self
    {
        $this->repairImpossible = $repairImpossible;

        return $this;
    }

    public function isRepairImpossible(): bool
    {
        return $this->repairImpossible;
    }
}
