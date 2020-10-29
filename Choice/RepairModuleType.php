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
            'flat_rate' => 'repair_module_type.flat_rate',
            'pay_as_you_go' => 'repair_module_type.pay_as_you_go',
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
