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
use Klipper\Module\ProductBundle\Model\Traits\PriceListableInterface;
use Klipper\Module\ProductBundle\Price\PriceManagerInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use Klipper\Module\RepairBundle\Model\RepairItemInterface;
use Klipper\Module\RepairBundle\Model\Traits\RepairModuleableInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RepairItemSubscriber implements EventSubscriber
{
    private PriceManagerInterface $priceManager;

    /**
     * @var array[] Array<int[]|string[]>
     */
    private array $updateRepairPrices = [];

    public function __construct(PriceManagerInterface $priceManager)
    {
        $this->priceManager = $priceManager;
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
                $price = null === $object->getWarrantyEndDate()
                        && null !== $object->getUsedCoupon()
                        && null !== $object->getUsedCoupon()->getPrice()
                    ? $object->getUsedCoupon()->getPrice()
                    : 0.0;

                $object->setPrice($price);
                $this->reCalculateRepairPrice($object);
            }

            if (isset($changeSet['warrantyEndDate'])) {
                if (null === $object->getWarrantyEndDate() && null !== $object->getAccount()) {
                    $object->setPrice(null);
                    RepairSubscriber::updatePrice($object, $object->getAccount());
                } else {
                    $object->setPrice(0.0);
                }
            }

            if (isset($changeSet['usedCoupon']) || isset($changeSet['warrantyEndDate'])) {
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

        // Flat rate module type
        if (isset($this->updateRepairPrices['flat_rate'])) {
            foreach ($this->updateRepairPrices['flat_rate'] as $repairId) {
                $updateRepairItemFinalPrices[] = $repairId;
            }

            unset($this->updateRepairPrices['flat_rate']);
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
            $res = $em->createQueryBuilder()
                ->select('r.id as id, MAX(ri.price) as totalPrice')
                ->from(RepairItemInterface::class, 'ri')
                ->leftJoin('ri.repair', 'r')
                ->groupBy('r.id')
                ->where('r.id in (:ids)')
                ->setParameter('ids', $this->updateRepairPrices['operation_highest_price'])
                ->getQuery()
                ->getResult()
            ;

            foreach ($res as $val) {
                $updateRepairPrices[$val['id']] = (float) $val['totalPrice'];
                $updateRepairItemFinalPrices[] = $val['id'];
                $proportionalPriceRepairIds[] = $val['id'];
            }

            unset($this->updateRepairPrices['operation_highest_price']);
        }

        // Sum calculation
        $ids = [];

        foreach ($this->updateRepairPrices as $type => $repairIds) {
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
                        $itemPrice = round((float) $item->getPrice() * $repairPrice / 100, 2);
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

            if (null === $priceList && $account instanceof PriceListableInterface) {
                $edited = true;
                $priceList = $account->getPriceList();
            }

            if (null === $object->getPrice()) {
                $priceEdited = true;
                $object->setPrice($this->priceManager->getProductPrice(
                    $object->getProduct(),
                    $object->getProductCombination(),
                    $priceList,
                    1,
                    $repair->getProduct(),
                    $repair->getProductCombination(),
                    null !== $repair->getProduct() ? $repair->getProduct()->getProductRange() : null
                ));
            } elseif (isset($changeSet['price'])) {
                $priceEdited = true;
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

        if ($account instanceof RepairModuleableInterface && null !== $module = $account->getRepairModule()) {
            if (\in_array($module->getType(), ['flat_rate', 'coupon'], true)) {
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
