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
use Klipper\Module\RepairBundle\Model\RepairBreakdownInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RepairBreakdownSubscriber implements EventSubscriber
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

        if ($object instanceof RepairBreakdownInterface) {
            if (!$object->isRepairImpossibleInitialized()) {
                $val = null !== $object->getBreakdown() ? $object->getBreakdown()->isRepairImpossible() : false;
                $object->setRepairImpossible($val);
            }
        }
    }
}
