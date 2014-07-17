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
 * Autocomplete queries list
 */
class Fontis_Australia_Block_Autocomplete extends Mage_Core_Block_Abstract
{
    /**
     * @return string
     */
    protected function _toHtml()
    {
        $html = '';

        if (!$this->_beforeToHtml()) {
            return $html;
        }

        // Get the text that the customer has entered as a query.
        $results = $this->helper('australia')->getPostcodeAutocompleteResults();
        if (empty($results)) {
            return $html;
        }

        $html = '<ul>';
        $counter = 0;
        foreach ($results as $item) {
            $html .= '<li class="' . ((++$counter) % 2 ? 'odd' : 'even') . '" id="region-' . $item['region_id'] . '-postcode-' . $item['postcode'] . '">';
            $html .= $item['city'] . '<span class="informal"> ' . $item['region_code'] . ', ' . $item['postcode'] . '</span>';
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }
}
