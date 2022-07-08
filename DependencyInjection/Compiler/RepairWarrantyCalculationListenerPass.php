<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\RepairBundle\DependencyInjection\Compiler;

use Klipper\Module\RepairBundle\Doctrine\Listener\RepairSubscriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RepairWarrantyCalculationListenerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(RepairSubscriber::class)) {
            return;
        }

        $def = $container->getDefinition(RepairSubscriber::class);

        foreach ($this->findAndSortTaggedServices('klipper_module_repair.warranty_calculation_listener', $container) as $service) {
            $def->addMethodCall('addWarrantyCalculationListener', [$service]);
        }
    }
}
