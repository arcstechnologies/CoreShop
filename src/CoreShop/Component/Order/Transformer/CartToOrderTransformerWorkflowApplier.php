<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2019 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Component\Order\Transformer;

use CoreShop\Component\Order\Model\CartInterface;
use CoreShop\Component\Order\Model\OrderInterface;
use CoreShop\Component\Order\Model\ProposalInterface;
use CoreShop\Component\Order\OrderInvoiceStates;
use CoreShop\Component\Order\OrderPaymentStates;
use CoreShop\Component\Order\OrderShipmentStates;
use CoreShop\Component\Order\OrderStates;
use CoreShop\Component\Order\OrderTransitions;
use CoreShop\Bundle\WorkflowBundle\Applier\StateMachineApplierInterface;

final class CartToOrderTransformerWorkflowApplier implements ProposalTransformerInterface
{
    /**
     * @var ProposalTransformerInterface
     */
    private $innerCartToOrderTransformer;

    /**
     * @var StateMachineApplierInterface
     */
    private $stateMachineApplier;

    /**
     * @param ProposalTransformerInterface $innerCartToOrderTransformer
     * @param StateMachineApplierInterface $stateMachineApplier
     */
    public function __construct(
        ProposalTransformerInterface $innerCartToOrderTransformer,
        StateMachineApplierInterface $stateMachineApplier
    ) {
        $this->innerCartToOrderTransformer = $innerCartToOrderTransformer;
        $this->stateMachineApplier = $stateMachineApplier;
    }

    /**
     * @param ProposalInterface $cart
     * @param ProposalInterface $sale
     *
     * @return ProposalInterface|mixed
     */
    public function transform(ProposalInterface $cart, ProposalInterface $sale)
    {
        /**
         * @var CartInterface  $cart
         * @var OrderInterface $order
         */
        $sale = $this->innerCartToOrderTransformer->transform($cart, $sale);

        $sale->setOrderState(OrderStates::STATE_INITIALIZED);
        $sale->setShippingState(OrderShipmentStates::STATE_NEW);
        $sale->setPaymentState(OrderPaymentStates::STATE_NEW);
        $sale->setInvoiceState(OrderInvoiceStates::STATE_NEW);

        if ($sale instanceof OrderInterface) {
            $this->stateMachineApplier->apply($sale, 'coreshop_order', OrderTransitions::TRANSITION_CREATE);
        }

        return $sale;
    }
}
