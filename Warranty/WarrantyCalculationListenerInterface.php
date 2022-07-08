<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\RepairBundle\Warranty;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface WarrantyCalculationListenerInterface
{
    public function calculate(object $account, \DateTimeInterface $startDate, \DateTimeInterface $endDate): void;
}
