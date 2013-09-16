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
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * Originally based on Magento Tablerate Shipping code and Auctionmaid Matrixrate.
 * @copyright  Copyright (c) 2008 Auction Maid (http://www.auctionmaid.com)
 * @author     Karen Baker <enquiries@auctionmaid.com>
 *
 * @category   Fontis
 * @package    Fontis_Australia
 * @author     Chris Norton
 * @copyright  Copyright (c) 2008 Fontis Pty. Ltd. (http://www.fontis.com.au)
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
	protected $_code = 'eparcel';
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

        if(is_array($rates))
        {
            foreach ($rates as $rate)
            {
               if (!empty($rate) && $rate['price'] >= 0) {
                  $method = Mage::getModel('shipping/rate_result_method');

                    $method->setCarrier('eparcel');
                    $method->setCarrierTitle($this->getConfigData('title'));

                    $method->setMethod($this->_getChargeCode($rate));
                    $method->setMethodTitle($rate['delivery_type']);
                    
                    $method->setMethodChargeCodeIndividual($rate['charge_code_individual']);
                    $method->setMethodChargeCodeBusiness($rate['charge_code_business']);

                    $shippingPrice = $this->getFinalPriceWithHandlingFee($rate['price']);

                    $method->setPrice($shippingPrice);
                    $method->setCost($rate['cost']);
                    $method->setDeliveryType($rate['delivery_type']);

                    $result->append($method);
                }
            }
        }
        else
        {
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

	public function getCode($type, $code='')
	{
		$codes = array(

		    'condition_name'=>array(
		        'package_weight' => Mage::helper('shipping')->__('Weight vs. Destination'),
		        'package_value'  => Mage::helper('shipping')->__('Price vs. Destination'),
		        'package_qty'    => Mage::helper('shipping')->__('# of Items vs. Destination'),
		    ),

		    'condition_name_short'=>array(
		        'package_weight' => Mage::helper('shipping')->__('Weight (and above)'),
		        'package_value'  => Mage::helper('shipping')->__('Order Subtotal (and above)'),
		        'package_qty'    => Mage::helper('shipping')->__('# of Items (and above)'),
		    ),

		);

		if (!isset($codes[$type])) {
		    throw Mage::exception('Mage_Shipping', Mage::helper('shipping')->__('Invalid Table Rate code type: %s', $type));
		}

		if (''===$code) {
		    return $codes[$type];
		}

		if (!isset($codes[$type][$code])) {
		    throw Mage::exception('Mage_Shipping', Mage::helper('shipping')->__('Invalid Table Rate code for type %s: %s', $type, $code));
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
		return array('bestway'=>$this->getConfigData('name'));
	}
	
	/*
	 * Tracking code
	 */
	public function isTrackingAvailable()
	{
		return true;
	}
	
	public function getTrackingInfo($tracking)
	{
		$info = array();

		$result = $this->getTracking($tracking);

		if($result instanceof Mage_Shipping_Model_Tracking_Result){
			if ($trackings = $result->getAllTrackings()) {
				return $trackings[0];
			}
		}
		elseif (is_string($result) && !empty($result)) {
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
		
		foreach($trackings as $t) {
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
            'label' => $observer->block->__('Eparcel CSV export'),
            'url' => $observer->block->getUrl('australia/eparcel/export')
        ));
	}
	
	/**
	 * Event Observer. Triggered when a shipment is created.
	 */
	public function sendManifest($observer)
	{
	    /**
	     * As far as I can tell, this has never worked.
	     * I'll refactor to work is possible and integrate with new charge code management. 
	     * @author Jonathan Melnick
	     */
	    return;
	    
	    
        //Mage::log('=========================================================================');
		
		$order = $observer->getEvent()->getShipment()->getOrder();
		
		if(!($order->getShippingCarrier() instanceof $this)) {
            return $this;
		}
		
		// The current timestamp is used several times
		$timestamp = date('c');
		
		// Save the consignment number as it will be used more than once
		$consignmentNumber = $order->getIncrementId();
		
		$doc = new SimpleXMLElement('<PCMS xmlns="http://www.auspost.com.au/xml/pcms"></PCMS>');

		$pcms = $doc->addChild('SendPCMSManifest');

		$head = $pcms->addChild('header');
		$body = $pcms->addChild('body');

		$head->addChild('TransactionDateTime', $timestamp);
		$head->addChild('TransactionId', $consignmentNumber);
		$head->addChild('TransactionSequence', '0');	// Used to identify a sequence of transactions, N/A
		$head->addChild('ApplicationId', 'MERCHANT');

		$manifest = $body->addChild('PCMSManifest');

		$manifest->addChild('MerchantLocationId', $this->getConfigData('merchant_location_id')); // Testing = AWV
		$manifest->addChild('ManifestNumber', $consignmentNumber);
		$manifest->addChild('DateSubmitted', $timestamp);
		$manifest->addChild('DateLodged', $timestamp);

		// There may be multiple consignments per manifest.
		$consignment = $manifest->addChild('PCMSConsignment');
		
		// Get shipping address info
		$shippingAddress = $order->getShippingAddress();
		$name = $shippingAddress->getFirstname().' '.$shippingAddress->getLastname();
		$street = $shippingAddress->getStreet();
		
		// TODO: Revert back to using the Magento directory lookup, once they have
		// fixed the code that does loadByName.
		$stateCodes = array(
			'Victoria' => 'VIC',
			'New South Wales' => 'NSW',
			'Australian Capital Territory' => 'ACT',
			'Northern Territory' => 'NT',
			'Queensland' => 'QLD',
			'South Australia' => 'SA',
			'Tasmania' => 'TAS',
			'Western Australia' => 'WA'
		);
			

		$consignment->addChild('ConsignmentNumber', $consignmentNumber);
		$consignment->addChild('ChargeCode', $this->getConfigData('charge_code')); // Testing = S2
		$consignment->addChild('DeliveryName', $name);
		if($shippingAddress->getCompany()) { $consignment->addChild('DeliveryCompanyName', $shippingAddress->getCompany()); } // Optional
		if(is_array($street)) {
			$consignment->addChild('DeliveryAddressLine1', $street[0]);
			if(count($street) >= 2) { $consignment->addChild('DeliveryAddressLine2', $street[1]); } // Optional
			if(count($street) >= 3) { $consignment->addChild('DeliveryAddressLine3', $street[2]); } // Optional
			if(count($street) >= 4) { $consignment->addChild('DeliveryAddressLine4', $street[3]); } // Optional
		}
		else {
			$consignment->addChild('DeliveryAddressLine1', $street);
		}
		$consignment->addChild('DeliveryPhoneNumber', $shippingAddress->getTelephone());
		$consignment->addChild('DeliveryEmailAddress', $order->getCustomerEmail());
		$consignment->addChild('DeliverySuburb', $shippingAddress->getCity());
		//$consignment->addChild('DeliveryStateCode', Mage::getModel('directory/region')->loadByName($shippingAddress->getRegion(), 'AU')->getCode());
		$consignment->addChild('DeliveryStateCode', $stateCodes[$shippingAddress->getRegion()]);
		$consignment->addChild('DeliveryPostcode', $shippingAddress->getPostcode());
		$consignment->addChild('DeliveryCountryCode', 'AU'); // International deliveries not currently accepted
		$consignment->addChild('IsInternationalDelivery', 'false'); // International deliveries not currently accepted
		$consignment->addChild('ReturnName', $this->getConfigData('return_name')); // Optional
		$consignment->addChild('ReturnAddressLine1', $this->getConfigData('return_address_1')); 
		$consignment->addChild('ReturnAddressLine2', $this->getConfigData('return_address_2')); // Optional
		$consignment->addChild('ReturnAddressLine3', $this->getConfigData('return_address_3')); // Optional
		$consignment->addChild('ReturnAddressLine4', $this->getConfigData('return_address_4')); // Optional
		$consignment->addChild('ReturnSuburb', $this->getConfigData('return_suburb'));
		$consignment->addChild('ReturnStateCode', $this->getConfigData('return_state'));
		$consignment->addChild('ReturnPostcode', $this->getConfigData('return_postcode'));
		$consignment->addChild('ReturnCountryCode', 'AU');
		$consignment->addChild('CreatedDateTime', $timestamp);
		$consignment->addChild('PostChargeToAccount', $this->getConfigData('post_charge_account')); // For Testing = 8830728
		$consignment->addChild('IsSignatureRequired', $this->getConfigData('signature_required') ? 'Y' : 'N'); // Y/N
		$consignment->addChild('DeliverPartConsignment', 'N'); // Y/N
		$consignment->addChild('ContainsDangerousGoods', 'false'); // true/false

		foreach($order->getAllVisibleItems() as $item) {
			Mage::log('Item: ' . print_r($item->getData(), true));	
			// Consignments have one article per product
			$article = $consignment->addChild('PCMSDomesticArticle'); // International deliveries not currently accepted

			$article->addChild('ArticleNumber', $item->getSku());
			$article->addChild('BarcodeArticleNumber', '');
			//$article->addChild('Length', ''); // Optional
			//$article->addChild('Width', ''); // Optional
			//$article->addChild('Height', ''); // Optional
			$article->addChild('ActualWeight', $item->getRowWeight());
			//$article->addChild('CubicWeight', ''); // Optional
			$article->addChild('ArticleDescription', $item->getShortDescription());
			$article->addChild('IsTransitCoverRequired', 'N');
			//$article->addChild('TransitCoverAmount', '');

			// All contents are optional
			$contents = $article->addChild('ContentsItem');

			//$contents->addChild('ProductType', '');
			//$contents->addChild('GoodsDescription', '');
			//$contents->addChild('CountryOriginCode', '');
			//$contents->addChild('Weight', $item->getWeight());
			$contents->addChild('Quantity', $item->getQtyShipped());
			$contents->addChild('UnitValue', $item->getPrice());
			$contents->addChild('Value', $item->getRowTotal());
			//$contents->addChild('HSTariff', '');
			//$contents->addChild('ProductClassification', '');
			//*/
		}

		$data = $doc->asXML();
		
		//Mage::log($data);

		$soap = new SoapClient('https://test603a.auspost.com.au/despatchManifest/DespatchManifestWS?WSDL', array('login' => 'soaptest', 'password' => 'password'));
		$soap->submitManifestForDespatch($data);
		
		// Automatically add tracking information
		$track = Mage::getModel('sales/order_shipment_track');
		$track->setCarrierCode('eparcel');
		$track->setNumber($consignmentNumber);
		$track->setTitle($this->getConfigData('title'));
		$observer->getEvent()->getShipment()->addTrack($track);
		
		//Mage::log(print_r($track->getData(), true));
		
		return $this;
	}
}
