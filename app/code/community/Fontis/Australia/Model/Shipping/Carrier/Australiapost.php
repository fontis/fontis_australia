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
 * @category   Fontis
 * @package    Fontis_Australia
 * @author     Chris Norton
 * @copyright  Copyright (c) 2008 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Australia Post shipping model
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Model_Shipping_Carrier_Australiapost
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_code = 'australiapost';

    /**
     * Collects the shipping rates for Australia Post from the DRC API.
     *
     * @param Mage_Shipping_Model_Rate_Request $data
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {        
    	// Check if this method is active
		if (!$this->getConfigFlag('active')) 
		{
			return false;
		}
		
		// Check if this method is even applicable (shipping from Australia)
		$origCountry = Mage::getStoreConfig('shipping/origin/country_id', $this->getStore());
		if ($origCountry != "AU") 
		{
			return false;
		}

		$result = Mage::getModel('shipping/rate_result');

		// TODO: Add some more validations
		$frompcode = Mage::getStoreConfig('shipping/origin/postcode', $this->getStore());
		$topcode = $request->getDestPostcode();

		if ($request->getDestCountryId()) 
		{
			$destCountry = $request->getDestCountryId();
		} 
		else 
		{
			$destCountry = "AU";
		}

		// Here we get the weight (and convert it to grams) and set some
		// sensible defaults for other shipping parameters.	
		$sweight = (int)((float)$request->getPackageWeight() * (float)$this->getConfigData('weight_units'));
		$sheight = $swidth = $slength = 100;
		$shipping_num_boxes = 1;

		// Switch between domestic and international shipping methods based
		// on destination country.
		if($destCountry == "AU")
		{
			//============= Domestic Shipping ==================
			$shipping_methods = array('STANDARD', 'EXPRESS');

		    foreach($shipping_methods as $shipping_method)
		    {
				$drc = $this->_drcRequest($shipping_method, $frompcode, $topcode, $destCountry, $sweight, $slength, $swidth, $sheight, $shipping_num_boxes);

				if($drc['err_msg'] == 'OK')
				{
					// Check for registered post activation. If so, add extra options
					if($this->getConfigData('registered_post'))
					{
						$title_days = ($drc['days'] == 1) ? ' (1 day)' : ' (' . $drc['days'] . ' days)';
						$title = $this->getConfigData('name') . " " . 
								ucfirst(strtolower($shipping_method)) . 
								$title_days;
								
						$charge = $drc['charge'];
						$charge += $this->getConfigData('registered_post_charge');
						
						if($this->getConfigData('person_to_person'))
						{
							$charge += 5.50;
						}
						elseif($this->getConfigData('delivery_confirmation'))
						{
							$charge += 1.85;
						}
						
						$method = $this->_createMethod($request, $shipping_method, $title, $charge, $charge);
						$result->append($method);
					
						// Insurance only covers up to $5000 worth of goods.
						$packageValue = ($request->getPackageValue() > 5000) ? 5000 : $request->getPackageValue();
					
						// Insurance cost is $1.25 per $100 or part thereof. First $100 is
						// included in normal registered post costs.
						$insurance = (ceil($packageValue / 100) - 1) * 1.25;
						
						// Only add a new method if the insurance is different
						if($insurance > 0) {
						    $charge += $insurance;
					
						    $title = $this->getConfigData('name') . " " . ucfirst(strtolower($shipping_method)) . ' with Extra Cover';
						    $method = $this->_createMethod($request, $shipping_method . '_EC', $title, $charge, $charge);
						    $result->append($method);
						}
					}
					else
					{
						$title_days = ($drc['days'] == 1) ? ' (1 day)' : ' (' . $drc['days'] . ' days)';
						$title = $this->getConfigData('name') . " " . 
								ucfirst(strtolower($shipping_method)) . 
								$title_days;
						
						$method = $this->_createMethod($request, $shipping_method, $title, $drc['charge'], $drc['charge']);
						$result->append($method);
					}
				}
			}
		}
		else
		{
			//============= International Shipping ==================		
			// International shipping options are highly dependent upon whether 
			// or not you are using registered post.
			if($this->getConfigData('registered_post'))
			{
				//============= Registered Post ================
				// Registered Post International
				// Same price as Air Mail, plus $5. Extra cover is not available.
				// A weight limit of 2kg applies.				
				if($sweight <= 2000)
				{
					$drc = $this->_drcRequest('AIR', $frompcode, $topcode, $destCountry, $sweight, $slength, $swidth, $sheight, $shipping_num_boxes);
			
					if($drc['err_msg'] == 'OK')
					{
						$title_days = ($drc['days'] == 1) ? ' (1 day)' : ' (' . $drc['days'] . ' days)';
						$title = $this->getConfigData('name') . ' Registered Post International' . $title_days;
							
						// RPI is another 5 dollars.
						$charge = $drc['charge'] + 5;
						
						if($this->getConfigData('delivery_confirmation'))
						{
							$charge += 3.30;
						}
						
						$charge += $this->getConfigData('registered_post_charge');
			
						$method = $this->_createMethod($request, 'AIR', $title, $charge, $charge);
						$result->append($method);
					}
				}
				
				// Express Post International
				$drc = $this->_drcRequest('EPI', $frompcode, $topcode, $destCountry, $sweight, $slength, $swidth, $sheight, $shipping_num_boxes);
			
				if($drc['err_msg'] == 'OK')
				{
					$title_days = ($drc['days'] == 1) ? ' (1 day)' : ' (' . $drc['days'] . ' days)';
					$title = $this->getConfigData('name') . ' Express Post International' . $title_days;
					
					$charge = $drc['charge'];
					
					if($this->getConfigData('delivery_confirmation'))
					{
						$charge += 3.30;
					}
					
					$charge += $this->getConfigData('registered_post_charge');
			
					$method = $this->_createMethod($request, 'EPI', $title, $charge, $charge);
					$result->append($method);
					
					// Insurance only covers up to $5000 worth of goods.
					$packageValue = ($request->getPackageValue() > 5000) ? 5000 : $request->getPackageValue();
				
					// Insurance cost is $2.25 per $100 or part thereof. First $100 is $8.45.
					$insurance = 8.45 + (ceil($packageValue / 100) - 1) * 1.25;
					$charge += $insurance;
					
					$title = $this->getConfigData('name') . ' Express Post International with Extra Cover';
					$method = $this->_createMethod($request, 'EPI-EC', $title, $charge, $charge);
					$result->append($method);
				}
				
				// Express Courier International
				// TODO: Create a table for this method.
			}
			else
			{
				//============= Standard Post ================
				// Sea Shipping
				$drc = $this->_drcRequest('SEA', $frompcode, $topcode, $destCountry, $sweight, $slength, $swidth, $sheight, $shipping_num_boxes);
			
				if($drc['err_msg'] == 'OK')
				{
					$title_days = ($drc['days'] == 1) ? ' (1 day)' : ' (' . $drc['days'] . ' days)';
					$title = $this->getConfigData('name') . ' Sea Mail' . 
							$title_days;
			
					$method = $this->_createMethod($request, 'SEA', $title, $drc['charge'], $drc['charge']);
					$result->append($method);
				}
			
				// Air Mail
				$drc = $this->_drcRequest('AIR', $frompcode, $topcode, $destCountry, $sweight, $slength, $swidth, $sheight, $shipping_num_boxes);
			
				if($drc['err_msg'] == 'OK')
				{
					$title_days = ($drc['days'] == 1) ? ' (1 day)' : ' (' . $drc['days'] . ' days)';
					$title = $this->getConfigData('name') . ' Air Mail' . 
							$title_days;
			
					$method = $this->_createMethod($request, 'AIR', $title, $drc['charge'], $drc['charge']);
					$result->append($method);
				}
			
				// Express Post International
				$drc = $this->_drcRequest('EPI', $frompcode, $topcode, $destCountry, $sweight, $slength, $swidth, $sheight, $shipping_num_boxes);
			
				if($drc['err_msg'] == 'OK')
				{
					$title_days = ($drc['days'] == 1) ? ' (1 day)' : ' (' . $drc['days'] . ' days)';
					$title = $this->getConfigData('name') . ' Express Post International' . 
							$title_days;
			
					$method = $this->_createMethod($request, 'EPI', $title, $drc['charge'], $drc['charge']);
					$result->append($method);
				}
							
				// Express Courier International
				// TODO: Create a table for this method.		
			}

		}
		
		Mage::log(print_r($result->asArray(), true), null, 'rates.log');
				
        return $result;
    }
    
    protected function _createMethod($request, $method_code, $title, $price, $cost)
    {
		$method = Mage::getModel('shipping/rate_result_method');

		$method->setCarrier('australiapost');
		$method->setCarrierTitle($this->getConfigData('title'));

		$method->setMethod($method_code);
		$method->setMethodTitle($title);

		$method->setPrice($this->getFinalPriceWithHandlingFee($price));
		$method->setCost($cost);
		
		return $method;
    }
    
    protected function _curl_get_file_contents($url)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $url);
        $contents = curl_exec($c);
        curl_close($c);
            
        if ($contents)
        {
            return $contents;
        }
        else
        {
            return false;
        }
    }
    
	protected function _drcRequest($service, $fromPostCode, $toPostCode, $destCountry, $weight, $length, $width, $height, $num_boxes)
	{
		// Construct the appropriate URL and send all the information
		// to the Australia Post DRC.
		$url = "http://drc.edeliver.com.au/ratecalc.asp?" . 
			"Pickup_Postcode=" . rawurlencode($fromPostCode) .
			"&Destination_Postcode=" . rawurlencode($toPostCode) .
			"&Country=" . rawurlencode($destCountry) .
			"&Weight=" . rawurlencode($weight) .
			"&Service_Type=" . rawurlencode($service) . 
			"&Height=" . rawurlencode($height) . 
			"&Width=" . rawurlencode($width) . 
			"&Length=" . rawurlencode($length) .
			"&Quantity=" . rawurlencode($num_boxes);

		if(ini_get('allow_url_fopen'))
        {
            Mage::log('Using fopen URL wrappers', null, 'fontis_australia.log');
            $drc_result = file($url);
        }
        else if(extension_loaded('curl'))
        {
            Mage::log('fopen URL wrappers unavailable, using curl', null, 'fontis_australia.log');
            try {
                $drc_result = $this->_curl_get_file_contents($url);
            } catch(Exception $e) {
                Mage::log($e);
                $drc_result = array();
                $drc_result['err_msg'] = 'FAIL';
            }

            $drc_result = explode("\n",$drc_result);
                                                
            //clean up array
            foreach($drc_result as $k => $vals) {
                if($vals == '') unset($drc_result[$k]);
            }
        }
        else
        {
            Mage::log('No download method available, could not contact DRC!', null, 'fontis_australia.log');
            $a = array();
            $a['err_msg'] = 'FAIL';
            return $a;
        }
        Mage::log("DRC result: " . $drc_result, null, 'fontis_australia.log');

		$result = array();
		foreach($drc_result as $vals)
		{
			$tokens = split("=", $vals);
			if(isset($tokens[1])) {
    			$result[$tokens[0]] = trim($tokens[1]);
    	    } else {
    	        return array('err_msg' => 'Parsing error on Australia Post results');
    	    }
		}
		
		return $result;
	}

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return array('australiapost' => $this->getConfigData('name'));
    }

}
