<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\RepairBundle\Choice;

use Klipper\Component\Choice\ChoiceInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
final class RepairModuleType implements ChoiceInterface
{
    public static function listIdentifiers(): array
    {
        return [
            'annual_flat_rate' => 'repair_module_type.annual_flat_rate',
            'fix_price' => 'repair_module_type.fix_price',
            'price_list' => 'repair_module_type.price_list',
            'coupon' => 'repair_module_type.coupon',
        ];
    }

    public static function getValues(): array
    {
        return array_keys(static::listIdentifiers());
    }

    public static function getTranslationDomain(): string
    {
        return 'choices';
    }
}
