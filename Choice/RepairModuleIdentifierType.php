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
final class RepairModuleIdentifierType implements ChoiceInterface
{
    public static function listIdentifiers(): array
    {
        return [
            'imei' => 'repair_module_identifier_type.imei',
            'serial_number' => 'repair_module_identifier_type.serial_number',
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
