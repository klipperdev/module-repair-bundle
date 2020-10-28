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

use Klipper\Module\RepairBundle\Model\BreakdownInterface;

/**
 * Product Breakdownable interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface ProductBreakdownableInterface
{
    /**
     * @return static
     */
    public function setOperationBreakdown(?BreakdownInterface $operationBreakdown);

    public function getOperationBreakdown(): ?BreakdownInterface;
}
