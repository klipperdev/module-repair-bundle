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
use Klipper\Component\DoctrineChoice\Listener\Traits\DoctrineListenerChoiceTrait;
use Klipper\Component\DoctrineExtensionsExtra\Util\ListenerUtil;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Module\RepairBundle\Model\CouponInterface;
use Klipper\Module\RepairBundle\Model\Traits\RepairModuleableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class CouponSubscriber implements EventSubscriber
{
    use DoctrineListenerChoiceTrait;

    private CodeGenerator $generator;

    private TranslatorInterface $translator;

    public function __construct(CodeGenerator $generator, TranslatorInterface $translator)
    {
        $this->generator = $generator;
        $this->translator = $translator;
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
            $changeSet = $uow->getEntityChangeSet($object);
            $edited = false;

            // Update reference
            if (null === $object->getReference()) {
                $edited = true;
                $object->setReference($this->generator->generate());
            }

            // Update price
            if (null === $object->getPrice()) {
                $account = $object->getAccount();

                if ($account instanceof RepairModuleableInterface
                    && $account->getRepairModule()
                    && 'coupon' === $account->getRepairModule()->getType()
                    && null !== $account->getRepairModule()->getDefaultPrice()
                ) {
                    $edited = true;
                    $object->setPrice($account->getRepairModule()->getDefaultPrice());
                } else {
                    ListenerUtil::thrownError($this->translator->trans(
                        'klipper_repair.coupon.invalid_empty_price',
                        [],
                        'validators'
                    ), $object, 'price');
                }
            }

            //Update valid until
            if (null === $object->getValidUntil()) {
                $account = $object->getAccount();
                $validityPeriod = 1;
                $edited = true;

                if ($account instanceof RepairModuleableInterface
                    && $account->getRepairModule()
                    && null !== $account->getRepairModule()->getDefaultCouponValidityInMonth()
                ) {
                    $validityPeriod = $account->getRepairModule()->getDefaultCouponValidityInMonth();
                }

                $validityDate = new \DateTime();
                $validityDate->setTime(0, 0, 0);
                $validityDate->add(new \DateInterval('P'.((int) $validityPeriod).'M'));
                $object->setValidUntil($validityDate);
            }

            //Update supplier
            if (null === $object->getSupplier()) {
                $account = $object->getAccount();

                if ($account instanceof RepairModuleableInterface
                    && $account->getRepairModule()
                    && null !== $account->getRepairModule()->getSupplier()
                ) {
                    $edited = true;
                } else {
                    ListenerUtil::thrownError($this->translator->trans(
                        'klipper_repair.coupon.invalid_empty_supplier',
                        [],
                        'validators'
                    ), $object, 'supplier');
                }
            }

            // Update status and used at
            if ($create && null !== $object->getUsedByRepair()) {
                $edited = true;
                $object->setStatus($this->getChoice($em, 'coupon_status', 'used'));
                $object->setUsedAt(new \DateTime());
            } elseif (isset($changeSet['usedByRepair'])) {
                $edited = true;
                $now = new \DateTime();

                if (null !== $changeSet['usedByRepair'][1]) {
                    $statusValue = 'used';
                } elseif (null !== $object->getValidUntil() && $object->getValidUntil() < $now) {
                    $statusValue = 'expired';
                } else {
                    $statusValue = 'valid';
                }

                $object->setStatus($this->getChoice($em, 'coupon_status', $statusValue));
                $object->setUsedAt('used' === $statusValue ? $now : null);
            }

            if ($edited && $create) {
                $meta = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($meta, $object);
            }
        }
    }
}
