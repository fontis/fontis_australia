<?php
/**
 * @package    Fontis_Australia
 */


class Fontis_Australia_Model_Shipping_Carrier_Eparcel_Export extends Mage_Core_Model_Abstract
{
    const ENCLOSURE = '"';
    const DELIMITER = ',';
    
    const DEBUG = false;

    protected $_defaults = null;
    
    /**
     * Concrete implementation of abstract method to export given orders to csv file in var/export.
     *
     * @param $orders List of orders of type Mage_Sales_Model_Order or order ids to export.
     * @return String The name of the written csv file in var/export
     */
    public function exportOrders($orders) 
    {
        $eparcel = new Dhmedia_AustraliaPost_Eparcel();

        foreach ($orders as $order)
        {       	
            $order = Mage::getModel('sales/order')->load($order);

            if ( ! $order->getShippingCarrier() instanceof Fontis_Australia_Model_Shipping_Carrier_Eparcel )
            {
				throw new Dhmedia_EparcelExport_Exception("Order #" . $order->getId() . " doesn't use Australia Post's Eparcel as it's carrier!");				
            }
            
            $orderItems = $order->getItemsCollection();
            
        	$currentParcel = $this->getNewParcel();
        	
        	$consignementRecord = $this->getConsignementRecord($order,$currentParcel);
        	
	        foreach ($orderItems as $item)
			{
				/**
				 * Check item is valid
				 */
	            if ( $item->isDummy() ) continue;

	            /**
				 * Calculate item quantity
				 */
	            $itemQuantity = $item->getData('qty_ordered') - $item->getData('qty_canceled') - $item->getData('qty_shipped');
				
	            /**
				 * Check item quantity
				 */
	            if ( $itemQuantity == 0 ) continue;
	            
				/**
				 * Populate Good Record
				 * 
				 * UPDATE 2010.06.16 : Auspost support has said that we should only have ONE good record 
				 * per consignment (though their documentation says otherwise)
				 * 
				 * @var Dhmedia_AustraliaPost_Eparcel_Record_Good
				 */
				$goodRecord = new Dhmedia_AustraliaPost_Eparcel_Record_Good();
				$goodRecord->originCountryCode = '';
				$goodRecord->hsTariffCode = '';
				$goodRecord->description = str_replace(',','',$item['name']); // remove commas
				$goodRecord->productType = $this->getDefault('good/product_type');
				$goodRecord->productClassification = null;
				$goodRecord->quantity = $itemQuantity;
				$goodRecord->weight = max($item['weight'],0);
				$goodRecord->unitValue = max($item->getData('price') + $item->getData('tax_amount'), 0);
				$goodRecord->totalValue = max($goodRecord->unitValue * $goodRecord->quantity, 0);
				
				/**
				 * We have at least one Good, Time to add the consignementRecord if not done yet
				 */
				if ( ! $consignementRecord->isAddedToEparcel() )
				{
					$eparcel->addRecord( $consignementRecord );
				}
				
				/**
	             * If current parcel can't accept extra item, close it, and open new parcel
	             */
				if ( ! $currentParcel->canAddGood( $goodRecord ) )
				{
					$this->closeParcel($eparcel,$currentParcel);
					
					$currentParcel = $this->getNewParcel();
				}
				
				/**
				 * Add item to Parcel
				 */
				$currentParcel->addGood( $goodRecord );
			}
			
			$this->closeParcel($eparcel,$currentParcel);
        }

        if ( self::DEBUG )
        {
        	throw new Dhmedia_EparcelExport_Exception( nl2br($this->log()) );
        }
        
        /**
         * Save file
         */
        $fileName = 'order_export_'.date("Ymd_His").'_eparcel.csv';
        $filePath = Mage::getBaseDir('export').'/'.$fileName;
        
        if ( $eparcel->makeCsv( $filePath ) )
        {
        	return $filePath;
        }

        throw new Dhmedia_EparcelExport_Exception("Unable to build .CSV file!");
    }
    
    protected function getNewParcel()
    {
    	$parcel = new Dhmedia_AustraliaPost_Eparcel_Parcel_Carton();
    	
    	$parcel->weightMax = $this->getDefault('parcel/weightmax');
    	$parcel->width = (int) $this->getDefault('parcel/width');
    	$parcel->height = (int) $this->getDefault('parcel/height');
    	$parcel->length = (int) $this->getDefault('parcel/length');
    	
    	return $parcel;
    }
    
    protected function closeParcel(Dhmedia_AustraliaPost_Eparcel $eparcel, Dhmedia_AustraliaPost_Eparcel_Parcel $parcel)
    {
    	$articleRecordClass = (bool) $this->getDefault('parcel/use_cubicweight') ?
    		'Dhmedia_AustraliaPost_Eparcel_Record_Article_CubicWeight' :
    		'Dhmedia_AustraliaPost_Eparcel_Record_Article';
	            
    	$goodRecords = $parcel->getGoodRecords();

    	if (count($goodRecords) == 0) return false;
    	
    	$eparcel->addRecord(
			$parcel->processArticleRecord(
				new $articleRecordClass()
			)
		);
		
		if ( (bool) $this->getDefault('good/use_multiplegoodrecords') )
		{
			foreach( $parcel->getGoodRecords() as $_goodRecord )
			{
				$eparcel->addRecord( $_goodRecord );
			}
		}
		else
		{
			$goodRecord = new Dhmedia_AustraliaPost_Eparcel_Record_Good();
			$goodRecord->originCountryCode = '';
			$goodRecord->hsTariffCode = '';
			$goodRecord->productClassification = null;
			$goodRecord->quantity = 1;
			
			foreach( $parcel->getGoodRecords() as $_goodRecord )
			{
				$goodRecord->productType = $_goodRecord->productType;
				$goodRecord->description = str_replace(',','',$_goodRecord->productType); // remove commas
				
				$goodRecord->weight += $_goodRecord->weight * $_goodRecord->quantity;
				$goodRecord->unitValue += $_goodRecord->unitValue * $_goodRecord->quantity;
				$goodRecord->totalValue += $_goodRecord->totalValue;
			}
			
			$eparcel->addRecord( $goodRecord );
		}
		
		return true;
    }

    /**
	 * ##### AUSTRALIA POST CHARGE CODES #####
	 * 
	 * # Domestic / Standard / Individual
	 * 
	 *  S1      EPARCEL 1       Domestic       
	 * 	S2      EPARCEL 2       Domestic       
	 * 	S3      EPARCEL 3       Domestic       
	 * 	S4      EPARCEL 4       Domestic       
	 * 	S5      EPARCEL 5       Domestic       
	 * 	S6      EPARCEL 6       Domestic       
	 * 	S7      EPARCEL 7       Domestic       
	 * 	S8      EPARCEL 8       Domestic
	 * 	
	 * # Domestic / Standard / Business
	 * 
	 *  B1      B TO B EPARCEL 1        Domestic       
	 * 	B2      B TO B EPARCEL 2        Domestic       
	 * 	B5      B TO B EPARCEL 5        Domestic
	 * 
	 * # Domestic / Express / Individual
	 *     
	 *  X1      EXPRESS POST EPARCEL    Domestic       
	 * 	X2      EXPRESS POST EPARCEL 2  Domestic
	 * 	
	 * # Domestic / Express / Business
	 *     
	 *  XB1     EXPRESS POST EPARCEL B2B        Domestic       
	 * 	XB2     EXPRESS POST EPARCEL B2B 2      Domestic
	 * 	
	 * # International / Standard
	 *     
	 *  AIR1    INTERNATIONAL Airmail 1 International  
	 * 	AIR2    INTERNATIONAL Airmail 2 International  
	 * 	AIR3    INTERNATIONAL Airmail - 8 Zones International
	 * 	
	 * # International / Express
	 *     
	 *  EPI1    Express Post International      International  
	 * 	EPI2    Express Post International      International  
	 * 	EPI3    Express Post International – 8 zones    International
	 * 	
	 * 	ECM1    Express Courier Int’l Merchandise 1      International  
	 * 	ECM2    Express Courier Int’l Merchandise 2     International  
	 * 	ECM3    Express Courier Int’l Merch 8Zone       International
	 *     
	 *  [NOT IMPLEMENTED] ECD1    EXPRESS COURIER INT'L DOC 1     International  
	 * 	[NOT IMPLEMENTED] ECD2    EXPRESS COURIER INT'L DOC 2     International  
	 * 	[NOT IMPLEMENTED] ECD3    Express Courier Int’l Doc – 8 zones     International
	 * 
	 * # Other
	 * 
	 * 	[NOT IMPLEMENTED] CFR     eParcel Call For Return Domestic       
	 * 	[NOT IMPLEMENTED] PR      eParcel Post Returns Service    Domestic
	 * 	
	 * 	[NOT IMPLEMENTED] CS1     CTC EPARCEL     Domestic       
	 * 	[NOT IMPLEMENTED] CS4     CTC EPARCEL     Domestic       
	 * 	[NOT IMPLEMENTED] CS5     CTC EPARCEL 5   Domestic       
	 * 	[NOT IMPLEMENTED] CS6     CTC EPARCEL 6   Domestic       
	 * 	[NOT IMPLEMENTED] CS7     CTC EPARCEL 7   Domestic       
	 * 	[NOT IMPLEMENTED] CS8     CTC EPARCEL 8   Domestic
	 * 	       
	 * 	[NOT IMPLEMENTED] CX1     CTC EXPRESS POST 500G BRK       Domestic       
	 * 	[NOT IMPLEMENTED] CX2     CTC EXPRESS POST MULTI BRK      Domestic
	 * 	  
	 * 	[NOT IMPLEMENTED] RPI1    Registered Post International   International	
	 * 
	 */
    protected function getConsignementRecord(Mage_Sales_Model_Order $order, Dhmedia_AustraliaPost_Eparcel_Parcel $parcel)
    {
    	/**
    	 * The Fontis module is a piece of crap.  We have to map their codes to the proper Auspost one's.
    	 * They should be using the correct ones from the start.
    	 * 
    	 * This is terribly error-prone.  If they decide to change them in a future version, back to the drawing board.
    	 * 
    	 * This is how we map the codes (left is Fontis proclamed code, right is proper Auspost Eparcel code) :
    	 * 
		 * STANDARD : 	S? / B?
		 * EXPRESS : 	X? / XB?
		 * ECI : 		ECM?
		 * AIR : 		AIR?
		 * EPI : 		EPI?
		 * ECIEC : 		ECI? + Insurance
		 * AIREC : 		AIR? + Insurance
		 * EPI-EC : 	EPI? + Insurance
		 * SEA : 		??? [NOT IMPLEMENTED]
		 * 
		 * The '?' is a number (see the correct Eparcel codes)
		 * The '/' delimits individual vs business shipping methods (see the correct Eparcel codes)
    	 * 
    	 */

    	$shippingMethod = $order->getData('shipping_method');
    	
    	/**
    	 * [UPDATE: 2011-04-28]
    	 * 
    	 * Something major has changed with Fontis' new Australia module.
    	 * 
    	 * There is only one charge code in their config settings, and they use delivery types from the db.
    	 * Those delivery types can be anything (since the user can update them from a csv).
    	 * 
    	 * Until we update our extension and integrate it to Fontis', here is a quick fix for alphamaleskincare.com.au:
    	 * 
    	 * Strings look something like this: "eparcel_eParcel Standard with TransitCover"
    	 * 
    	 * We want "standard", so we explode, get second word (index=1), and convert to lowercase
    	 * 
    	 */
    	
    	/* -----| START OF ALPHAMALESKINCARE.COM.AU ~HACK~ |----- */
    	
    	$map = array(
    		"eparcel_Standard Delivery (1-5 Business Days)" => 'standard',
    		"eparcel_Express Delivery (*Guaranteed Next Day)" => 'express',
    		"eparcel_Registered International Airmail" => 'airec',
    	
    		// Backwards compatibility
    		"eparcel_eParcel Standard with TransitCover" => 'standard',
    		"eparcel_Standard Delivery (1-5 Business Days) with TransitCover" => 'standard'
    	);

    	$chargeCodeType = $map[$shippingMethod];
    	
    	/* -----| END OF ALPHAMALESKINCARE.COM.AU ~HACK~ |----- */
    	
    	
    	$signatureRequired = (bool) $this->getDefault('consignement/is_signature_required');

    	switch($chargeCodeType)
    	{
    		case 'standard':
    		case 'express':
    			
    			/**
	    		 * Is this customer is in a ~business~ group ?
	    		 */
	    		$isBusinessTest = in_array(
	    			$order->getData('customer_group_id'),
	    			explode(',', $this->getDefault('charge_codes/business_groups'))
	    		);
	    		
	    		$chargeCodeType .= $isBusinessTest ? '_business' : '_individual';
	    		
    			break;

    		case 'airec':
    		case 'air-ec':
    			$chargeCodeType = 'air';
    			$parcel->isInsuranceRequired(true);
    			$signatureRequired = true;
    			break;
    			
    		case 'eciec':
    		case 'eci-ec':
    			$chargeCodeType = 'eci';
    			$parcel->isInsuranceRequired(true);
    		case 'eci':
    			$signatureRequired = true;
    			break;

    		case 'epiec':
    		case 'epi-ec':
    			$chargeCodeType = 'epi';
    			$parcel->isInsuranceRequired(true);
    			$signatureRequired = true;
    			break;
    	}
    	
    	$chargeCode = $this->getDefault('charge_codes/' . $chargeCodeType); 
    	
    	if (empty($chargeCode))
    	{
    		throw new Dhmedia_EparcelExport_Exception("No charge code defined for the following shipping method : $shippingMethod");
    	}
    	
    	
    	$consignementRecord = new Dhmedia_AustraliaPost_Eparcel_Record_Consignement();

		$consignementRecord->chargeCode = $chargeCode;
            	
		$consignementRecord->isSignatureRequired 	= $signatureRequired;
		$consignementRecord->addToAddressBook 		= (bool) $this->getDefault('consignement/add_to_address_book');
		$consignementRecord->isRefPrintRequired 	= (bool) $this->getDefault('consignement/print_ref1');
		$consignementRecord->isRef2PrintRequired 	= (bool) $this->getDefault('consignement/print_ref2');
		
		/**
		 * AusPost said this was useless/pointless 
		 */
		//$consignementRecord->merchantConsigneeCode	= $order->getCustomerId(); // only returns if customer not anonymous

		$consignementRecord->consigneeName 			= $order->getShippingAddress()->getName();
		$consignementRecord->consigneeAddressLine1 	= $order->getShippingAddress()->getStreet1();
        $consignementRecord->consigneeAddressLine2 	= $order->getShippingAddress()->getStreet2();
        $consignementRecord->consigneeAddressLine3 	= $order->getShippingAddress()->getStreet3();
        $consignementRecord->consigneeAddressLine4 	= $order->getShippingAddress()->getStreet4();
		$consignementRecord->consigneeSuburb 		= $order->getShippingAddress()->getCity();
		$consignementRecord->consigneeStateCode 	= $order->getShippingAddress()->getRegionCode();
		$consignementRecord->consigneePostcode 		= $order->getShippingAddress()->getPostcode();
		$consignementRecord->consigneeCountryCode	= $order->getShippingAddress()->getCountry();
		$consignementRecord->consigneePhoneNumber	= $order->getShippingAddress()->getData('telephone');
		$consignementRecord->ref 					= $order->hasInvoices() ? $order->getInvoiceCollection()->getLastItem()->getData('increment_id') : "";
		$consignementRecord->ref2 					= $order->getRealOrderId();
		
		return $consignementRecord;
	}
    
	protected function getDefault($key)
	{
		if ( !is_array($this->_defaults) )
		{
			$this->_defaults = Mage::getStoreConfig('dhmedia_eparcelexport');
		}
		
		$_defaults = $this->_defaults;
		
		foreach(explode('/',$key) as $keyPart)
		{
			$_defaults = $_defaults[$keyPart];
		}
		
		return $_defaults;
	}
	
	
	/* ---------- HELPERS ---------- */
	
	
	/**
	 * Returns the name of the website, store and store view the order was placed in.
	 *
	 * @param Mage_Sales_Model_Order $order The order to return info from
	 * @return String The name of the website, store and store view the order was placed in
	 */
	protected function getStoreName($order)
	{
	    $storeId = $order->getStoreId();
	    if (is_null($storeId)) {
	        return $this->getOrder()->getStoreName();
	    }
	    $store = Mage::app()->getStore($storeId);
	    $name = array(
	            $store->getWebsite()->getName(),
	            $store->getGroup()->getName(),
	            $store->getName()
	    );
	    return implode(', ', $name);
	}
	
	/**
	 * Returns the payment method of the given order.
	 *
	 * @param Mage_Sales_Model_Order $order The order to return info from
	 * @return String The name of the payment method
	 */
	protected function getPaymentMethod($order)
	{
	    return $order->getPayment()->getMethod();
	}
	
	/**
	 * Returns the shipping method of the given order.
	 *
	 * @param Mage_Sales_Model_Order $order The order to return info from
	 * @return String The name of the shipping method
	 */
	protected function getShippingMethod($order)
	{
	    if (!$order->getIsVirtual() && $order->getShippingMethod()) {
	        return $order->getShippingMethod();
	    }
	    return '';
	}
	
	/**
	 * Returns the total quantity of ordered items of the given order.
	 *
	 * @param Mage_Sales_Model_Order $order The order to return info from
	 * @return int The total quantity of ordered items
	 */
	protected function getTotalQtyItemsOrdered($order) {
	    $qty = 0;
	    $orderedItems = $order->getItemsCollection();
	    foreach ($orderedItems as $item)
	    {
	        if (!$item->isDummy()) {
	            $qty += (int)$item->getQtyOrdered();
	        }
	    }
	    return $qty;
	}
	
	/**
	 * Returns the sku of the given item dependant on the product type.
	 *
	 * @param Mage_Sales_Model_Order_Item $item The item to return info from
	 * @return String The sku
	 */
	protected function getItemSku($item)
	{
	    if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
	        return $item->getProductOptionByCode('simple_sku');
	    }
	    return $item->getSku();
	}
	
	/**
	 * Returns the options of the given item separated by comma(s) like this:
	 * option1: value1, option2: value2
	 *
	 * @param Mage_Sales_Model_Order_Item $item The item to return info from
	 * @return String The item options
	 */
	protected function getItemOptions($item)
	{
	    $options = '';
	    if ($orderOptions = $this->getItemOrderOptions($item)) {
	        foreach ($orderOptions as $_option) {
	            if (strlen($options) > 0) {
	                $options .= ', ';
	            }
	            $options .= $_option['label'].': '.$_option['value'];
	        }
	    }
	    return $options;
	}
	
	/**
	 * Returns all the product options of the given item including additional_options and
	 * attributes_info.
	 *
	 * @param Mage_Sales_Model_Order_Item $item The item to return info from
	 * @return Array The item options
	 */
	protected function getItemOrderOptions($item)
	{
	    $result = array();
	    if ($options = $item->getProductOptions()) {
	        if (isset($options['options'])) {
	            $result = array_merge($result, $options['options']);
	        }
	        if (isset($options['additional_options'])) {
	            $result = array_merge($result, $options['additional_options']);
	        }
	        if (!empty($options['attributes_info'])) {
	            $result = array_merge($options['attributes_info'], $result);
	        }
	    }
	    return $result;
	}
	
	/**
	 * Calculates and returns the grand total of an item including tax and excluding
	 * discount.
	 *
	 * @param Mage_Sales_Model_Order_Item $item The item to return info from
	 * @return Float The grand total
	 */
	protected function getItemTotal($item)
	{
	    return $item->getRowTotal() - $item->getDiscountAmount() + $item->getTaxAmount() + $item->getWeeeTaxAppliedRowAmount();
	}
	
	/**
	 * Formats a price by adding the currency symbol and formatting the number
	 * depending on the current locale.
	 *
	 * @param Float $price The price to format
	 * @param Mage_Sales_Model_Order $formatter The order to format the price by implementing the method formatPriceTxt($price)
	 * @return String The formatted price
	 */
	protected function formatPrice($price, $formatter)
	{
	    return $formatter->formatPriceTxt($price);
	}
}
