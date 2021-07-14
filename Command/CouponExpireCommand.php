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
use Klipper\Component\DoctrineChoice\ChoiceManagerInterface;
use Klipper\Module\RepairBundle\Exception\RuntimeException;
use Klipper\Module\RepairBundle\Model\CouponInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class CouponExpireCommand extends Command
{
    private EntityManagerInterface $em;

    private ChoiceManagerInterface $choiceManager;

    public function __construct(EntityManagerInterface $em, ChoiceManagerInterface $choiceManager)
    {
        parent::__construct();

        $this->em = $em;
        $this->choiceManager = $choiceManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('coupon:expire')
            ->setDescription('Expire all coupons that validity dates are outpassed')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->comment('Expire all coupons that validity dates are outpassed');

        $this->expireCoupons();

        $io->success('Expired coupons were successfully updated');

        return 0;
    }

    private function expireCoupons(): void
    {
        $status = $this->choiceManager->getChoice('coupon_status', 'expired');

        if (null === $status) {
            throw new RuntimeException('The doctrine choice "expired" for "coupon_status" does not exist');
        }

        $this->em->createQueryBuilder()
            ->update(CouponInterface::class, 'c')
            ->set('c.status', ':status')
            ->where('c.usedByRepair is null')
            ->andWhere('c.validUntil <= CURRENT_TIMESTAMP()')
            ->andWhere('c.status != :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->execute()
        ;
    }
}
