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
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Klipper\Component\CodeGenerator\CodeGenerator;
use Klipper\Component\DoctrineChoice\Listener\Traits\DoctrineListenerChoiceTrait;
use Klipper\Component\DoctrineExtensionsExtra\Util\ListenerUtil;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Component\Resource\Object\ObjectFactoryInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\PartnerBundle\Model\AccountInterface;
use Klipper\Module\ProductBundle\Model\Traits\PriceListableInterface;
use Klipper\Module\RepairBundle\Model\RepairHistoryInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use Klipper\Module\RepairBundle\Model\RepairModuleProductInterface;
use Klipper\Module\RepairBundle\Model\Traits\DeviceRepairableInterface;
use Klipper\Module\RepairBundle\Model\Traits\RepairModuleableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RepairSubscriber implements EventSubscriber
{
    use DoctrineListenerChoiceTrait;

    private CodeGenerator $generator;

    private ObjectFactoryInterface $objectFactory;

    private TranslatorInterface $translator;

    private array $closedStatues;

    private bool $autoRecreditCoupon = true;

    public function __construct(
        CodeGenerator $generator,
        ObjectFactoryInterface $objectFactory,
        TranslatorInterface $translator,
        array $closedStatues = []
    ) {
        $this->generator = $generator;
        $this->objectFactory = $objectFactory;
        $this->translator = $translator;
        $this->closedStatues = $closedStatues;
    }

    public static function updatePrice(RepairInterface $object, AccountInterface $account): void
    {
        if (null === $object->getPrice()) {
            if (!$object->isUnderContract()) {
                $object->setPrice(0.0);
            } elseif ($account instanceof RepairModuleableInterface && null !== $module = $account->getRepairModule()) {
                if ('annual_flat_rate' === $module->getType()) {
                    $object->setPrice(0.0);
                } elseif ('fix_price' === $module->getType()) {
                    $object->setPrice($module->getDefaultPrice() ?? 0.0);
                } elseif ('coupon' === $module->getType()) {
                    $price = null !== $object->getUsedCoupon() && null !== $object->getUsedCoupon()->getPrice()
                        ? $object->getUsedCoupon()->getPrice()
                        : 0.0;
                    $object->setPrice($price);
                }
            }
        }

        if ($object->hasWarrantyApplied()) {
            $object->setPrice(0.0);
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::onFlush,
        ];
    }

    public function setAutoRecreditCoupon(bool $enabled): void
    {
        $this->autoRecreditCoupon = $enabled;
    }

    public function isAutoRecreditCoupon(): bool
    {
        return $this->autoRecreditCoupon;
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

            if (null === $object->getBatchReference()) {
                $object->setBatchReference($object->getReference());
            }

            $account = $object->getAccount();

            if (null === $object->getPriceList() && null !== $account && $account instanceof PriceListableInterface) {
                $object->setPriceList($account->getPriceList());
            }

            // Price
            static::updatePrice($object, $account);
        }
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $object) {
            $this->updateLastRepairOnDevice($em, $object, true);
            $this->updateProduct($em, $object, true);
            $this->updateAccount($em, $object);
            $this->updateUnderContract($em, $object, true);
            $this->updateStatus($em, $object, true);
            $this->updateReceiptedAt($em, $object, true);
            $this->updateRepairedAt($em, $object);
            $this->updateClosed($em, $object, true);
            $this->updateTrayReference($em, $object);
            $this->updateWarrantyApplied($em, $object);
            $this->updateWarrantyEndDate($em, $object, true);
            $this->updateDeviceStatus($em, $object, true);
            $this->recreditCoupon($em, $object);
            $this->saveRepairHistory($em, $object, true);
        }

        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->validateChangeAccount($em, $object);
            $this->updateLastRepairOnDevice($em, $object);
            $this->updateProduct($em, $object);
            $this->updateAccount($em, $object);
            $this->updateUnderContract($em, $object);
            $this->updateStatus($em, $object);
            $this->updateReceiptedAt($em, $object);
            $this->updateRepairedAt($em, $object);
            $this->updateClosed($em, $object);
            $this->updateTrayReference($em, $object);
            $this->updateWarrantyApplied($em, $object);
            $this->updateWarrantyEndDate($em, $object);
            $this->updateDeviceStatus($em, $object);
            $this->recreditCoupon($em, $object);
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

    private function updateStatus(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof RepairInterface) {
            if ($create) {
                if (null === $object->getStatus()) {
                    $account = $object->getAccount();
                    $repairStatus = null;

                    if ($account instanceof RepairModuleableInterface && null !== ($module = $account->getRepairModule())) {
                        if (!$object->isUnderContract()) {
                            $repairStatus = $module->getDefaultStatusForNoUnderContract();
                        }

                        $repairStatus = $repairStatus ?? $module->getDefaultStatus();
                    }

                    $repairStatus = $repairStatus ?? $this->getChoice($em, 'repair_status', null);

                    if (null !== $repairStatus) {
                        $object->setStatus($repairStatus);
                    }
                }
            } else {
                $this->changeStatus($em, $object, 'swappedToDevice', 'swapped');
                $this->changeStatus($em, $object, 'shipping', 'shipped');
            }
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

        // Automatically change the status only if the repair is repairable
        if (!$object->isUnrepairable()
            && isset($changeSet[$changeSetField])
            && (null === $object->getStatus() || $statusValue !== $object->getStatus()->getValue())
        ) {
            $repairStatus = $this->getChoice($em, 'repair_status', $statusValue);

            if (null !== $repairStatus) {
                $object->setStatus($repairStatus);

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    private function updateLastRepairOnDevice(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof RepairInterface && null !== $device = $object->getDevice()) {
            if ($device instanceof DeviceRepairableInterface) {
                $uow = $em->getUnitOfWork();

                if ($create && null !== $device->getLastRepair() && !$device->getLastRepair()->isClosed()) {
                    ListenerUtil::thrownError($this->translator->trans(
                        'klipper_repair.repair.previous_repair_already_open',
                        [],
                        'validators'
                    ));
                }

                if (null === $object->getPreviousRepair() && null !== $device->getLastRepair() && $object !== $device->getLastRepair()) {
                    $object->setPreviousRepair($device->getLastRepair());

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
                }

                if ($create || null === $device->getLastRepair()) {
                    $device->setLastRepair($object);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($device));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $device);
                }
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

    private function updateAccount(EntityManagerInterface $em, object $object): void
    {
        $uow = $em->getUnitOfWork();

        if ($object instanceof RepairInterface && null !== $device = $object->getDevice()) {
            if (null !== $object->getAccount() && (null === $device->getAccount() || $object->getAccount()->getId() !== $device->getAccount()->getId())) {
                $device->setAccount($object->getAccount());

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($device));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $device);
            }
        }
    }

    private function updateReceiptedAt(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof RepairInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if (($create && null === $object->getReceiptedAt()) || (!$create && !isset($changeSet['receiptedAt']))) {
                $repairStatus = null !== $object->getStatus() ? $object->getStatus()->getValue() : '';

                if (\in_array($repairStatus, ['received'], true)) {
                    $object->setReceiptedAt(new \DateTime());

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
                }
            }
        }
    }

    private function updateRepairedAt(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof RepairInterface) {
            $uow = $em->getUnitOfWork();
            $repairStatus = null !== $object->getStatus() ? $object->getStatus()->getValue() : '';

            if (\in_array($repairStatus, ['repaired'], true)) {
                $object->setRepairedAt(new \DateTime());

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    private function updateClosed(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof RepairInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if ($create || isset($changeSet['status'])) {
                $closed = null === $object->getStatus() || \in_array($object->getStatus()->getValue(), $this->closedStatues, true);
                $object->setClosed($closed);

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function updateUnderContract(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof RepairInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if ($create || isset($changeSet['product']) || isset($changeSet['device'])) {
                $account = $object->getAccount();
                $product = $object->getProduct();

                if (null !== $product && $account instanceof RepairModuleableInterface && null !== $account->getRepairModule()) {
                    $countUnderContract = $em->createQueryBuilder()
                        ->select('count(mp.id)')
                        ->from(RepairModuleProductInterface::class, 'mp')
                        ->join('mp.repairModule', 'rm')
                        ->where('mp.repairModule = :module')
                        ->andWhere('mp.product = :product')
                        ->setParameter('module', $account->getRepairModule())
                        ->setParameter('product', $product)
                        ->getQuery()
                        ->getSingleScalarResult()
                    ;

                    $object->setUnderContract($countUnderContract > 0);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
                }
            }
        }
    }

    private function updateTrayReference(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof RepairInterface) {
            $uow = $em->getUnitOfWork();

            if (null !== $object->getTrayReference() && $object->isClosed()) {
                $object->setTrayReference(null);

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    private function updateWarrantyApplied(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof RepairInterface) {
            $uow = $em->getUnitOfWork();

            // Clean the warranty applied and warranty comment for the first repair of device
            if (($object->hasWarrantyApplied() || null !== $object->getWarrantyComment())
                && null === $object->getPreviousRepair()
            ) {
                $object->setWarrantyApplied(false);
                $object->setWarrantyComment(null);

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    private function updateWarrantyEndDate(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof RepairInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);
            $device = $object->getDevice();

            if ($object->isClosed()) {
                $isReparable = null !== $object->getStatus() && 0 !== strpos($object->getStatus()->getValue(), 'unrepairable_');

                // Define the warranty end date when the repair is closed and repaired
                if (null === $object->getWarrantyEndDate()) {
                    if ($isReparable) {
                        $object->setWarrantyEndDate($this->calculateWarrantyEndDate($object->getAccount(), $this->getRepairStartDate($object)));

                        $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                        $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
                    }
                } elseif (!$isReparable) {
                    // Clean the warranty end date when the repair is closed and unrepaired
                    $object->setWarrantyEndDate(null);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
                }
            } elseif (null !== $object->getWarrantyEndDate()) {
                // Clean the warranty end date when the repair is open
                $object->setWarrantyEndDate(null);

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }

            // Update warranty end date of device with the warranty end date of repair
            if (null !== $device && $device instanceof DeviceRepairableInterface) {
                if (($create && null !== $object->getWarrantyEndDate()) || (!$create && isset($changeSet['warrantyEndDate']))) {
                    $device->setWarrantyEndDate($object->getWarrantyEndDate());

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($device));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $device);
                }
            }
        }
    }

    private function getRepairStartDate(RepairInterface $object): \DateTimeInterface
    {
        if (null !== $object->getReceiptedAt()) {
            return clone $object->getReceiptedAt();
        }

        if (null !== $object->getCreatedAt()) {
            return clone $object->getCreatedAt();
        }

        return new \DateTime();
    }

    private function calculateWarrantyEndDate(object $account, \DateTimeInterface $startDate): ?\DateTimeInterface
    {
        if ($account instanceof RepairModuleableInterface
            && null !== $account->getRepairModule()
            && (int) $account->getRepairModule()->getWarrantyLengthInMonth() > 0
        ) {
            $endDate = clone $startDate;
            $endDate->setTime(0, 0);
            $endDate->modify(sprintf('+ %s months', $account->getRepairModule()->getWarrantyLengthInMonth()));

            return $endDate;
        }

        return null;
    }

    private function updateDeviceStatus(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if (!$object instanceof RepairInterface || null === $object->getDevice()) {
            return;
        }

        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($object);
        $device = $object->getDevice();

        if ($create || isset($changeSet['device'])) {
            if (isset($changeSet['device'][0])) {
                /** @var DeviceInterface $oldDevice */
                $oldDevice = $changeSet['device'][0];
                $statusOperational = $this->getChoice($em, 'device_status', 'operational');

                if (null !== $statusOperational) {
                    $oldDevice->setStatus($statusOperational);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($oldDevice));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $oldDevice);
                }
            }
        }

        if (null === $device->getTerminatedAt()) {
            $repairStatus = null !== $object->getStatus() ? $object->getStatus()->getValue() : '';

            switch ($repairStatus) {
                case 'unrepairable_recycling':
                    $newDeviceStatusValue = 'recycled';

                    break;

                case 'unrepairable_return_to_customer':
                    $newDeviceStatusValue = 'broken_down_return_to_customer';

                    break;

                case 'waiting':
                case 'received_improper':
                    $newDeviceStatusValue = 'broken_down';

                    break;

                case 'shipped':
                    $newDeviceStatusValue = 'operational';

                    break;

                case 'received':
                case 'received_compliant':
                case 'repaired':
                case 'swapped':
                default:
                    $newDeviceStatusValue = 'under_maintenance';

                    break;
            }

            if (null === $device->getStatus() || $newDeviceStatusValue !== $device->getStatus()->getValue()) {
                $newDeviceStatus = $this->getChoice($em, 'device_status', $newDeviceStatusValue);

                if (null !== $newDeviceStatus) {
                    $device->setStatus($newDeviceStatus);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($device));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $device);
                }
            }
        }
    }

    private function recreditCoupon(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof RepairInterface) {
            $uow = $em->getUnitOfWork();

            // Re-credit coupon only if repair status is one of unrepairable statuses
            if ($this->autoRecreditCoupon
                && null !== $object->getUsedCoupon()
                && !$object->getUsedCoupon()->isRecredited()
                && null !== $object->getStatus()
                && 0 === strpos($object->getStatus()->getValue(), 'unrepairable_')
            ) {
                $coupon = $object->getUsedCoupon();
                $newCoupon = clone $coupon;
                $newCoupon->setRecreditedCoupon($coupon);
                $newCoupon->setPrice(0);

                $em->persist($newCoupon);
                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($newCoupon));
                $uow->computeChangeSet($classMetadata, $newCoupon);
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
}
