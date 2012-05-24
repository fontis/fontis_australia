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

class Fontis_Australia_Model_Mysql4_Shipping_Carrier_Eparcel extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('australia/eparcel', 'pk');
    }

    public function getRate(Mage_Shipping_Model_Rate_Request $request)
    {
        $read = $this->_getReadAdapter();
        $write = $this->_getWriteAdapter();

		$postcode = $request->getDestPostcode();
        $table = $this->getMainTable();

        $insuranceStep = (float)Mage::getConfig()->getNode('default/carriers/eparcel/insurance_step');
        $insuranceCostPerStep = (float)Mage::getConfig()->getNode('default/carriers/eparcel/insurance_cost_per_step');
        $signatureRequired = Mage::getConfig()->getNode('default/carriers/eparcel/signature_required');
        if($signatureRequired) {
            $signatureCost = (float)Mage::getConfig()->getNode('default/carriers/eparcel/signature_cost');
        } else {
            $signatureCost = 0;
        }
        
        Mage::log($request->getDestCountryId());
        Mage::log($request->getDestRegionId());
        Mage::log($postcode);
        Mage::log(var_export($request->getConditionName(), true));
		
        for ($j=0;$j<5;$j++)
		{

            $select = $read->select()->from($table);


            switch($j) {
                case 0:
                    $select->where(
                        $read->quoteInto(" (dest_country_id=? ", $request->getDestCountryId()).
                            $read->quoteInto(" AND dest_region_id=? ", $request->getDestRegionId()).
                            $read->quoteInto(" AND dest_zip=?) ", $postcode)
                        );
                    break;
                case 1:
                    $select->where(
                       $read->quoteInto("  (dest_country_id=? ", $request->getDestCountryId()).
                            $read->quoteInto(" AND dest_region_id=? AND dest_zip='') ", $request->getDestRegionId())
                       );
                    break;

                case 2:
                    $select->where(
                       $read->quoteInto("  (dest_country_id=? AND dest_region_id='0' AND dest_zip='') ", $request->getDestCountryId())
                    );
                    break;
                case 3:
                    $select->where(
                        $read->quoteInto("  (dest_country_id=? AND dest_region_id='0' ", $request->getDestCountryId()).
                        $read->quoteInto("  AND dest_zip=?) ", $postcode)
                        );
                    break;
                case 4:
                    $select->where(
                            "  (dest_country_id='0' AND dest_region_id='0' AND dest_zip='')"
                );
                    break;
            }


            if (is_array($request->getConditionName())) {
                $i = 0;
                foreach ($request->getConditionName() as $conditionName) {
                    if ($i == 0) {
                        $select->where('condition_name=?', $conditionName);
                    } else {
                        $select->orWhere('condition_name=?', $conditionName);
                    }
                    $select->where('condition_from_value<=?', $request->getData($conditionName));
                    $select->where('condition_to_value>=?', $request->getData($conditionName));

                    $i++;
                }
            } else {
                $select->where('condition_name=?', $request->getConditionName());
                $select->where('condition_from_value<=?', $request->getData($request->getConditionName()));
                $select->where('condition_to_value>=?', $request->getData($request->getConditionName()));
            }
            $select->where('website_id=?', $request->getWebsiteId());

            $select->order('dest_country_id DESC');
            $select->order('dest_region_id DESC');
            $select->order('dest_zip DESC');
            $select->order('condition_from_value DESC');

            // pdo has an issue. we cannot use bind

            $newdata=array();
            Mage::log($select->__toString());
            $row = $read->fetchAll($select);
            if (!empty($row) && ($j<5))
            {
                // have found a result or found nothing and at end of list!
                foreach ($row as $data) {
		            try {
                        $price = (float)($data['price']);
                        
                        // add per-Kg cost
                        $conditionValue = (float)($request->getData($request->getConditionName()));
                        $price += (float)($data['price_per_kg']) * $conditionValue;
                    
                        // add signature cost
                        $price += $signatureCost;
                        
                        // add version without insurance
                        $data['price'] = (string)$price;
                        $newdata[]=$data;

                        // add version with insurance
                        // work out how many insurance 'steps' we have
                        $steps = ceil($request->getPackageValue() / $insuranceStep);
                        Mage::log("Insurance steps: $steps");
                        // add on number of 'steps' multiplied by the
                        // insurance cost per step
                        $insuranceCost = $insuranceCostPerStep * $steps;
                        Mage::log("Insurance cost: $insuranceCost");
                        $price += $insuranceCost;
                        
                        $data['price'] = (string)$price;
                        $data['delivery_type'] .= " with TransitCover";
                        $newdata[]=$data;

                    } catch(Exception $e) {
                        Mage::log($e->getMessage());
                    }
                }
                break;
            }
		}
        Mage::log(var_export($newdata, true));
        return $newdata;
    }

    public function uploadAndImport(Varien_Object $object)
    {
    	Mage::log('Eparcel uploadAndImport');
        $csvFile = $_FILES["groups"]["tmp_name"]["eparcel"]["fields"]["import"]["value"];

        if (!empty($csvFile)) {

            $csv = trim(file_get_contents($csvFile));

            $table = Mage::getSingleton('core/resource')->getTableName('australia/eparcel');

            $websiteId = $object->getScopeId();
            $websiteModel = Mage::app()->getWebsite($websiteId);
            /*
            getting condition name from post instead of the following commented logic
            */

            if (isset($_POST['groups']['eparcel']['fields']['condition_name']['inherit'])) {
                $conditionName = (string)Mage::getConfig()->getNode('default/carriers/eparcel/condition_name');
            } else {
                $conditionName = $_POST['groups']['eparcel']['fields']['condition_name']['value'];
            }

            $conditionFullName = Mage::getModel('australia/shipping_carrier_eparcel')->getCode('condition_name_short', $conditionName);
            if (!empty($csv)) {
                $exceptions = array();
                $csvLines = explode("\n", $csv);
                $csvLine = array_shift($csvLines);
                $csvLine = $this->_getCsvValues($csvLine);
                if (count($csvLine) < 8) {
                    $exceptions[0] = Mage::helper('shipping')->__('Invalid Table Rates File Format');
                }

                $countryCodes = array();
                $regionCodes = array();
                foreach ($csvLines as $k=>$csvLine) {
                    $csvLine = $this->_getCsvValues($csvLine);
                    if (count($csvLine) > 0 && count($csvLine) < 8) {
                        $exceptions[0] = Mage::helper('shipping')->__('Invalid Table Rates File Format');
                    } else {
                        $countryCodes[] = $csvLine[0];
                        $regionCodes[] = $csvLine[1];
                    }
                }

                if (empty($exceptions)) {
                    $data = array();
                    $countryCodesToIds = array();
                    $regionCodesToIds = array();
                    $countryCodesIso2 = array();

                    $countryCollection = Mage::getResourceModel('directory/country_collection')->addCountryCodeFilter($countryCodes)->load();
                    foreach ($countryCollection->getItems() as $country) {
                        $countryCodesToIds[$country->getData('iso3_code')] = $country->getData('country_id');
                        $countryCodesToIds[$country->getData('iso2_code')] = $country->getData('country_id');
                        $countryCodesIso2[] = $country->getData('iso2_code');
                    }

                    $regionCollection = Mage::getResourceModel('directory/region_collection')
                        ->addRegionCodeFilter($regionCodes)
                        ->addCountryFilter($countryCodesIso2)
                        ->load();

                    foreach ($regionCollection->getItems() as $region) {
                        $regionCodesToIds[$region->getData('code')] = $region->getData('region_id');
                    }

                    foreach ($csvLines as $k=>$csvLine) {
                        $csvLine = $this->_getCsvValues($csvLine);

                        if (empty($countryCodesToIds) || !array_key_exists($csvLine[0], $countryCodesToIds)) {
                            $countryId = '0';
                            if ($csvLine[0] != '*' && $csvLine[0] != '') {
                                $exceptions[] = Mage::helper('shipping')->__('Invalid Country "%s" in the Row #%s', $csvLine[0], ($k+1));
                            }
                        } else {
                            $countryId = $countryCodesToIds[$csvLine[0]];
                        }

                        if (empty($regionCodesToIds) || !array_key_exists($csvLine[1], $regionCodesToIds)) {
                            $regionId = '0';
                            if ($csvLine[1] != '*' && $csvLine[1] != '') {
                                $exceptions[] = Mage::helper('shipping')->__('Invalid Region/State "%s" in the Row #%s', $csvLine[1], ($k+1));
                            }
                        } else {
                            $regionId = $regionCodesToIds[$csvLine[1]];
                        }

                        if ($csvLine[2] == '*' || $csvLine[2] == '') {
                            $zip = '';
                        } else {
                            $zip = $csvLine[2];
                        }

                        if (!$this->_isPositiveDecimalNumber($csvLine[3]) || $csvLine[3] == '*' || $csvLine[3] == '') {
                            $exceptions[] = Mage::helper('shipping')->__('Invalid %s "%s" in the Row #%s', $conditionFullName, $csvLine[3], ($k+1));
                        } else {
                            $csvLine[3] = (float)$csvLine[3];
                        }

                        if (!$this->_isPositiveDecimalNumber($csvLine[4]) || $csvLine[4] == '*' || $csvLine[4] == '') {
                            $exceptions[] = Mage::helper('shipping')->__('Invalid %s "%s" in the Row #%s', $conditionFullName, $csvLine[4], ($k+1));
                        } else {
                            $csvLine[4] = (float)$csvLine[4];
                        }

                        if (!$this->_isPositiveDecimalNumber($csvLine[5])) {
                            $exceptions[] = Mage::helper('shipping')->__('Invalid Shipping Price "%s" in the Row #%s', $csvLine[5], ($k+1));
                        } else {
                            $csvLine[5] = (float)$csvLine[5];
                        }

                        if (!$this->_isPositiveDecimalNumber($csvLine[6])) {
                            $exceptions[] = Mage::helper('shipping')->__('Invalid Shipping Price per Kg "%s" in the Row #%s', $csvLine[6], ($k+1));
                        } else {
                            $csvLine[6] = (float)$csvLine[6];
                        }
                        
                        $data[] = array('website_id'=>$websiteId, 'dest_country_id'=>$countryId, 'dest_region_id'=>$regionId, 'dest_zip'=>$zip, 'condition_name'=>$conditionName, 'condition_from_value'=>$csvLine[3],'condition_to_value'=>$csvLine[4], 'price'=>$csvLine[5], 'price_per_kg'=>$csvLine[6], 'delivery_type'=>$csvLine[7]);
                        $dataDetails[] = array('country'=>$csvLine[0], 'region'=>$csvLine[1]);
                    }
                }

                if (empty($exceptions)) {
                    $connection = $this->_getWriteAdapter();

                    $condition = array(
                        $connection->quoteInto('website_id = ?', $websiteId),
                        $connection->quoteInto('condition_name = ?', $conditionName),
                    );
                    $connection->delete($table, $condition);

                    Mage::log(count($data)." lines read from CSV");
                    foreach($data as $k=>$dataLine) {
                        try {
                            // convert comma-seperated postcode/postcode range
                            // string into an array
                            $postcodes = array();
                            foreach(explode(',', $dataLine['dest_zip']) as $postcodeEntry) {
                                $postcodeEntry = explode("-", trim($postcodeEntry));
                                if(count($postcodeEntry) == 1) {
                                    Mage::log("Line $k, single postcode: ".$postcodeEntry[0]);
                                    // if the postcode entry is length 1, it's
                                    // just a single postcode
                                    $postcodes[] = $postcodeEntry[0];
                                } else {
                                    // otherwise it's a range, so convert that
                                    // to a sequence of numbers
                                    $pcode1 = (int)$postcodeEntry[0];
                                    $pcode2 = (int)$postcodeEntry[1];
                                    Mage::log("Line $k, postcode range: $pcode1-$pcode2");
                                    
                                    $postcodes = array_merge($postcodes, range(min($pcode1, $pcode2), max($pcode1, $pcode2)));
                                }
                            }
                            
                            Mage::log(var_export($postcodes, true));
                            foreach($postcodes as $postcode) {
                                $dataLine['dest_zip'] = str_pad($postcode, 4, "0", STR_PAD_LEFT);
                                $connection->insert($table, $dataLine);
                            }
                        } catch (Exception $e) {
                            //$exceptions[] = Mage::helper('shipping')->__('ERROR Row #%s (Country "%s", Region/State "%s", Zip "%s" and Value "%s")', ($k+1), $dataDetails[$k]['country'], $dataDetails[$k]['region'], $dataLine['dest_zip'], $dataLine['condition_value']);
                            Mage::log($e->getMessage());
                            $exceptionas[] = $e->getMessage();
                        }
                    }
                }

                if (!empty($exceptions)) {
                    throw new Exception( "\n" . implode("\n", $exceptions) );
                }
            }
        }
    }

    protected function _getCsvValues($string, $separator=",")
    {
        $elements = explode($separator, trim($string));
        for ($i = 0; $i < count($elements); $i++) {
            $nquotes = substr_count($elements[$i], '"');
            if ($nquotes %2 == 1) {
                for ($j = $i+1; $j < count($elements); $j++) {
                    if (substr_count($elements[$j], '"') > 0) {
                        // Put the quoted string's pieces back together again
                        array_splice($elements, $i, $j-$i+1, implode($separator, array_slice($elements, $i, $j-$i+1)));
                        break;
                    }
                }
            }
            if ($nquotes > 0) {
                // Remove first and last quotes, then merge pairs of quotes
                $qstr =& $elements[$i];
                $qstr = substr_replace($qstr, '', strpos($qstr, '"'), 1);
                $qstr = substr_replace($qstr, '', strrpos($qstr, '"'), 1);
                $qstr = str_replace('""', '"', $qstr);
            }
            $elements[$i] = trim($elements[$i]);
        }
        return $elements;
    }

    protected function _isPositiveDecimalNumber($n)
    {
        return preg_match ("/^[0-9]+(\.[0-9]*)?$/", $n);
    }

}
