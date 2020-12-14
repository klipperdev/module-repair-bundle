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
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Klipper\Component\CodeGenerator\CodeGenerator;
use Klipper\Module\ProductBundle\Model\Traits\PriceListableInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RepairSubscriber implements EventSubscriber
{
    private CodeGenerator $generator;

    public function __construct(CodeGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
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
        }
    }
}
