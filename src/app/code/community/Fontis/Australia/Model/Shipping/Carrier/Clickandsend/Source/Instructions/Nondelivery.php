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

class Fontis_Australia_Model_Shipping_Carrier_Clickandsend_Source_Instructions_Nondelivery
{
    const RETURN_BY_SURFACE = 1;
    const RETURN_BY_AIRMAIL = 2;
    const DELIVER_REDIRECT_BY_AIRMAIL = 3;
    const DELIVER_REDIRECT_BY_SURFACE = 4;
    const TREAT_AS_ABANDONED = 5;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('australia');
        return array(
            array('value' => self::RETURN_BY_SURFACE,           'label' => $helper->__('Return by Surface')),
            array('value' => self::RETURN_BY_AIRMAIL,           'label' => $helper->__('Return By Airmail')),
            array('value' => self::DELIVER_REDIRECT_BY_AIRMAIL, 'label' => $helper->__('Delivery/Redirect by Airmail')),
            array('value' => self::DELIVER_REDIRECT_BY_SURFACE, 'label' => $helper->__('Delivery/Redirect by Surface')),
            array('value' => self::TREAT_AS_ABANDONED,          'label' => $helper->__('Treat as Abandoned'))
        );
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $helper = Mage::helper('australia');
        return array(
            self::RETURN_BY_SURFACE           => $helper->__('Return by Surface'),
            self::RETURN_BY_AIRMAIL           => $helper->__('Return By Airmail'),
            self::DELIVER_REDIRECT_BY_AIRMAIL => $helper->__('Delivery/Redirect by Airmail'),
            self::DELIVER_REDIRECT_BY_SURFACE => $helper->__('Delivery/Redirect by Surface'),
            self::TREAT_AS_ABANDONED          => $helper->__('Treat as Abandoned')
        );
    }
}
