<?php 

class MyHealthWarehouse_Australia_Model_Shipping_Carrier_Australiapost extends Fontis_Australia_Model_Shipping_Carrier_Australiapost {

    const XML_PATH_STANDARD_SHIPPING_ENABLED    = 'carriers/australiapost/standard_method_enabled';
    const XML_PATH_STANDARD_SHIPPING_TITLE      = 'carriers/australiapost/standard_method_title';
    
    const XML_PATH_EXPRESS_SHIPPING_ENABLED     = 'carriers/australiapost/express_method_enabled';
    const XML_PATH_EXPRESS_SHIPPING_TITLE       = 'carriers/australiapost/express_method_title';
    
    /**
     * We want a fixed amount of days for the shipping method title
     * Instead of overriding the whole method (which is huge), we just change the title if standard of express shipping is used
     * 
     * @param  Mage_Shipping_Model_Rate_Request $request [description]
     * @return [type]                                    [description]
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
        
        //instance of Mage_Shipping_Model_Rate_Result
        //$australiapost = parent::collectRates($request);
        $australiapost = $this->collectRatesCopy($request);


        $rates = $australiapost->getAllRates();


        foreach($rates as $rate) {

            //instance of Mage_Shipping_Model_Rate_Result_Method
            $ratemethod = $rate->getMethod();

            switch($ratemethod) {
                case 'STANDARD':
                    $rate->setMethodTitle($this->getConfigData('standard_method_title'));
                    break;
                
                case 'EXPRESS':
                    $rate->setMethodTitle($this->getConfigData('express_method_title'));
                    break;

                default:
                    break;
            }

        }

        return $australiapost;

    }


    public function collectRatesCopy($request) {
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
            
            /* DHMEDIA EDIT START*/

            //$shipping_methods = array('STANDARD', 'EXPRESS');
            
            $shipping_methods = array();

            if(Mage::getStoreConfig(self::XML_PATH_STANDARD_SHIPPING_ENABLED)) {
                array_push($shipping_methods, 'STANDARD');
            }

            if(Mage::getStoreConfig(self::XML_PATH_EXPRESS_SHIPPING_ENABLED)) {

                array_push($shipping_methods, 'EXPRESS');
            }

            /* DHMEDIA EDIT END*/

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
    
    protected function _drcRequest($service, $fromPostCode, $toPostCode, $destCountry, $weight, $length, $width, $height, $num_boxes)
	{
            // Construct the appropriate URL and send all the information
            // to the Australia Post DRC.
		
            // don't make a call if the postcodes are not populated.
            if(is_null($fromPostCode) || is_null($toPostCode)) {
                return array('err_msg' => 'One of To or From Postcodes are missing');
            }
            
            /**
             * Lucas van Staden @ doghouse media (lucas@dhmedia.com.au)
             * Add a drc call cache to session. (valid for 1 hour)
             * The call to drc is made at least 3-4 times, using the same data (ugh)
             *  - Add to cart (sometimes * 2)
             *  - Checkout * 2
             * 
             * Create a lookup cache based on FromPostcode->ToPostcode combination, and re-use cached data
             * The end result will kill lookups in checkout process, as it was actually done at cart, which will speed checkout up.
             */
            
            $drcCache = Mage::getSingleton('checkout/session')->getDrcCache();
            if(!$drcCache) {
                $drcCache = array();
            } else {
                // wrap it in a try block, s it is used durng checkout.
                // prevents any mistake from stopping checkout as a new lookup will be done.
                try {
                    $time = time();
                    if($this->getConfigFlag('cache') 
                            && array_key_exists($fromPostCode, $drcCache) 
                            && array_key_exists($toPostCode, $drcCache[$fromPostCode])
                            && $time - $drcCache[$fromPostCode][$toPostCode]['timestamp'] < 3600) {
                        if ($this->getConfigFlag('debug')) {
                            Mage::log('Using cached drc lookup for ' . $fromPostCode . '/' . $toPostCode, null, 'fontis_australia.log');
                        }
                        return $drcCache[$fromPostCode][$toPostCode]['result'];
                    }
                } catch (Exception $e) {
                    mage::logException($e);
                }   
            }
            
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
            
        if(extension_loaded('curl'))
        {
            
            if ($this->getConfigFlag('debug')) {
                Mage::log('Using curl', null, 'fontis_australia.log');
            }
            try {
                // use CURL rather tan php fopen url wroppers.
                // curl is faster.
                // see http://stackoverflow.com/questions/555523/file-get-contents-vs-curl-what-has-better-performance
                // and do it the 'magento way'
                // @author Lucas van Staden from Doghouse Media (lucas@dhmedia.com.au)
                $curl = new Varien_Http_Adapter_Curl();
                $curl->setConfig(array(
                        'timeout'   => 15    //Timeout in no of seconds
                 ));
                $curl->write(Zend_Http_Client::GET, $url);
                $curlData = $curl->read();
                $drc_result = Zend_Http_Response::extractBody($curlData);
                $curl->close();
            } catch(Exception $e) {
                Mage::log($e);
                $drc_result = array();
                $drc_result['err_msg'] = 'FAIL';
            }

            $drc_result = explode("\n",$drc_result);
            //clean up array
            $drc_result = array_map('trim', $drc_result);
            $drc_result = array_filter($drc_result);                                    
            
        }
        else if(ini_get('allow_url_fopen'))
        {
            if ($this->getConfigFlag('debug')) {
                Mage::log('Using fopen URL wrappers', null, 'fontis_australia.log');
            }
            
            $drc_result = file($url);
        }
        else
        {
            Mage::log('No download method available, could not contact DRC!', null, 'fontis_australia.log');
            $a = array();
            $a['err_msg'] = 'FAIL';
            return $a;
        }
        Mage::log("DRC result: " . print_r($drc_result,true), null, 'fontis_australia.log');

		$result = array();
		foreach($drc_result as $vals)
		{
			$tokens = explode("=", $vals);
			if(isset($tokens[1])) {
    			$result[$tokens[0]] = trim($tokens[1]);
    	    } else {
    	        return array('err_msg' => 'Parsing error on Australia Post results');
    	    }
		}
		
                // save the drc data to lookup cache, with a timestamp.
                if(is_array($drcCache)){
                    $drcCache[$fromPostCode][$toPostCode] = array('result'=>$result,'timestamp'=>time());         
                    Mage::getSingleton('checkout/session')->setDrcCache($drcCache);
                }    
		return $result;
	}

}