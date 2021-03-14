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
use Klipper\Module\ProductBundle\Model\ProductInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductableTrait;
use Klipper\Module\RepairBundle\Model\Traits\RepairModuleableTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Repair module product model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractRepairModuleProduct implements RepairModuleProductInterface
{
    use OrganizationalRequiredTrait;
    use ProductableTrait;
    use RepairModuleableTrait;
    use TimestampableTrait;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\RepairModuleInterface",
     *     inversedBy="repairModuleProducts",
     *     fetch="EAGER"
     * )
     * @ORM\JoinColumn(
     *     name="repair_module_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     * @Serializer\Groups({"ViewsDetails", "View"})
     */
    protected ?RepairModuleInterface $repairModule = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\ProductBundle\Model\ProductInterface",
     *     fetch="EAGER"
     * )
     *
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(2)
     */
    protected ?ProductInterface $product = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=65535)
     *
     * @Serializer\Expose
     */
    protected ?string $specificities = null;

    public function setSpecificities(?string $specificities): self
    {
        $this->specificities = $specificities;

        return $this;
    }

    public function getSpecificities(): ?string
    {
        return $this->specificities;
    }
}
