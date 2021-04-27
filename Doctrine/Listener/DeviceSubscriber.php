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
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use Klipper\Module\RepairBundle\Model\Traits\DeviceRepairableInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class DeviceSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate,
        ];
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $object = $event->getObject();

        if ($object instanceof DeviceInterface && $object instanceof DeviceRepairableInterface) {
            $changeSet = $uow->getEntityChangeSet($object);

            if (isset($changeSet['product'])
                && null !== $object->getProduct()
                && null !== $object->getLastRepair()
                && $object->getProduct() !== $object->getLastRepair()->getProduct()
            ) {
                $lastRepair = $object->getLastRepair();
                $lastRepair->setProduct($object->getProduct());
                $repairMeta = $em->getClassMetadata(ClassUtils::getClass($lastRepair));
                $uow->recomputeSingleEntityChangeSet($repairMeta, $lastRepair);
            }
        } elseif ($object instanceof RepairInterface) {
            $changeSet = $uow->getEntityChangeSet($object);

            if (isset($changeSet['product'])
                && null !== $object->getProduct()
                && null !== $object->getDevice()
                && $object->getProduct() !== $object->getDevice()->getProduct()
            ) {
                $device = $object->getDevice();
                $device->setProduct($object->getProduct());
                $repairMeta = $em->getClassMetadata(ClassUtils::getClass($device));
                $uow->recomputeSingleEntityChangeSet($repairMeta, $device);
            }
        }
    }
}
