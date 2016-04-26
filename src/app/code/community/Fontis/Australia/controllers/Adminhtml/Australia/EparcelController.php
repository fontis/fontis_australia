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
 * @author     Chris Norton
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Controller handling order export requests.
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Adminhtml_Australia_EparcelController extends Mage_Adminhtml_Controller_Action
{
    const ADMINHTML_SALES_ORDER_INDEX = 'adminhtml/sales_order/index';

    /**
     * Generate and export a CSV file for the given orders.
     */
    public function exportAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $this->_redirect(self::ADMINHTML_SALES_ORDER_INDEX);
        }

        $orderIds = $this->getRequest()->getPost('order_ids', array());

        try {
            // Build the CSV and retrieve its path
            $filePath = Mage::getModel('australia/shipping_carrier_eparcel_export_csv')->exportOrders($orderIds);

            // Download the file
            $this->_prepareDownloadResponse(basename($filePath), file_get_contents($filePath));
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
            $this->_redirect(self::ADMINHTML_SALES_ORDER_INDEX);
        }
    }

    /**
     * Export the eParcel table rates as a CSV file.
     */
    public function exportTableratesAction()
    {
        $rates = Mage::getResourceModel('australia/shipping_carrier_eparcel_collection');
        $response = array(
            array(
                'Country',
                'Region/State',
                'Postcodes',
                'Weight from',
                'Weight to',
                'Parcel Cost',
                'Cost Per Kg',
                'Delivery Type',
                'Charge Code Individual',
                'Charge Code Business'
            )
        );

        foreach ($rates as $rate) {
            $countryId = $rate->getData('dest_country_id');
            $countryCode = Mage::getModel('directory/country')->load($countryId)->getIso3Code();
            $regionId = $rate->getData('dest_region_id');
            $regionCode = Mage::getModel('directory/region')->load($regionId)->getCode();

            $response[] = array(
                $countryCode,
                $regionCode,
                $rate->getData('dest_zip'),
                $rate->getData('condition_from_value'),
                $rate->getData('condition_to_value'),
                $rate->getData('price'),
                $rate->getData('price_per_kg'),
                $rate->getData('delivery_type'),
                $rate->getData('charge_code_individual'),
                $rate->getData('charge_code_business')
            );
        }

        $csv = new Varien_File_Csv();
        $temp = tmpfile();

        foreach ($response as $responseRow) {
            $csv->fputcsv($temp, $responseRow);
        }

        rewind($temp);

        $contents = stream_get_contents($temp);
        $this->_prepareDownloadResponse('tablerates.csv', $contents);

        fclose($temp);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton("admin/session")->isAllowed("sales/order");
    }
}
