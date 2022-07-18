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
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Component\Resource\Object\ObjectFactoryInterface;
use Klipper\Component\Security\Model\UserInterface;
use Klipper\Module\ProductBundle\Model\Traits\PriceListableInterface;
use Klipper\Module\ProductBundle\Price\PriceManagerInterface;
use Klipper\Module\RepairBundle\Model\RepairBreakdownInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use Klipper\Module\RepairBundle\Model\RepairItemInterface;
use Klipper\Module\RepairBundle\Model\Traits\ProductBreakdownableInterface;
use Klipper\Module\RepairBundle\Model\Traits\RepairModuleableInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RepairItemSubscriber implements EventSubscriber
{
    private PriceManagerInterface $priceManager;

    private ObjectFactoryInterface $objectFactory;

    private TokenStorageInterface $tokenStorage;

    /**
     * @var RepairPriceListenerInterface[]
     */
    private array $repairPriceListeners = [];

    /**
     * @var array[] Array<int[]|string[]>
     */
    private array $updateRepairPrices = [];

    public function __construct(
        PriceManagerInterface $priceManager,
        ObjectFactoryInterface $objectFactory,
        TokenStorageInterface $tokenStorage
    ) {
        $this->priceManager = $priceManager;
        $this->objectFactory = $objectFactory;
        $this->tokenStorage = $tokenStorage;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::onFlush,
            Events::postFlush,
        ];
    }

    public function addRepairPriceListener(RepairPriceListenerInterface $repairPriceListener): void
    {
        $this->repairPriceListeners[] = $repairPriceListener;
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->preUpdate($event);
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();
        $uow = $event->getEntityManager()->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($object);

        if ($object instanceof RepairInterface) {
            // Price
            if (isset($changeSet['usedCoupon'])) {
                $price = !$object->hasWarrantyApplied()
                        && null !== $object->getUsedCoupon()
                        && null !== $object->getUsedCoupon()->getPrice()
                    ? $object->getUsedCoupon()->getPrice()
                    : 0.0;

                $object->setPrice($price);
                $this->reCalculateRepairPrice($object);
            }

            if (isset($changeSet['warrantyApplied'])) {
                if (!$object->hasWarrantyApplied() && null !== $object->getAccount()) {
                    $object->setPrice(null);
                    RepairSubscriber::updatePrice($object, $object->getAccount());
                } else {
                    $object->setPrice(0.0);
                }
            }

            if (isset($changeSet['usedCoupon']) || isset($changeSet['warrantyApplied'])) {
                $this->reCalculateRepairPrice($object);
            }
        }
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $object) {
            $this->persistRepairItem($em, $object, true);
        }

        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->persistRepairItem($em, $object);
        }

        foreach ($uow->getScheduledEntityDeletions() as $object) {
            if ($object instanceof RepairItemInterface && null !== $repair = $object->getRepair()) {
                $this->reCalculateRepairPrice($repair);
            }
        }
    }

    /**
     * @throws
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();
        $updateRepairPrices = [];
        $updateRepairItemFinalPrices = [];
        $proportionalPriceRepairIds = [];

        // Warranty type
        if (isset($this->updateRepairPrices['warranty'])) {
            foreach ($this->updateRepairPrices['warranty'] as $repairId) {
                $updateRepairPrices[$repairId] = 0.0;
                $updateRepairItemFinalPrices[] = $repairId;
            }

            unset($this->updateRepairPrices['warranty']);
        }

        // Annual flat rate module type
        if (isset($this->updateRepairPrices['annual_flat_rate'])) {
            foreach ($this->updateRepairPrices['annual_flat_rate'] as $repairId) {
                $updateRepairItemFinalPrices[] = $repairId;
            }

            unset($this->updateRepairPrices['annual_flat_rate']);
        }

        // Flat rate module type
        if (isset($this->updateRepairPrices['fix_price'])) {
            foreach ($this->updateRepairPrices['fix_price'] as $repairId) {
                $updateRepairItemFinalPrices[] = $repairId;
            }

            unset($this->updateRepairPrices['fix_price']);
        }

        // Coupon module type
        if (isset($this->updateRepairPrices['coupon'])) {
            foreach ($this->updateRepairPrices['coupon'] as $repairId) {
                $updateRepairItemFinalPrices[] = $repairId;
            }

            unset($this->updateRepairPrices['coupon']);
        }

        // Operation highest price calculation
        if (isset($this->updateRepairPrices['operation_highest_price'])) {
            $resMax = $em->createQueryBuilder()
                ->select('r.id as id, MAX(ri.price) as totalPrice')
                ->from(RepairItemInterface::class, 'ri')
                ->leftJoin('ri.repair', 'r')
                ->groupBy('r.id')
                ->where('r.id in (:ids)')
                ->andWhere('ri.extra = false')
                ->setParameter('ids', $this->updateRepairPrices['operation_highest_price'])
                ->getQuery()
                ->getResult()
            ;

            foreach ($resMax as $val) {
                $updateRepairPrices[$val['id']] = (float) $val['totalPrice'];
                $updateRepairItemFinalPrices[] = $val['id'];
                $proportionalPriceRepairIds[] = $val['id'];
            }

            $resSum = $em->createQueryBuilder()
                ->select('r.id as id, SUM(ri.price) as totalPrice')
                ->from(RepairItemInterface::class, 'ri')
                ->leftJoin('ri.repair', 'r')
                ->groupBy('r.id')
                ->where('r.id in (:ids)')
                ->andWhere('ri.extra = true')
                ->setParameter('ids', $this->updateRepairPrices['operation_highest_price'])
                ->getQuery()
                ->getResult()
            ;

            foreach ($resSum as $val) {
                $updateRepairPrices[$val['id']] = ($updateRepairPrices[$val['id']] ?? 0) + (float) $val['totalPrice'];
                $updateRepairItemFinalPrices[] = $val['id'];
                $updateRepairItemFinalPrices[] = $val['id'];
            }

            unset($this->updateRepairPrices['operation_highest_price']);
        }

        // Sum calculation
        $ids = [];

        foreach ($this->updateRepairPrices as $repairIds) {
            $ids = array_merge($ids, $repairIds);
        }

        if (\count($ids) > 0) {
            $res = $em->createQueryBuilder()
                ->select('r.id as id, SUM(ri.price) as totalPrice')
                ->from(RepairItemInterface::class, 'ri')
                ->leftJoin('ri.repair', 'r')
                ->groupBy('r.id')
                ->where('r.id in (:ids)')
                ->setParameter('ids', array_unique($ids))
                ->getQuery()
                ->getResult()
            ;

            foreach ($res as $val) {
                $updateRepairPrices[$val['id']] = (float) $val['totalPrice'];
                $updateRepairItemFinalPrices[] = $val['id'];
                $proportionalPriceRepairIds[] = $val['id'];
            }
        }

        // check if the repair has no more items to set price to 0
        foreach ($ids as $id) {
            if (!isset($updateRepairPrices[$id])) {
                $updateRepairPrices[$id] = 0;
            }
        }

        // Update repair prices
        if (\count($updateRepairPrices) > 0) {
            foreach ($updateRepairPrices as $id => $repairPrice) {
                $em->createQueryBuilder()
                    ->update(RepairInterface::class, 'r')
                    ->set('r.price', ':price')
                    ->where('r.id = :id')
                    ->setParameter('id', $id)
                    ->setParameter('price', $repairPrice)
                    ->getQuery()
                    ->execute()
                ;
            }
        }

        // Update repair item final prices
        if (\count($updateRepairItemFinalPrices) > 0) {
            $res = $em->createQueryBuilder()
                ->select('r', 'ris')
                ->from(RepairInterface::class, 'r')
                ->leftJoin('r.repairItems', 'ris')
                ->where('r.id in (:ids)')
                ->setParameter('ids', $updateRepairItemFinalPrices)
                ->getQuery()
                ->getResult()
            ;

            /** @var RepairInterface $repair */
            foreach ($res as $repair) {
                $repairPrice = (float) (isset($updateRepairPrices[$repair->getId()])
                    ? $updateRepairPrices[$repair->getId()]
                    : $repair->getPrice());

                /** @var RepairItemInterface[] $items */
                $items = $repair->getRepairItems()->toArray();
                $countItems = \count($items);
                $itemPrice = $countItems > 0 ? round($repairPrice / $countItems, 2) : $repairPrice;
                $isProportionalPrice = \in_array($repair->getId(), $proportionalPriceRepairIds, true);
                $sum = 0;

                foreach ($items as $i => $item) {
                    if ($isProportionalPrice) {
                        $ipPrice = (float) $item->getPrice();
                        $ripPrice = 0.0 !== $repairPrice ? $repairPrice : 1;
                        $itemPrice = round(($ipPrice / $ripPrice) * $ripPrice, 2);
                    }

                    $item->setFinalPrice($itemPrice);
                    $sum += (float) $item->getFinalPrice();

                    // Add round difference on the last item
                    if ($i === ($countItems - 1) && $sum !== $repairPrice) {
                        $item->setFinalPrice((float) $item->getFinalPrice() + ($repairPrice - $sum));
                    }

                    // Do not the persist/flush in postFlush event
                    $em->createQueryBuilder()
                        ->update(RepairItemInterface::class, 'ri')
                        ->set('ri.finalPrice', ':price')
                        ->where('ri.id = :id')
                        ->setParameter('id', $item->getId())
                        ->setParameter('price', (float) $item->getFinalPrice())
                        ->getQuery()
                        ->execute()
                    ;
                }
            }
        }

        // Update repair prices
        if (\count($this->repairPriceListeners) > 0 && \count($updateRepairPrices) > 0) {
            foreach ($this->repairPriceListeners as $repairPriceListener) {
                $repairPriceListener->onUpdate($em, $updateRepairPrices);
            }
        }

        $this->updateRepairPrices = [];
    }

    private function persistRepairItem(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        $uow = $em->getUnitOfWork();

        if ($object instanceof RepairItemInterface && null !== $repair = $object->getRepair()) {
            $meta = $em->getClassMetadata(ClassUtils::getClass($object));
            $changeSet = $uow->getEntityChangeSet($object);
            $priceEdited = false;
            $edited = false;

            $account = $repair->getAccount();
            $priceList = $repair->getPriceList();
            $product = $object->getProduct();

            if (null === $priceList && $account instanceof PriceListableInterface) {
                $edited = true;
                $priceList = $account->getPriceList();
            }

            if (null === $object->getPrice()) {
                $priceEdited = true;
                $price = $this->priceManager->getProductPrice(
                    $product,
                    $object->getProductCombination(),
                    $priceList,
                    1,
                    $repair->getProduct(),
                    $repair->getProductCombination(),
                    null !== $repair->getProduct() ? $repair->getProduct()->getProductFamily() : null,
                    null !== $repair->getProduct() ? $repair->getProduct()->getProductRange() : null
                );
                $object->setPrice($price->getPrice());
                $object->setExtra($price->isExtra());
            } elseif (isset($changeSet['price']) || isset($changeSet['extra'])) {
                $priceEdited = true;
            }

            // Add associated breakdown
            if ($create && $product instanceof ProductBreakdownableInterface && null !== ($breakdown = $product->getOperationBreakdown())) {
                $breakdownExist = false;

                foreach ($repair->getRepairBreakdowns() as $repairBreakdown) {
                    if ($breakdown === $repairBreakdown->getBreakdown()) {
                        $breakdownExist = true;

                        break;
                    }
                }

                if (!$breakdownExist) {
                    /** @var RepairBreakdownInterface $operationBreakdown */
                    $operationBreakdown = $this->objectFactory->create(RepairBreakdownInterface::class);
                    $operationBreakdown->setRepair($repair);
                    $operationBreakdown->setBreakdown($breakdown);
                    $operationBreakdown->setRepairImpossible($breakdown->isRepairImpossible());
                    $repair->getRepairBreakdowns()->add($operationBreakdown);

                    $em->persist($operationBreakdown);
                    $repairClassMeta = $em->getClassMetadata(ClassUtils::getClass($operationBreakdown));
                    $uow->computeChangeSet($repairClassMeta, $operationBreakdown);
                }
            }

            // Add Repairer user in repair
            if ($create && null === $repair->getRepairer()) {
                $token = $this->tokenStorage->getToken();
                $user = $token?->getUser();

                if ($user instanceof UserInterface) {
                    $repair->setRepairer($user);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($repair));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $repair);
                }
            }

            if (($edited || $priceEdited) && $create) {
                $uow->recomputeSingleEntityChangeSet($meta, $object);
            }

            if ($priceEdited || $create) {
                $this->reCalculateRepairPrice($repair);
            }
        }
    }

    private function getPriceCalculationType(RepairInterface $repair): string
    {
        $account = $repair->getAccount();
        $type = 'sum';

        if ($repair->hasWarrantyApplied()) {
            return 'warranty';
        }

        if ($account instanceof RepairModuleableInterface && null !== $module = $account->getRepairModule()) {
            if (\in_array($module->getType(), ['annual_flat_rate', 'fix_price', 'coupon'], true)) {
                $type = $module->getType();
            } elseif (null !== $module->getPriceCalculation()) {
                $type = $module->getPriceCalculation();
            }
        }

        return $type;
    }

    private function reCalculateRepairPrice(RepairInterface $repair): void
    {
        $this->updateRepairPrices[$this->getPriceCalculationType($repair)][] = $repair->getId();
        $this->updateRepairPrices[$this->getPriceCalculationType($repair)] = array_unique($this->updateRepairPrices[$this->getPriceCalculationType($repair)]);
    }
}
