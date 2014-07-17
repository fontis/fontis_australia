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

/**
 * Controller for address validation requests
 */
class Fontis_Australia_AddressController extends Mage_Core_Controller_Front_Action
{
    protected $client;

    protected function _construct()
    {
        $model = Mage::getStoreConfig('fontis_australia/address_validation/backend');
        $this->setClient($model);
    }

    /**
     * Set the address validation backend client.
     *
     * @param string $client The model alias of the backend client
     */
    public function setClient($client)
    {
        $this->client = Mage::getModel($client);
    }

    /**
     * Sends a request to the address validation backend to validate the address
     * the customer provided on the checkout page.
     */
    public function validateAction()
    {
        if ($this->getRequest()->isPost()) {
            $request = $this->getRequest();
            if ($request->getPost('billing')) {
                $data = $request->getPost('billing');
            } else {
                $data = $request->getPost('shipping', array());
            }
            if (
                !empty($data) &&
                isset($data['country_id']) &&
                isset($data['region_id']) &&
                isset($data['street']) &&
                isset($data['city']) &&
                isset($data['postcode'])
            ) {
                $country = Mage::getModel('directory/country')->load($data['country_id'])->getName();
                $region = Mage::getModel('directory/region')->load($data['region_id'])->getCode();

                $result = $this->client->validateAddress(
                    $data['street'],
                    $region,
                    $data['city'],
                    $data['postcode'],
                    $country
                );

                $this->getResponse()->setHeader('Content-type', 'application/json');
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
            } else {
                Mage::log('Address validation failed. Please fill in all required fields.');
                $this->_redirect('noRoute');
            }
        } else {
            Mage::log('Address validation requests must use the POST method.');
            $this->_redirect('noRoute');
        }
    }
}
