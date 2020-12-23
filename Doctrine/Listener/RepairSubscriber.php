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
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Klipper\Component\CodeGenerator\CodeGenerator;
use Klipper\Component\DoctrineChoice\Model\ChoiceInterface;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Component\Resource\Object\ObjectFactoryInterface;
use Klipper\Module\ProductBundle\Model\Traits\PriceListableInterface;
use Klipper\Module\RepairBundle\Model\RepairHistoryInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use Klipper\Module\RepairBundle\Model\Traits\RepairModuleableInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RepairSubscriber implements EventSubscriber
{
    private CodeGenerator $generator;

    private ObjectFactoryInterface $objectFactory;

    /**
     * @var null|bool|ChoiceInterface
     */
    private $shippedChoice;

    public function __construct(
        CodeGenerator $generator,
        ObjectFactoryInterface $objectFactory
    ) {
        $this->generator = $generator;
        $this->objectFactory = $objectFactory;
    }

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

        if ($object instanceof RepairInterface) {
            if (null === $object->getReference()) {
                $object->setReference($this->generator->generate());
            }

            $account = $object->getAccount();

            if (null === $object->getPriceList() && null !== $account && $account instanceof PriceListableInterface) {
                $object->setPriceList($account->getPriceList());
            }

            // Price
            if (null === $object->getPrice()) {
                if ($account instanceof RepairModuleableInterface && null !== $module = $account->getRepairModule()) {
                    if ('flat_rate' === $module->getType()) {
                        $object->setPrice($module->getDefaultPrice() ?? 0.0);
                    } elseif ('coupon' === $module->getType()) {
                        $price = null !== $object->getUsedCoupon() && null !== $object->getUsedCoupon()->getPrice()
                            ? $object->getUsedCoupon()->getPrice()
                            : 0.0;
                        $object->setPrice($price);
                    }
                }
            }
        }
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $object) {
            $this->saveRepairHistory($em, $object, true);
        }

        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->updateStatus($em, $object);
            $this->saveRepairHistory($em, $object);
        }
    }

    private function updateStatus(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof RepairInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if (isset($changeSet['shipping']) && (null === $object->getStatus() || 'shipped' !== $object->getStatus()->getValue())) {
                if (null === $this->shippedChoice) {
                    $this->shippedChoice = $em->getRepository(ChoiceInterface::class)->findOneBy([
                        'type' => 'repair_status',
                        'value' => 'shipped',
                    ]) ?? false;
                }

                if ($this->shippedChoice) {
                    $object->setStatus($this->shippedChoice);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
                }
            }
        }
    }

    private function saveRepairHistory(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof RepairInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if ($create || isset($changeSet['status'])) {
                /** @var RepairHistoryInterface $history */
                $history = $this->objectFactory->create(RepairHistoryInterface::class);
                $history->setRepair($object);
                $history->setPublic(true);
                $history->setNewStatus($object->getStatus());

                if (isset($changeSet['status'])) {
                    $history->setPreviousStatus($changeSet['status'][0]);
                }

                if (($create && null !== $object->getSwappedToDevice()) || isset($changeSet['swappedToDevice'])) {
                    $history->setSwap(true);
                    $history->setNewDevice($object->getSwappedToDevice());

                    if (isset($changeSet['swappedToDevice'])) {
                        $history->setPreviousDevice($changeSet['swappedToDevice'][0]);
                    }
                }

                if (($create && null !== $object->getShipping()) || isset($changeSet['shipping'])) {
                    if (isset($changeSet['shipping'])) {
                        $history->setShipping($object->getShipping());
                    }
                }

                $em->persist($history);
                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($history));
                $uow->computeChangeSet($classMetadata, $history);
            }
        }
    }
}
