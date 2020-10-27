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

use Klipper\Component\Geocoder\Model\Traits\AddressInterface;
use Klipper\Component\Model\Traits\IdInterface;
use Klipper\Component\Model\Traits\LabelableInterface;
use Klipper\Component\Model\Traits\OrganizationalRequiredInterface;
use Klipper\Component\Model\Traits\TimestampableInterface;
use Klipper\Component\Model\Traits\UserTrackableInterface;

/**
 * Repair place interface.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface RepairPlaceInterface extends
    AddressInterface,
    IdInterface,
    LabelableInterface,
    OrganizationalRequiredInterface,
    TimestampableInterface,
    UserTrackableInterface
{
    /**
     * @return static
     */
    public function setDescription(?string $description);

    public function getDescription(): ?string;

    /**
     * @return static
     */
    public function setSubcontractor(bool $subcontractor);

    public function isSubcontractor(): bool;
}
