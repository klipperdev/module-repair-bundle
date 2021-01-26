<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\RepairBundle\Controller;

use Klipper\Bundle\ApiBundle\Action\Upsert;
use Klipper\Bundle\ApiBundle\Controller\ControllerHelper;
use Klipper\Component\SecurityOauth\Scope\ScopeVote;
use Klipper\Module\RepairBundle\Model\CouponInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ApiCouponController
{
    /**
     * Recredit the coupon.
     *
     * @Entity("id", class="App:Coupon")
     *
     * @Route("/coupons/{id}/recredit", methods={"PUT"})
     */
    public function recreditCoupon(
        ControllerHelper $helper,
        CouponInterface $id
    ): Response {
        if (class_exists(ScopeVote::class)) {
            $helper->denyAccessUnlessGranted(new ScopeVote('meta/coupon'));
        }

        $newCoupon = clone $id;
        $newCoupon->setRecreditedCoupon($id);
        $newCoupon->setPrice(0);
        $action = Upsert::build('', $newCoupon)->setProcessForm(false);

        return $helper->upsert($action);
    }
}
