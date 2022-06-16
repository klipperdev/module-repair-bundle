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
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Module\RepairBundle\Model\RepairBreakdownInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;

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
            Events::onFlush,
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

    public function onFlush(OnFlushEventArgs $event): void
    {
        $this->updateRepairUnrepairableField($event);
    }

    public function updateRepairUnrepairableField(OnFlushEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        /** @var RepairInterface[] $repairs */
        $repairs = [];
        $deletedRepairBreakdowns = [];

        foreach ($uow->getScheduledEntityInsertions() as $object) {
            if ($object instanceof RepairBreakdownInterface && null !== $object->getRepair()) {
                $repairs[$object->getRepair()->getId()] = $object->getRepair();

                if (!$object->getRepair()->getRepairBreakdowns()->contains($object)) {
                    $object->getRepair()->getRepairBreakdowns()->add($object);
                }
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $object) {
            if ($object instanceof RepairBreakdownInterface && null !== $object->getRepair()) {
                $repairs[$object->getRepair()->getId()] = $object->getRepair();
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $object) {
            if ($object instanceof RepairBreakdownInterface && null !== $object->getRepair()) {
                $repairs[$object->getRepair()->getId()] = $object->getRepair();
                $object->getRepair()->getRepairBreakdowns()->removeElement($object);
                $deletedRepairBreakdowns[] = $object;
            }
        }

        foreach ($repairs as $repair) {
            $unrepairable = false;

            foreach ($repair->getRepairBreakdowns() as $repairBreakdown) {
                if ($repairBreakdown->isRepairImpossible() && !\in_array($repairBreakdown, $deletedRepairBreakdowns, true)) {
                    $unrepairable = true;

                    break;
                }
            }

            if ($unrepairable !== $repair->isUnrepairable() && !$uow->isScheduledForDelete($repair)) {
                $repair->setUnrepairable($unrepairable);

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($repair));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $repair);
            }
        }
    }
}
