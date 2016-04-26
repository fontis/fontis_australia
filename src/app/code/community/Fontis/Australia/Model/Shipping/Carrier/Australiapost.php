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
 * @author     Ronilo Carr
 * @author     Thai Phan
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

use Auspost\Common\Auspost;
use Auspost\Postage\PostageClient;
use Auspost\Postage\Enum\ServiceCode;
use Auspost\Postage\Enum\ServiceOption;

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
    const EXTRA_COVER_LIMIT = 5000;

    protected $_code = 'australiapost';

    /** @var PostageClient */
    protected $_client = null;

    /** @var Mage_Shipping_Model_Rate_Result  */
    protected $_result = null;

    public function __construct()
    {
        /** @var Fontis_Australia_Helper_Australiapost $helper */
        $helper = Mage::helper('australia/australiapost');
        $apiKey = $this->getConfigData('api_key');
        $config = array();

        if ($helper->isAustraliaPostDeveloperMode()) {
            $config = array(
                'developer_mode' => true
            );
        } else if ($apiKey) {
            //TODO: Specify the encrypted backend model in config.xml so we no longer need to decrypt this manually. This will be a breaking change.
            $config = array(
                'auth_key' => Mage::helper('core')->decrypt($apiKey)
            );
        } else {
            Mage::log('You need a valid API key in order to use this feature.', null, 'fontis_australia.log');
        }

        if (!empty($config)) {
            $this->_client = Auspost::factory($config)->get('postage');
        }

        $this->_result = Mage::getModel('shipping/rate_result');
    }

    /**
     * Collects the shipping rates for Australia Post from the REST API.
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result|bool
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        // Check if this method is active
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // Check if this method is even applicable (shipping from Australia)
        $origCountry = Mage::getStoreConfig('shipping/origin/country_id', $request->getStore());
        if ($origCountry != Fontis_Australia_Helper_Data::AUSTRALIA_COUNTRY_CODE) {
            return false;
        }

        if ($this->_client == null) {
            return false;
        }

        $fromPostcode = (int)Mage::getStoreConfig('shipping/origin/postcode', $this->getStore());
        $toPostcode = (int)$request->getDestPostcode();

        $destCountry = $request->getDestCountryId();
        if (!$destCountry) {
            $destCountry = Fontis_Australia_Helper_Data::AUSTRALIA_COUNTRY_CODE;
        }

        /** @var Fontis_Australia_Helper_Australiapost $helper */
        $helper = Mage::helper('australia/australiapost');

        $weight = (int)$request->getPackageWeight();
        $length = (int)$helper->getAttribute($request, 'length');
        $width = (int)$helper->getAttribute($request, 'width');
        $height = (int)$helper->getAttribute($request, 'height');

        $extraCover = max((int)$request->getPackageValue(), self::EXTRA_COVER_LIMIT);

        $config = array(
            'from_postcode' => $fromPostcode,
            'to_postcode' => $toPostcode,
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'weight' => $weight,
            'country_code' => $destCountry
        );
        $this->_getQuotes($extraCover, $config);

        $_result = $this->_result->asArray();
        if (empty($_result)) {
            return false;
        }

        return $this->_result;
    }

    protected function _getQuotes($extraCover, $config)
    {
        $destCountry = $config['country_code'];
        try {
            if ($destCountry == Fontis_Australia_Helper_Data::AUSTRALIA_COUNTRY_CODE) {
                $services = $this->_client->listDomesticParcelServices($config);
            } else {
                $services = $this->_client->listInternationalParcelServices($config);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return;
        }

        // TODO: Clean up this logic
        $allowedMethods = $this->getAllowedMethods();
        $extraCoverParent = $this->getCode('extra_cover');
        foreach ($services['services']['service'] as $service) {
            $serviceCode = $service['code']; // e.g. AUS_PARCEL_REGULAR

            if (in_array($serviceCode, $allowedMethods)) {
                $serviceName = $service['name']; // e.g. Parcel Post
                $servicePrice = $service['price'];

                // Just add the shipping method if the call to Australia Post
                // returns no options for that method
                if (
                    !isset($service['options']['option']) &&
                    $this->_isAvailableShippingMethod($serviceName, $destCountry)
                ) {
                    $method = $this->createMethod($serviceCode, $serviceName, $servicePrice);
                    $this->_result->append($method);
                    // If a shipping method has a bunch of options, we will have to
                    // create a specific method for each of the variants
                } else {
                    $serviceOption = $service['options']['option'];

                    // Unlike domestic shipping methods where the "default"
                    // method is listed as simply another service option (this
                    // allows us to simply loop through each one), we have to
                    // extrapolate the default international shipping method
                    // from what we know about the service itself
                    if (
                        $destCountry != Fontis_Australia_Helper_Data::AUSTRALIA_COUNTRY_CODE &&
                        $this->_isAvailableShippingMethod($serviceName, $destCountry)
                    ) {
                        $method = $this->createMethod($serviceCode, $serviceName, $servicePrice);
                        $this->_result->append($method);
                    }

                    // Checks to see if the API has returned either a single
                    // service option or an array of them. If it is a single
                    // option then turn it into an array.
                    if (isset($serviceOption['name'])) {
                        $serviceOption = array($serviceOption);
                    }

                    foreach ($serviceOption as $option) {
                        $serviceOptionName = $option['name'];
                        $serviceOptionCode = $option['code'];

                        $config = array_merge($config, array(
                            'service_code' => $serviceCode,
                            'option_code' => $serviceOptionCode,
                            'extra_cover' => $extraCover
                        ));
                        try {
                            if ($destCountry == Fontis_Australia_Helper_Data::AUSTRALIA_COUNTRY_CODE) {
                                $postage = $this->_client->calculateDomesticParcelPostage($config);
                            } else {
                                $postage = $this->_client->calculateInternationalParcelPostage($config);
                            }
                        } catch (Exception $e) {
                            continue;
                        }
                        $servicePrice = $postage['postage_result']['total_cost'];

                        /** @var Fontis_Australia_Helper_Clickandsend $clickandsendHelper */
                        $clickandsendHelper = Mage::helper('australia/clickandsend');

                        // Create a shipping method with only the top-level options
                        $_finalCode = $serviceCode . '_' . $serviceOptionCode;
                        $_finalName = $serviceName . ' (' . $serviceOptionName . ')';
                        if (
                            $this->_isAvailableShippingMethod($_finalName, $destCountry) &&
                            // The following shipping methods and shipping options don't work with
                            // the Click & Send CSV import service.
                            !(
                                in_array($serviceOptionCode, $clickandsendHelper->getDisallowedServiceOptions()) &&
                                in_array($serviceCode, $clickandsendHelper->getDisallowedServiceCodes()) &&
                                $clickandsendHelper->isClickAndSendEnabled() &&
                                $clickandsendHelper->isFilterShippingMethods()
                            )
                        ) {
                            $method = $this->createMethod($_finalCode, $_finalName, $servicePrice);
                            $this->_result->append($method);
                        }

                        // Add the extra cover options (these are suboptions of
                        // the top-level options)
                        if (
                            array_key_exists($serviceOptionCode, $extraCoverParent) &&
                            // The Click & Send CSV import doesn't work with Extra Cover so we
                            // will need to disable the option if it has been activated. The
                            // fields are there in the specification but I couldn't get it to
                            // import at all.
                            !(
                                $clickandsendHelper->isClickAndSendEnabled() &&
                                $clickandsendHelper->isFilterShippingMethods()
                            )
                        ) {
                            try {
                                if ($destCountry == Fontis_Australia_Helper_Data::AUSTRALIA_COUNTRY_CODE) {
                                    $config = array_merge($config, array(
                                        'suboption_code' => ServiceOption::AUS_SERVICE_OPTION_EXTRA_COVER,
                                    ));
                                    $postageWithExtraCover = $this->_client->calculateDomesticParcelPostage($config);
                                } else {
                                    $postageWithExtraCover = $this->_client->calculateInternationalParcelPostage($config);
                                }
                                unset($config['suboption_code']);
                            } catch (Exception $e) {
                                continue;
                            }
                            if ($serviceOptionName == 'Signature on Delivery') {
                                $serviceOptionName = $serviceOptionName . ' + Extra Cover';
                            } else {
                                $serviceOptionName = 'Extra Cover';
                            }
                            if ($serviceOptionCode == ServiceOption::AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY) {
                                $serviceOptionCode = 'FULL_PACKAGE';
                            } else {
                                $serviceOptionCode = 'EXTRA_COVER';
                            }
                            $servicePrice = $postageWithExtraCover['postage_result']['total_cost'];

                            $_finalCode = $serviceCode . '_' . $serviceOptionCode;
                            $_finalName = $serviceName . ' (' . $serviceOptionName . ')';
                            if ($this->_isAvailableShippingMethod($_finalName, $destCountry)) {
                                $method = $this->createMethod($_finalCode, $_finalName, $servicePrice);
                                $this->_result->append($method);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Determines whether a shipping method should be added to the result.
     *
     * @param string $name Name of the shipping method
     * @param string $destCountry Country code
     * @return bool
     */
    protected function _isAvailableShippingMethod($name, $destCountry)
    {
        return $this->_isOptionVisibilityRequired($name, $destCountry) &&
            !$this->_isOptionVisibilityNever($name, $destCountry);
    }

    /**
     * Checks whether a shipping method option has the visibility "never"
     *
     * @param string $name Name of the shipping method
     * @param string $destCountry Country code
     * @return bool
     */
    protected function _isOptionVisibilityNever($name, $destCountry)
    {
        $suboptions = $this->_getOptionVisibilities($destCountry, Fontis_Australia_Model_Shipping_Carrier_Australiapost_Source_Visibility::NEVER);
        foreach ($suboptions as $suboption) {
            if (stripos($name, $suboption) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks whether a shipping method has the visibility "required"
     *
     * @param string $name Name of the shipping method
     * @param string $destCountry Country code
     * @return bool
     */
    protected function _isOptionVisibilityRequired($name, $destCountry)
    {
        $suboptions = $this->_getOptionVisibilities($destCountry, Fontis_Australia_Model_Shipping_Carrier_Australiapost_Source_Visibility::REQUIRED);
        foreach ($suboptions as $suboption) {
            if (stripos($name, $suboption) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns an array of shipping method options, e.g. "signature on
     * delivery", that have a certain visibility, e.g. "never"
     *
     * @param string $destCountry Destination country code
     * @param int $visibility Shipping method option visibility
     * @return array
     */
    protected function _getOptionVisibilities($destCountry, $visibility)
    {
        /** @var Fontis_Australia_Helper_Australiapost $helper */
        $helper = Mage::helper('australia/australiapost');
        $suboptions = array();
        if ($helper->getPickUp() == $visibility && $destCountry != Fontis_Australia_Helper_Data::AUSTRALIA_COUNTRY_CODE) {
            $suboptions[] = 'pick up';
        }
        if ($helper->getExtraCover() == $visibility) {
            $suboptions[] = 'extra cover';
        }
        if ($helper->getSignatureOnDelivery() == $visibility && $destCountry == Fontis_Australia_Helper_Data::AUSTRALIA_COUNTRY_CODE) {
            $suboptions[] = 'signature on delivery';
        }
        return $suboptions;
    }

    /**
     * Simplifies creating a new shipping method.
     *
     * @param string $code
     * @param string $title
     * @param string $price
     * @return Mage_Shipping_Model_Rate_Result_Method
     */
    protected function createMethod($code, $title, $price)
    {
        /** @var Mage_Shipping_Model_Rate_Result_Method $method */
        $method = Mage::getModel('shipping/rate_result_method');

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod($code);
        $method->setMethodTitle($title);

        $method->setPrice($this->getFinalPriceWithHandlingFee($price));

        return $method;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return explode(',', $this->getConfigData('allowed_methods'));
    }

    /**
     * Returns an associative array of shipping method codes.
     *
     * @param string $type
     * @param string $code
     * @return array|bool
     */
    public function getCode($type, $code='')
    {
        /** @var Fontis_Australia_Helper_Data $helper */
        $helper = Mage::helper('australia');
        $codes = array(
            'services' => array(
                'AUS_LETTER_EXPRESS_SMALL'          => $helper->__('Express Post Small Envelope'),
                'AUS_LETTER_REGULAR_LARGE'          => $helper->__('Large Letter'),
                'AUS_PARCEL_COURIER'                => $helper->__('Courier Post'),
                'AUS_PARCEL_COURIER_SATCHEL_MEDIUM' => $helper->__('Courier Post Assessed Medium Satchel'),
                'AUS_PARCEL_EXPRESS'                => $helper->__('Express Post'),
                'AUS_PARCEL_REGULAR'                => $helper->__('Parcel Post'),

                'INT_PARCEL_COR_OWN_PACKAGING'      => $helper->__('International Courier'),
                'INT_PARCEL_EXP_OWN_PACKAGING'      => $helper->__('International Express'),
                'INT_PARCEL_STD_OWN_PACKAGING'      => $helper->__('International Standard'),
                'INT_PARCEL_AIR_OWN_PACKAGING'      => $helper->__('International Economy Air'),
                'INT_PARCEL_SEA_OWN_PACKAGING'      => $helper->__('International Economy Sea'),
            ),
            'extra_cover' => array(
                'AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY'       => $helper->__('Signature on Delivery'),
                'AUS_SERVICE_OPTION_COURIER_EXTRA_COVER_SERVICE' => $helper->__('Standard cover')
            )
        );

        if (!isset($codes[$type])) {
            return false;
        } elseif (''===$code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }
}
