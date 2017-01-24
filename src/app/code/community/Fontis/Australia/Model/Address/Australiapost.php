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
 * @author     Thai Phan
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

use Auspost\DeliveryChoice\DeliveryChoiceClient;
use Guzzle\Http\Client as HttpClient;
use Guzzle\Http\Exception\BadResponseException;

/**
 * Australia Post Delivery Choices address validation backend
 */
class Fontis_Australia_Model_Address_Australiapost implements Fontis_Australia_Model_Address_Interface
{
    const HTTP_REQUEST_TIMEOUT = 5;

    /**
     * @var DeliveryChoiceClient
     */
    protected $client;

    /**
     * @var array
     */
    protected $addressFieldMap = array(
        'street' => 'AddressLine',
        'suburb' => 'SuburbOrPlaceOrLocality',
        'postcode' => 'PostCode',
        'state' => 'StateOrTerritory',
        'country' => array('Country' => 'CountryCode'),
    );

    public function __construct()
    {
        $this->setClient(Mage::helper("australia/address_deliveryChoices")->getApiClient());
    }

    /**
     * Set the Australia Post Delivery Choices client
     *
     * @param DeliveryChoiceClient $client
     */
    public function setClient(DeliveryChoiceClient $client)
    {
        $client->getConfig()->setPath(HttpClient::CURL_OPTIONS . "/" . CURLOPT_TIMEOUT, self::HTTP_REQUEST_TIMEOUT);
        $this->client = $client;
    }

    /**
     * Converts the customer provided address data into an Australia
     * Post-supported format.
     *
     * @param array $street Address lines
     * @param string $state Address state
     * @param string $suburb Address city / suburb
     * @param string $postcode Address postcode
     * @param string $country Address country
     *
     * @return array
     */
    public function validateAddress(array $street, $state, $suburb, $postcode, $country)
    {
        $address = array(
            'address_line_1' => strtoupper($street[0]),
            'state' => strtoupper($state),
            'suburb' => strtoupper($suburb),
            'postcode' => strtoupper($postcode),
            'country' => strtoupper($country),
        );

        if (count($street) > 1) {
            $address['address_line_2'] = strtoupper($street[1]);
        }

        /** @var $helper Fontis_Australia_Helper_Data */
        $helper = Mage::helper('australia');
        $result = array();
        try {
            $result = $this->client->validateAddress($address);

            $result = $result['ValidateAustralianAddressResponse'];
            $result['success'] = true;

            $result['original'] = $this->_getAddressString(
                trim($address['address_line_1'] . (isset($address['address_line_2']) ? ' ' . $address['address_line_2'] : '')),
                $address['suburb'],
                $address['state'],
                $address['postcode']
            );

            // If the address isn't valid
            if (!$result['ValidAustralianAddress']) {
                return $result;
            }
            // Unset the delivery point identifier as it isn't relevant in this context.
            unset($result['Address']['DeliveryPointIdentifier']);

            $resultAddress = $result['Address'];
            $result['suggestion'] = $this->_getAddressString(
                $resultAddress['AddressLine'],
                $resultAddress['SuburbOrPlaceOrLocality'],
                $resultAddress['StateOrTerritory'],
                $resultAddress['PostCode']
            );

            /** @var $regionModel Mage_Directory_Model_Region */
            $regionModel = Mage::getModel('directory/region')->loadByCode($resultAddress['StateOrTerritory'], $resultAddress['Country']['CountryCode']);
            $regionId = $regionModel->getId();

            $result['suggestedAddress'] = array(
                'street1' => $resultAddress['AddressLine'],
                'city' => $resultAddress['SuburbOrPlaceOrLocality'],
                'regionId' => $regionId,
                'postcode' => $resultAddress['PostCode'],
            );

            // If we have a valid address append it to the response so the JavaScript can act accordingly.
            $result['validAddress'] = $this->_checkAddressValidity($result['original'], $result['suggestion']);
        } catch (BadResponseException $e) {
            $helper->logMessage("An error occurred while contacting the AusPost Delivery Choices API:\n" . $e->getMessage(), Zend_Log::ERR);
            $result['success'] = false;
        } catch (Exception $e) {
            Mage::logException($e);
            $result['success'] = false;
        }

        return $result;
    }

    /**
     * @param string $original
     * @param string $suggestion
     * @return bool
     */
    protected function _checkAddressValidity($original, $suggestion)
    {
        // TODO: Also check the street type abbreviations (st = street)
        return $original == $suggestion;
    }

    /**
     * @param string $street
     * @param string $city
     * @param string $state
     * @param string $postcode
     * @return string
     */
    protected function _getAddressString($street, $city, $state, $postcode)
    {
        if (is_array($street)) {
            $street = implode(' ', $street);
        }

        return $street . ', ' . $city . ', ' . $state . ' ' . $postcode;
    }
}
