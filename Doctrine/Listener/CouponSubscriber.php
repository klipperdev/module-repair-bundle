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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Klipper\Component\CodeGenerator\CodeGenerator;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Module\RepairBundle\Model\CouponInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class CouponSubscriber implements EventSubscriber
{
    private CodeGenerator $generator;

    public function __construct(CodeGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $object) {
            $this->persistCoupon($em, $object, true);
        }

        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->persistCoupon($em, $object);
        }
    }

    private function persistCoupon(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        $uow = $em->getUnitOfWork();

        if ($object instanceof CouponInterface) {
            $meta = $em->getClassMetadata(ClassUtils::getClass($object));
            $edited = false;

            if (null === $object->getReference()) {
                $edited = true;
                $object->setReference($this->generator->generate());
            }

            if ($edited && $create) {
                $uow->recomputeSingleEntityChangeSet($meta, $object);
            }
        }
    }
}
