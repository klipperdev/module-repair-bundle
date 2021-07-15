<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\RepairBundle\Doctrine\Listener;

use Doctrine\ORM\EntityManagerInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface RepairPriceListenerInterface
{
    /**
     * @param array<int|string, float> $repairPrices Map of repair ids and prices
     */
    public function onUpdate(EntityManagerInterface $em, array $repairPrices): void;
}
