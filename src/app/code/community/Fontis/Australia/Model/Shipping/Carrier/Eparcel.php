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
 * Originally based on Magento Tablerate Shipping code and Auctionmaid Matrixrate.
 * @copyright  Copyright (c) 2008 Auction Maid (http://www.auctionmaid.com)
 * @author     Karen Baker <enquiries@auctionmaid.com>
 *
 * @category   Fontis
 * @package    Fontis_Australia
 * @author     Chris Norton
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Australia Post eParcel shipping model
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */

class Fontis_Australia_Model_Shipping_Carrier_Eparcel
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    /**
     * @var string
     */
    protected $_code = 'eparcel';

    /**
     * @var string
     */
    protected $_default_condition_name = 'package_weight';

    protected $_conditionNames = array();

    public function __construct()
    {
        parent::__construct();
        foreach ($this->getCode('condition_name') as $k=>$v) {
            $this->_conditionNames[] = $k;
        }
    }

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        if (!$request->getConditionName()) {
            $request->setConditionName($this->getConfigData('condition_name') ? $this->getConfigData('condition_name') : $this->_default_condition_name);
        }

        $result = Mage::getModel('shipping/rate_result');
        $rates = $this->getRate($request);

        if (is_array($rates)) {
            foreach ($rates as $rate) {
                if (!empty($rate) && $rate['price'] >= 0) {
                    /** @var Mage_Shipping_Model_Rate_Result_Method $method */
                    $method = Mage::getModel('shipping/rate_result_method');

                    $method->setCarrier('eparcel');
                    $method->setCarrierTitle($this->getConfigData('title'));
                    if ($this->_getChargeCode($rate)) {
                        $_method = strtolower(str_replace(' ', '_', $this->_getChargeCode($rate)));
                    } else {
                        $_method = strtolower(str_replace(' ', '_', $rate['delivery_type']));
                    }
                    $method->setMethod($_method);
                    if ($this->getConfigData('carriers/eparcel/name')) {
                        $method->setMethodTitle($this->getConfigData('carriers/eparcel/name'));
                    } else {
                        $method->setMethodTitle($rate['delivery_type']);
                    }

                    $method->setMethodChargeCodeIndividual($rate['charge_code_individual']);
                    $method->setMethodChargeCodeBusiness($rate['charge_code_business']);

                    $shippingPrice = $this->getFinalPriceWithHandlingFee($rate['price']);

                    $method->setPrice($shippingPrice);
                    $method->setCost($rate['cost']);
                    $method->setDeliveryType($rate['delivery_type']);

                    $result->append($method);
                }
            }
        } else {
            if (!empty($rates) && $rates['price'] >= 0) {
                $method = Mage::getModel('shipping/rate_result_method');

                $method->setCarrier('eparcel');
                $method->setCarrierTitle($this->getConfigData('title'));

                $method->setMethod('bestway');
                $method->setMethodTitle($this->getConfigData('name'));

                $method->setMethodChargeCodeIndividual($rates['charge_code_individual']);
                $method->setMethodChargeCodeBusiness($rates['charge_code_business']);

                $shippingPrice = $this->getFinalPriceWithHandlingFee($rates['price']);

                $method->setPrice($shippingPrice);
                $method->setCost($rates['cost']);
                $method->setDeliveryType($rates['delivery_type']);

                $result->append($method);
            }
        }

        return $result;
    }

    /**
     * @param array $rate
     * @return mixed
     */
    protected function _getChargeCode($rate)
    {
        /* Is this customer is in a ~business~ group ? */
        $isBusinessCustomer = (
            Mage::getSingleton('customer/session')->isLoggedIn()
            AND
            in_array(
                Mage::getSingleton('customer/session')->getCustomerGroupId(),
                explode(
                    ',',
                    Mage::getStoreConfig('doghouse_eparcelexport/charge_codes/business_groups')
                )
            )
        );

        if ($isBusinessCustomer) {
            if (isset($rate['charge_code_business']) && $rate['charge_code_business']) {
                return $rate['charge_code_business'];
            }

            return Mage::getStoreConfig('doghouse_eparcelexport/charge_codes/default_charge_code_business');
        } else {
            if (isset($rate['charge_code_individual']) && $rate['charge_code_individual']) {
                return $rate['charge_code_individual'];
            }

            return Mage::getStoreConfig('doghouse_eparcelexport/charge_codes/default_charge_code_individual');
        }
    }

    public function getRate(Mage_Shipping_Model_Rate_Request $request)
    {
        return Mage::getResourceModel('australia/shipping_carrier_eparcel')->getRate($request);
    }

    public function getCode($type, $code = '')
    {
        $helper = Mage::helper('shipping');
        $codes = array(
            'condition_name' => array(
                'package_weight' => $helper->__('Weight vs. Destination'),
                'package_value'  => $helper->__('Price vs. Destination'),
                'package_qty'    => $helper->__('# of Items vs. Destination'),
            ),
            'condition_name_short' => array(
                'package_weight' => $helper->__('Weight (and above)'),
                'package_value'  => $helper->__('Order Subtotal (and above)'),
                'package_qty'    => $helper->__('# of Items (and above)'),
            ),
        );

        if (!isset($codes[$type])) {
            throw Mage::exception('Mage_Shipping', $helper->__('Invalid Table Rate code type: %s', $type));
        }

        if ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            throw Mage::exception('Mage_Shipping', $helper->__('Invalid Table Rate code for type %s: %s', $type, $code));
        }

        return $codes[$type][$code];
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return array('bestway' => $this->getConfigData('name'));
    }

    /*
     * Tracking code
     *
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    public function getTrackingInfo($tracking)
    {
        $result = $this->getTracking($tracking);

        if ($result instanceof Mage_Shipping_Model_Tracking_Result) {
            if ($trackings = $result->getAllTrackings()) {
                return $trackings[0];
            }
        } elseif (is_string($result) && !empty($result)) {
            return $result;
        }

        return false;
    }

    public function getTracking($trackings)
    {
        if (!is_array($trackings)) {
            $trackings = array($trackings);
        }

        return $this->_getTracking($trackings);
    }

    protected function _getTracking($trackings)
    {
        $result = Mage::getModel('shipping/tracking_result');

        foreach ($trackings as $t) {
            $tracking = Mage::getModel('shipping/tracking_result_status');
            $tracking->setCarrier($this->_code);
            $tracking->setCarrierTitle($this->getConfigData('title'));
            $tracking->setTracking($t);
            $tracking->setUrl('http://www.eparcel.com.au/');
            $result->append($tracking);
        }

        return $result;
    }

    /**
     * Event Observer. Triggered before an adminhtml widget template is rendered.
     * We use this to add our action to bulk actions in the sales order grid instead of overridding the class.
     */
    public function addExportToBulkAction($observer)
    {
        if (! $observer->block instanceof Mage_Adminhtml_Block_Sales_Order_Grid) {
            return;
        }

        $observer->block->getMassactionBlock()->addItem('eparcelexport', array(
            'label' => $observer->block->__('Export to CSV (eParcel)'),
            'url' => $observer->block->getUrl('australia/eparcel/export')
        ));
    }
}
