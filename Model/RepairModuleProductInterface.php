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

use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Module\ProductBundle\Model\Traits\ProductableRequiredInterface;
use Klipper\Module\RepairBundle\Model\Traits\RepairModuleableInterface;

/**
 * Repair module product interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface RepairModuleProductInterface extends
    IdInterface,
    OrganizationalRequiredInterface,
    ProductableRequiredInterface,
    RepairModuleableInterface,
    TimestampableInterface
{
    /**
     * @return static
     */
    public function setSpecificities(?string $specificities);

    public function getSpecificities(): ?string;
}
