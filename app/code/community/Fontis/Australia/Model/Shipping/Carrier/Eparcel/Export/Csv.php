<?php
/**
 * @package    Fontis_Australia
 */



class Fontis_Australia_Model_Shipping_Carrier_Eparcel_Export_Csv
extends Fontis_Australia_Model_Shipping_Carrier_Eparcel_Export_Abstract
{
    const ENCLOSURE = '"';
    const DELIMITER = ',';
    
    const DEBUG = false;

    protected $_defaults = null;
    
    /* AUSTRALIA POST CHARGE CODES */
    protected $_chargeCodes = array(
            
        /* Domestic / Standard / Individual */
            
        'S1', // EPARCEL 1       Domestic
        'S2', // EPARCEL 2       Domestic
        'S3', // EPARCEL 3       Domestic
        'S4', // EPARCEL 4       Domestic
        'S5', // EPARCEL 5       Domestic
        'S6', // EPARCEL 6       Domestic
        'S7', // EPARCEL 7       Domestic
        'S8', // EPARCEL 8       Domestic
        
        /* Domestic / Standard / Business */
            
        'B1', // B TO B EPARCEL 1        Domestic
        'B2', // B TO B EPARCEL 2        Domestic
        'B5', // B TO B EPARCEL 5        Domestic
        
        /* Domestic / Express / Individual */
        'X1', // EXPRESS POST EPARCEL    Domestic
        'X2', // EXPRESS POST EPARCEL 2  Domestic
        
        /* Domestic / Express / Business */
        'XB1', // EXPRESS POST EPARCEL B2B        Domestic
        'XB2', // EXPRESS POST EPARCEL B2B 2      Domestic
        
        /* International / Standard */
        'AIR1', // INTERNATIONAL Airmail 1 International
        'AIR2', // INTERNATIONAL Airmail 2 International
        'AIR3', // INTERNATIONAL Airmail - 8 Zones International
        
        /* International / Express */
        'EPI1', // Express Post International      International
        'EPI2', // Express Post International      International
        'EPI3', // Express Post International – 8 zones    International
        
        'ECM1', // Express Courier Int’l Merchandise 1      International
        'ECM2', // Express Courier Int’l Merchandise 2     International
        'ECM3', // Express Courier Int’l Merch 8Zone       International
        
        'ECD1', // EXPRESS COURIER INT'L DOC 1     International
        'ECD2', // EXPRESS COURIER INT'L DOC 2     International
        'ECD3', // Express Courier Int’l Doc – 8 zones     International
        
        /* Other */
        
        'CFR', // eParcel Call For Return Domestic
        'PR', // eParcel Post Returns Service    Domestic
        
        'CS1', // CTC EPARCEL     Domestic
        'CS4', // CTC EPARCEL     Domestic
        'CS5', // CTC EPARCEL 5   Domestic
        'CS6', // CTC EPARCEL 6   Domestic
        'CS7', // CTC EPARCEL 7   Domestic
        'CS8', // CTC EPARCEL 8   Domestic
        
        'CX1', // CTC EXPRESS POST 500G BRK       Domestic
        'CX2', // CTC EXPRESS POST MULTI BRK      Domestic
        
        'RPI1', // Registered Post International   International
    );
    
    /**
     * Implementation of abstract method to export given orders to csv file in var/export.
     *
     * @param $orders List of orders of type Mage_Sales_Model_Order or order ids to export.
     * @return String The name of the written csv file in var/export
     */
    public function exportOrders($orders) 
    {
        $eparcel = new Doghouse_Australia_Eparcel();

        foreach ($orders as $order) {
            $order = Mage::getModel('sales/order')->load($order);

            if ( ! $order->getShippingCarrier() instanceof Fontis_Australia_Model_Shipping_Carrier_Eparcel ) {
                throw new Fontis_Australia_Model_Shipping_Carrier_Eparcel_Export_Exception(
                    "Order #" . $order->getIncrementId() . " doesn't use Australia Post's Eparcel as it's carrier!"
                );                
            }
            
            $orderItems = $order->getItemsCollection();
            $currentParcel = $this->getNewParcel();
            $consignementRecord = $this->getConsignementRecord($order,$currentParcel);
            
            foreach ($orderItems as $item) {
                /* Check item is valid */
                if ( $item->isDummy() ) continue;

                /* Calculate item quantity */
                $itemQuantity = $item->getData('qty_ordered') - $item->getData('qty_canceled') - $item->getData('qty_shipped');
                
                /* Check item quantity */
                if ( $itemQuantity == 0 ) continue;
                
                /*
                 * Populate Good Record
                 * 
                 * UPDATE 2010.06.16 : Auspost support has said that we should only have ONE good record 
                 * per consignment (though their documentation says otherwise)
                 * 
                 * @var Doghouse_Australia_Eparcel_Record_Good
                 */
                $goodRecord = new Doghouse_Australia_Eparcel_Record_Good();
                $goodRecord->originCountryCode = '';
                $goodRecord->hsTariffCode = '';
                $goodRecord->description = str_replace(',', '', $item['name']); // remove commas
                $goodRecord->productType = $this->getDefault('good/product_type');
                $goodRecord->productClassification = null;
                $goodRecord->quantity = $itemQuantity;
                $goodRecord->weight = max($item['weight'], 0);
                $goodRecord->unitValue = max($item->getData('price') + $item->getData('tax_amount'), 0);
                $goodRecord->totalValue = max($goodRecord->unitValue * $goodRecord->quantity, 0);
                
                /* We have at least one Good, yime to add the consignementRecord if not done yet */
                if (! $consignementRecord->isAddedToEparcel()) {
                    $eparcel->addRecord($consignementRecord);
                }
                
                /* If current parcel can't fit extra item, close it, and open new parcel */
                if (! $currentParcel->canAddGood($goodRecord)) {
                    $this->closeParcel($eparcel,$currentParcel);
                    $currentParcel = $this->getNewParcel();
                }
                
                /* Add item to Parcel */
                $currentParcel->addGood($goodRecord);
            }
            
            $this->closeParcel($eparcel, $currentParcel);
        }

        if (self::DEBUG) {
            throw new Fontis_Australia_Model_Shipping_Carrier_Eparcel_Export_Exception(
                nl2br($this->log())
            );
        }
        
        /* Save file */
        $fileName = 'order_export_'.date("Ymd_His").'_eparcel.csv';
        $filePath = Mage::getBaseDir('export').'/'.$fileName;
        
        if ($eparcel->makeCsv($filePath)) {
            return $filePath;
        }

        throw new Fontis_Australia_Model_Shipping_Carrier_Eparcel_Export_Exception(
            "Unable to build .CSV file!"
        );
    }
    
    protected function getNewParcel()
    {
        $parcel = new Doghouse_Australia_Eparcel_Parcel_Carton();
        
        $parcel->isInsuranceRequired(true);
        
        $parcel->weightMax = $this->getDefault('parcel/weightmax');
        $parcel->width = (int) $this->getDefault('parcel/width');
        $parcel->height = (int) $this->getDefault('parcel/height');
        $parcel->length = (int) $this->getDefault('parcel/length');
        
        return $parcel;
    }
    
    protected function closeParcel(Doghouse_Australia_Eparcel $eparcel, Doghouse_Australia_Eparcel_Parcel $parcel)
    {
        $articleRecordClass = (bool) $this->getDefault('parcel/use_cubicweight') ?
            'Doghouse_Australia_Eparcel_Record_Article_CubicWeight' :
            'Doghouse_Australia_Eparcel_Record_Article';
                
        $goodRecords = $parcel->getGoodRecords();

        if (count($goodRecords) == 0) {
            return false;
        }
        
        $eparcel->addRecord(
            $parcel->processArticleRecord(
                new $articleRecordClass()
            )
        );
        
        if ((bool) $this->getDefault('good/use_multiplegoodrecords')) {
            foreach ($parcel->getGoodRecords() as $_goodRecord) {
                $eparcel->addRecord($_goodRecord);
            }
        }
        else {
            $goodRecord = new Doghouse_Australia_Eparcel_Record_Good();
            $goodRecord->originCountryCode = '';
            $goodRecord->hsTariffCode = '';
            $goodRecord->productClassification = null;
            $goodRecord->quantity = 1;
            
            foreach ($parcel->getGoodRecords() as $_goodRecord) {
                /* Set product type and description */
                $goodRecord->productType = $_goodRecord->productType;
                $goodRecord->description = str_replace(',', '', $_goodRecord->productType); // remove commas
                
                /* Add weight * quantity */
                $goodRecord->weight += $_goodRecord->weight * $_goodRecord->quantity;
                $goodRecord->unitValue += $_goodRecord->unitValue * $_goodRecord->quantity;
                $goodRecord->totalValue += $_goodRecord->totalValue;
            }
            
            $eparcel->addRecord($goodRecord);
        }
        
        return true;
    }

    
    protected function getConsignementRecord(Mage_Sales_Model_Order $order, Dhmedia_AustraliaPost_Eparcel_Parcel $parcel)
    {
        $consignementRecord = new Doghouse_Australia_Eparcel_Record_Consignement();
        
        $consignementRecord->chargeCode = 
                
        $consignementRecord->isSignatureRequired    = (bool) $this->getDefault('consignement/is_signature_required');
        $consignementRecord->addToAddressBook       = (bool) $this->getDefault('consignement/add_to_address_book');
        $consignementRecord->isRefPrintRequired     = (bool) $this->getDefault('consignement/print_ref1');
        $consignementRecord->isRef2PrintRequired    = (bool) $this->getDefault('consignement/print_ref2');
        
        /* AusPost said this was useless/pointless */
        //$consignementRecord->merchantConsigneeCode    = $order->getCustomerId(); // only returns if customer not anonymous

        $consignementRecord->consigneeName            = $order->getShippingAddress()->getName();
        $consignementRecord->consigneeAddressLine1    = $order->getShippingAddress()->getStreet1();
        $consignementRecord->consigneeAddressLine2    = $order->getShippingAddress()->getStreet2();
        $consignementRecord->consigneeAddressLine3    = $order->getShippingAddress()->getStreet3();
        $consignementRecord->consigneeAddressLine4    = $order->getShippingAddress()->getStreet4();
        $consignementRecord->consigneeSuburb          = $order->getShippingAddress()->getCity();
        $consignementRecord->consigneeStateCode       = $order->getShippingAddress()->getRegionCode();
        $consignementRecord->consigneePostcode        = $order->getShippingAddress()->getPostcode();
        $consignementRecord->consigneeCountryCode     = $order->getShippingAddress()->getCountry();
        $consignementRecord->consigneePhoneNumber     = $order->getShippingAddress()->getData('telephone');
        $consignementRecord->ref                      = $order->hasInvoices() ? $order->getInvoiceCollection()->getLastItem()->getData('increment_id') : "";
        $consignementRecord->ref2                     = $order->getRealOrderId();
        
        return $consignementRecord;
    }
    
    protected function _getChargeCode(Mage_Sales_Model_Order $order)
    {
        list ($carrierCode, $chargeCode) = explode('_', $order->getData('shipping_method'));
        
        if ($this->_isValidChargeCode($chargeCode)) {
            return $chargeCode;
        }
        
        /* Is this customer is in a ~business~ group ? */
        $isBusinessCustomer = in_array(
            $order->getData('customer_group_id'),
            explode(',',
                Mage::getStoreConfig('doghouse_eparcelexport/charge_codes/business_groups')
            )
        );
         
        return $isBusinessCustomer ?
            Mage::getStoreConfig('doghouse_eparcelexport/charge_codes/default_charge_code_business') :
            Mage::getStoreConfig('doghouse_eparcelexport/charge_codes/default_charge_code_individual');
    }
    
    protected function _isValidChargeCode($chargeCode)
    {
        return in_array($chargeCode, $this->_chargeCodes);
    }
    
    protected function getDefault($key)
    {
        if (! is_array($this->_defaults)) {
            $this->_defaults = Mage::getStoreConfig('doghouse_eparcelexport');
        }
        
        $_defaults = $this->_defaults;
        
    	foreach (explode('/',$key) as $keyPart) {
            $_defaults = $_defaults[$keyPart];
        }

        return $_defaults;
    }
}