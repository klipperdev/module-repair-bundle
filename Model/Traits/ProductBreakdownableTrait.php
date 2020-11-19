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
use Klipper\Module\RepairBundle\Model\BreakdownInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
trait ProductBreakdownableTrait
{
    /**
     * @ORM\ManyToOne(
     *     targetEntity="Klipper\Module\RepairBundle\Model\BreakdownInterface",
     *     fetch="EAGER"
     * )
     *
     * @Assert\Expression(
     *     expression="!(value && (null == this.getProductType() || 'operation' != this.getProductType().getValue()))",
     *     message="This value should be blank."
     * )
     *
     * @Serializer\Expose
     * @Serializer\MaxDepth(1)
     */
    protected ?BreakdownInterface $operationBreakdown = null;

    /**
     * @see ProductBreakdownableInterface::setOperationBreakdown()
     */
    public function setOperationBreakdown(?BreakdownInterface $operationBreakdown): self
    {
        $this->operationBreakdown = $operationBreakdown;

        return $this;
    }

    /**
     * @see ProductBreakdownableInterface::getBreakdown()
     */
    public function getOperationBreakdown(): ?BreakdownInterface
    {
        return $this->operationBreakdown;
    }
}
