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
use Klipper\Component\DoctrineExtensionsExtra\Util\ListenerUtil;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Component\Resource\Object\ObjectFactoryInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\ProductBundle\Model\Traits\PriceListableInterface;
use Klipper\Module\RepairBundle\Model\RepairHistoryInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use Klipper\Module\RepairBundle\Model\Traits\RepairModuleableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RepairSubscriber implements EventSubscriber
{
    private CodeGenerator $generator;

    private ObjectFactoryInterface $objectFactory;

    private TranslatorInterface $translator;

    private array $statusChoices = [];

    private ?array $deviceStatusChoices = null;

    public function __construct(
        CodeGenerator $generator,
        ObjectFactoryInterface $objectFactory,
        TranslatorInterface $translator
    ) {
        $this->generator = $generator;
        $this->objectFactory = $objectFactory;
        $this->translator = $translator;
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
            $this->updateProduct($em, $object, true);
            $this->updateDeviceStatus($em, $object, true);
            $this->saveRepairHistory($em, $object, true);
        }

        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->validateChangeAccount($em, $object);
            $this->updateProduct($em, $object);
            $this->updateStatus($em, $object);
            $this->updateDeviceStatus($em, $object);
            $this->saveRepairHistory($em, $object);
        }
    }

    private function validateChangeAccount(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof RepairInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if (isset($changeSet['account']) && null !== $object->getUsedCoupon()) {
                ListenerUtil::thrownError($this->translator->trans(
                    'klipper_repair.repair.account_cannot_be_change_with_coupon',
                    [],
                    'validators'
                ));
            }
        }
    }

    private function updateStatus(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof RepairInterface) {
            $this->changeStatus($em, $object, 'shipping', 'shipped');
            $this->changeStatus($em, $object, 'swappedToDevice', 'swapped');
        }
    }

    private function changeStatus(
        EntityManagerInterface $em,
        RepairInterface $object,
        string $changeSetField,
        string $statusValue
    ): void {
        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($object);

        if (isset($changeSet[$changeSetField]) && (null === $object->getStatus() || $statusValue !== $object->getStatus()->getValue())) {
            if (!\array_key_exists($changeSetField, $this->statusChoices)) {
                $this->statusChoices[$changeSetField] = $em->getRepository(ChoiceInterface::class)->findOneBy([
                    'type' => 'repair_status',
                    'value' => $statusValue,
                ]) ?? false;
            }

            if (isset($this->statusChoices[$changeSetField]) && $this->statusChoices[$changeSetField] instanceof ChoiceInterface) {
                $object->setStatus($this->statusChoices[$changeSetField]);

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    private function updateProduct(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($object);

        if ($object instanceof RepairInterface && null !== $device = $object->getDevice()) {
            if (null !== $device->getProduct() && ($create || (isset($changeSet['device']) && null !== $changeSet['device'][1]))) {
                $object->setProduct($device->getProduct());
                $object->setProductCombination($device->getProductCombination());
            }
        }
    }

    private function updateDeviceStatus(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if (!$object instanceof RepairInterface || null === $object->getDevice()) {
            return;
        }

        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($object);

        if ($create || isset($changeSet['device'])) {
            if (isset($changeSet['device'][0])) {
                /** @var DeviceInterface $oldDevice */
                $oldDevice = $changeSet['device'][0];
                $statusOperational = $this->getDeviceStatus($em, 'operational');

                if (null !== $statusOperational) {
                    $oldDevice->setStatus($statusOperational);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($oldDevice));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $oldDevice);
                }
            }
        }
    }

    private function saveRepairHistory(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof RepairInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if ($create || isset($changeSet['status']) || isset($changeSet['swappedToDevice']) || isset($changeSet['shipping'])) {
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

                    if (null !== $object->getDevice()) {
                        $history->setPreviousDevice($object->getDevice());
                    } elseif (isset($changeSet['swappedToDevice'])) {
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

    private function getDeviceStatus(EntityManagerInterface $em, string $value): ?ChoiceInterface
    {
        if (null === $this->deviceStatusChoices) {
            $this->deviceStatusChoices = [];
            $res = $em->getRepository(ChoiceInterface::class)->findBy([
                'type' => 'device_status',
            ]);

            /** @var ChoiceInterface $item */
            foreach ($res as $item) {
                $this->deviceStatusChoices[$item->getValue()] = $item;
            }
        }

        return $this->deviceStatusChoices[$value] ?? null;
    }
}
