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

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Klipper\Module\RepairBundle\Model\RepairModuleInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RepairModuleSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->preUpdate($event);
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();

        if ($object instanceof RepairModuleInterface) {
            if (null !== $object->getDefaultPrice()
                && !\in_array($object->getType(), ['flat_rate', 'coupon'], true)
            ) {
                $object->setDefaultPrice(null);
            }
        }
    }
}
