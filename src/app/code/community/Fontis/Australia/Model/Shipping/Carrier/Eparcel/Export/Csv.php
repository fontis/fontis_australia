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
                    "Order #" . $order->getIncrementId() . " doesn't use Australia Post eParcel as its carrier!"
                );
            }

            $orderItems = $order->getItemsCollection();
            $currentParcel = $this->getNewParcel($order);
            $consignmentRecord = $this->getConsignmentRecord($order,$currentParcel);

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

                /* We have at least one Good, time to add the consignmentRecord if not done yet */
                if (! $consignmentRecord->isAddedToEparcel()) {
                    $eparcel->addRecord($consignmentRecord);
                }

                /* If current parcel can't fit extra item, close it, and open new parcel */
                if (! $currentParcel->canAddGood($goodRecord)) {
                    $this->closeParcel($eparcel, $currentParcel);
                    $currentParcel = $this->getNewParcel($order);
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

    /**
     * @param Mage_Sales_Model_Order $order
     * @return Doghouse_Australia_Eparcel_Parcel_Carton
     */
    protected function getNewParcel(Mage_Sales_Model_Order $order)
    {
        $parcel = new Doghouse_Australia_Eparcel_Parcel_Carton();

        $parcel->isInsuranceRequired(
            Mage::getStoreConfigFlag('carriers/eparcel/insurance_enable', $order->getStoreId())
        );

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
                $goodRecord->description = str_replace(',', '', $_goodRecord->description); // remove commas

                /* Add weight * quantity */
                $goodRecord->weight += $_goodRecord->weight * $_goodRecord->quantity;
                $goodRecord->unitValue += $_goodRecord->unitValue * $_goodRecord->quantity;
                $goodRecord->totalValue += $_goodRecord->totalValue;
            }

            $eparcel->addRecord($goodRecord);
        }

        return true;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Doghouse_Australia_Eparcel_Parcel $parcel
     * @return Doghouse_Australia_Eparcel_Record_Consignment
     */
    protected function getConsignmentRecord(Mage_Sales_Model_Order $order, Doghouse_Australia_Eparcel_Parcel $parcel)
    {
        /** @var Fontis_Australia_Helper_Eparcel $helper */
        $helper = Mage::helper('australia/eparcel');
        $consignmentRecord = new Doghouse_Australia_Eparcel_Record_Consignment();

        $consignmentRecord->chargeCode = $this->_getChargeCode($order);

        $consignmentRecord->isSignatureRequired    = (bool) $this->getDefault('consignement/is_signature_required');
        $consignmentRecord->addToAddressBook       = (bool) $this->getDefault('consignement/add_to_address_book');
        $consignmentRecord->isRefPrintRequired     = (bool) $this->getDefault('consignement/print_ref1');
        $consignmentRecord->isRef2PrintRequired    = (bool) $this->getDefault('consignement/print_ref2');

        /* AusPost said this was useless/pointless */
        //$consignmentRecord->merchantConsigneeCode    = $order->getCustomerId(); // only returns if customer not anonymous

        $shippingAddress = $order->getShippingAddress();
        $consignmentRecord->consigneeName            = $shippingAddress->getName();
        $consignmentRecord->consigneeAddressLine1    = $shippingAddress->getStreet1();
        $consignmentRecord->consigneeAddressLine2    = $shippingAddress->getStreet2();
        $consignmentRecord->consigneeAddressLine3    = $shippingAddress->getStreet3();
        $consignmentRecord->consigneeAddressLine4    = $shippingAddress->getStreet4();
        $consignmentRecord->consigneeSuburb          = $shippingAddress->getCity();
        $consignmentRecord->consigneeStateCode       = $shippingAddress->getRegionCode();
        $consignmentRecord->consigneePostcode        = $shippingAddress->getPostcode();
        $consignmentRecord->consigneeCountryCode     = $shippingAddress->getCountry();
        $consignmentRecord->consigneePhoneNumber     = $shippingAddress->getData('telephone');
        $consignmentRecord->ref                      = $order->hasInvoices() ? $order->getInvoiceCollection()->getLastItem()->getData('increment_id') : "";
        $consignmentRecord->ref2                     = $order->getRealOrderId();
        if ($helper->isEmailNotificationEnabled()) {
            $consignmentRecord->consigneeEmailAddress = $order->getCustomerEmail();
            $consignmentRecord->emailNotification = $helper->getEmailNotificationLevel();
        }

        return $consignmentRecord;
    }

    protected function _getChargeCode(Mage_Sales_Model_Order $order)
    {
        list ($carrierCode, $chargeCode) = explode('_', $order->getData('shipping_method'));

        /** @var Fontis_Australia_Helper_Eparcel $helper */
        $helper = Mage::helper('australia/eparcel');
        $chargeCode = strtoupper($chargeCode);
        if ($helper->isValidChargeCode($chargeCode)) {
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
