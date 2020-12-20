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
     * @var int[]|string[]
     */
    private array $updateRepairPrices = [];

    public function __construct(PriceManagerInterface $priceManager)
    {
        $this->priceManager = $priceManager;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
            Events::postFlush,
        ];
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
        $updatePrices = [];

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
                $updatePrices[$val['id']] = (float) $val['totalPrice'];
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
                $updatePrices[$val['id']] = (float) $val['totalPrice'];
            }
        }

        // Update repair prices
        if (\count($updatePrices) > 0) {
            foreach ($updatePrices as $id => $price) {
                $em->createQueryBuilder()
                    ->update(RepairInterface::class, 'r')
                    ->set('r.price', ':price')
                    ->where('r.id = :id')
                    ->setParameter('id', $id)
                    ->setParameter('price', $price)
                    ->getQuery()
                    ->execute()
                ;
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
            $edited = false;

            $account = $repair->getAccount();
            $priceList = $repair->getPriceList();

            if (null === $priceList && $account instanceof PriceListableInterface) {
                $priceList = $account->getPriceList();
            }

            if (null === $object->getPrice()) {
                $edited = true;
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
                $edited = true;
            }

            if ($edited && $create) {
                $uow->recomputeSingleEntityChangeSet($meta, $object);
            }

            if ($edited) {
                $this->reCalculateRepairPrice($repair);
            }
        }
    }

    private function getPriceCalculationType(RepairInterface $repair): string
    {
        $account = $repair->getAccount();
        $type = 'sum';

        if ($account instanceof RepairModuleableInterface && null !== $module = $account->getRepairModule()) {
            if (null !== $module->getPriceCalculation()) {
                $type = $module->getPriceCalculation();
            }
        }

        return $type;
    }

    private function reCalculateRepairPrice(RepairInterface $repair): void
    {
        $this->updateRepairPrices[$this->getPriceCalculationType($repair)][] = $repair->getId();
    }
}
