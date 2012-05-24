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
 * Autocomplete queries list
 */
class Fontis_Australia_Block_Autocomplete extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $html = '';

		if (!$this->_beforeToHtml()) {
			return $html;
		}

		// Get the text that the customer has entered as a query.
		$query = $this->helper('australia')->getQueryText();
		$country = $this->helper('australia')->getQueryCountry();
		if($country != "AU") return $html;
		
		$conn = Mage::getModel('Core/Mysql4_Config')->getReadConnection();
		$resultArray = $conn->fetchAll('SELECT au.*, dcr.region_id FROM au_postcode AS au 
										INNER JOIN directory_country_region AS dcr ON au.region_code = dcr.code
										WHERE city LIKE \'%' . $query . '%\' ORDER BY city, region_code');

		$html = '<ul>';
		$counter = 0;
		foreach ($resultArray as $item) {
            $html .= '<li class="'.((++$counter)%2?'odd':'even').'" id="region-' . $item['region_id'] . '-postcode-' . $item['postcode'] . '">';
            $html .= $item['city'] . '<span class="informal"> ' . $item['region_code'] . ', ' . $item['postcode'] . '</span>';
            $html .= '</li>';
		}

		$html.= '</ul>';

        return $html;
    }
}
