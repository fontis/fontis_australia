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
 * Address validation backend interface
 */
interface Fontis_Australia_Model_Address_Interface
{
    /**
     * Sends the customer's address off to some backend for validation and then
     * maps the response to the following array structure:
     *
     * array(
     *     'ValidAustralianAddress' => true,
     *     'Address' => array(
     *         'AddressLine' => '42 Wallaby Way',
     *         'Country' => array(
     *             'CountryCode' => 'AU',
     *             'CountryName' => 'Australia'
     *         ),
     *         'PostCode' => '2000',
     *         'StateOrTerritory' => 'NSW',
     *         'SuburbOrPlaceOrLocality' => 'Sydney'
     *     )
     * );
     *
     * Tip: If the value for the 'ValidAustraliaAddress' key is false then
     * the 'Address' key isn't needed.
     *
     * Note: Country and SuburbOrPlaceOrLocality are used by Magento.
     *
     * @param array $street Address lines
     * @param string $state Address state
     * @param string $suburb Address city / suburb
     * @param string $postcode Address postcode
     * @param string $country Address country
     *
     * @return array
     */
    public function validateAddress(array $street, $state, $suburb, $postcode, $country);
}
