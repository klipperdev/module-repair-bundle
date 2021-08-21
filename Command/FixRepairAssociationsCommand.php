<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\RepairBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class FixRepairAssociationsCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();

        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->setName('repair:fix-previous-associations')
            ->setDescription('Validate et fix the repair associations of previous repairs and device last repairs')
        ;
    }

    /**
     * @throws
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->fixDeviceLastRepairAssociations($io);
        $this->fixRepairEmptyPreviousRepairAssociations($io);

        return 0;
    }

    /**
     * @throws
     */
    private function fixDeviceLastRepairAssociations(SymfonyStyle $io): void
    {
        $io->comment('Fix the last repair associations of devices');
        $endResult = false;
        $fixed = false;

        while (!$endResult) {
            /** @var array[] $res */
            $res = $this->em->createQueryBuilder()
                ->select('d.id as deviceId')
                ->addSelect('FIRST(SELECT subR FROM App:Repair subR WHERE subR.device = d.id ORDER BY subR.receiptedAt DESC, subR.id DESC) as calculatedLastRepairId')
                ->from(DeviceInterface::class, 'd')
                ->leftJoin('d.lastRepair', 'lr')
                ->where('lr.id != FIRST(SELECT subR2 FROM App:Repair subR2 WHERE subR2.device = d.id ORDER BY subR2.receiptedAt DESC, subR2.id DESC)')
                ->setMaxResults(100)
                ->getQuery()
                ->getArrayResult()
            ;

            $endResult = empty($res);

            if (!empty($res)) {
                $this->em->beginTransaction();

                try {
                    foreach ($res as $item) {
                        $this->em->createQueryBuilder()
                            ->update(DeviceInterface::class, 'd')
                            ->set('d.lastRepair', ':lastRepairId')
                            ->where('d.id = :id')
                            ->setParameter('lastRepairId', $item['calculatedLastRepairId'])
                            ->setParameter('id', $item['deviceId'])
                            ->getQuery()
                            ->execute()
                        ;
                    }

                    $this->em->commit();
                    $fixed = true;
                } catch (\Throwable $e) {
                    $this->em->rollback();

                    throw $e;
                }
            }
        }

        $io->success(
            $fixed
            ? 'Last repair associations of devices were successfully fixed'
            : 'Last repair associations of devices are valid'
        );
    }

    /**
     * @throws
     */
    private function fixRepairEmptyPreviousRepairAssociations(SymfonyStyle $io): void
    {
        $io->comment('Fix the previous repair associations of repairs');
        $endResult = false;
        $fixed = false;

        while (!$endResult) {
            $findQb = $this->em->createQueryBuilder()
                ->select('r.id as repairId')
                ->addSelect('d.id as deviceId')
                ->addSelect('FIRST(SELECT r2.id FROM App:Repair r2 WHERE r2.device = r.device AND r2.id != r.id AND r2.receiptedAt <= r.receiptedAt ORDER BY r2.receiptedAt DESC, r2.id DESC) as calculatedPreviousRepairId')
                ->addSelect('(SELECT COUNT(r3.id) FROM App:Repair r3 WHERE r3.device = r.device) as countRepairsOfDevice')
                ->from(RepairInterface::class, 'r')
                ->leftJoin('r.previousRepair', 'rpr')
                ->join('r.device', 'd')
                ->where('CASE WHEN rpr.id IS NULL THEN :nullStr ELSE rpr.id END != CASE WHEN FIRST(SELECT r4.id FROM App:Repair r4 WHERE r4.device = r.device AND r4.id != r.id AND r4.receiptedAt <= r.receiptedAt ORDER BY r4.receiptedAt DESC, r4.id DESC) IS NULL THEN :nullStr ELSE FIRST(SELECT r5.id FROM App:Repair r5 WHERE r5.device = r.device AND r5.id != r.id AND r5.receiptedAt <= r.receiptedAt ORDER BY r5.receiptedAt DESC, r5.id DESC) END')
                ->orderBy('r.device', 'ASC')
                ->addOrderBy('r.id', 'ASC')
                ->setMaxResults(100)
                ->setParameter('nullStr', 'NULL')
            ;

            /** @var array[] $res */
            $res = $findQb->getQuery()->getArrayResult();

            $endResult = empty($res);
            $deviceTreatedIds = [];

            if (!empty($res)) {
                foreach ($res as $item) {
                    // Skip already treated device in batch
                    if (\in_array($item['deviceId'], $deviceTreatedIds, true)) {
                        continue;
                    }

                    $deviceTreatedIds[] = $item['deviceId'];

                    $this->em->createQueryBuilder()
                        ->update(RepairInterface::class, 'r')
                        ->set('r.previousRepair', ':previousRepairId')
                        ->where('r.device = :deviceId')
                        ->setParameter('previousRepairId', null)
                        ->setParameter('deviceId', $item['deviceId'])
                        ->getQuery()
                        ->execute()
                    ;

                    /** @var array[] $res */
                    $resForDevice = $findQb
                        ->andWhere('r.device = :deviceId')
                        ->setParameter('deviceId', $item['deviceId'])
                        ->getQuery()
                        ->getArrayResult()
                    ;

                    if (!empty($resForDevice)) {
                        $this->em->beginTransaction();

                        try {
                            foreach ($resForDevice as $itemForDevice) {
                                $this->em->createQueryBuilder()
                                    ->update(RepairInterface::class, 'r')
                                    ->set('r.previousRepair', ':previousRepairId')
                                    ->where('r.id = :id')
                                    ->setParameter('previousRepairId', $itemForDevice['calculatedPreviousRepairId'])
                                    ->setParameter('id', $itemForDevice['repairId'])
                                    ->getQuery()
                                    ->execute()
                                ;
                            }

                            $this->em->commit();
                            $fixed = true;
                        } catch (\Throwable $e) {
                            $this->em->rollback();

                            throw $e;
                        }
                    }
                }
            }
        }

        $io->success(
            $fixed
            ? 'Previous repair associations of repairs were successfully fixed'
            : 'Previous repair associations of repairs are valid'
        );
    }
}
