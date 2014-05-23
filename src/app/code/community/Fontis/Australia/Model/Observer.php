<?php
/**
 * Fontis Australia Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Fontis
 * @package    Fontis_Australia
 * @author     Thai Phan
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Fontis_Australia_Model_Observer
{
    public function autoload()
    {
        require_once(__DIR__ . '/../lib/vendor/autoload.php');
    }

    /**
     * Adds a history entry to orders placed using AU direct deposit or BPay.
     */
    public function addHistory($event)
    {
        $order = $event->getOrder();
        if($order && $order->getPayment()) {
            if($order->getPayment()->getMethod() == 'directdeposit_au') {
                $order->addStatusHistoryComment('Order placed with Direct Deposit')
                    ->setIsCustomerNotified(false);
            } else if($order->getPayment()->getMethod() == 'bpay') {
                $order->addStatusHistoryComment('Order placed with BPay')
                    ->setIsCustomerNotified(false);
            }
        }
    }
}
