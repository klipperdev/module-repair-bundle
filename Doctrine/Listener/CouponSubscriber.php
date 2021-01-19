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

            if ($edited && $create) {
                $meta = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($meta, $object);
            }
        }
    }
}
