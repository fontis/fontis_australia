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
use Auspost\Common\Auspost;
use Auspost\DeliveryChoice\DeliveryChoiceClient;

/**
 * Australia Post Delivery Choices address validation backend
 */
class Fontis_Australia_Model_Address_Australiapost implements Fontis_Australia_Model_Address_Interface
{
    protected $client;

    public function __construct()
    {
        /** @var Fontis_Australia_Helper_Address $helper */
        $helper = Mage::helper('australia/address');
        $options = array();
        if ($helper->isDeliveryChoicesDeveloperMode()) {
            $options['developer_mode'] = true;
        } else {
            $options['email_address'] = Mage::getStoreConfig('fontis_australia/address_validation/delivery_choices_account_email');
            $options['password'] = Mage::getStoreConfig('fontis_australia/address_validation/delivery_choices_account_password');
        }

        $this->setClient(Auspost::factory($options)->get('deliverychoice'));
    }

    /**
     * Set the Australia Post Delivery Choices client
     *
     * @param DeliveryChoiceClient $client
     */
    public function setClient(DeliveryChoiceClient $client)
    {
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
            'address_line_1' => $street[0],
            'state' => $state,
            'suburb' => $suburb,
            'postcode' => $postcode,
            'country' => $country
        );
        if (count($street) > 1) {
            $address['address_line_2'] = $street[1];
        }

        $result = array();
        try {
            $result = $this->client->validateAddress($address);

            $result = $result['ValidateAustralianAddressResponse'];
            if (is_array($result['Address']['AddressLine'])) {
                $result['Address']['AddressLine'] = $result['Address']['AddressLine'][0];
            }
            unset($result['Address']['DeliveryPointIdentifier']);
        } catch (Exception $e) {
            $result['ValidAustralianAddress'] = false;
            Mage::logException($e);
        }
        return $result;
    }
}
