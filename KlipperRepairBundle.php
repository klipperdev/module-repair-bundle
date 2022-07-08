<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\RepairBundle;

use Klipper\Module\RepairBundle\DependencyInjection\Compiler\RepairPriceListenerPass;
use Klipper\Module\RepairBundle\DependencyInjection\Compiler\RepairWarrantyCalculationListenerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class KlipperRepairBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RepairPriceListenerPass());
        $container->addCompilerPass(new RepairWarrantyCalculationListenerPass());
    }
}
