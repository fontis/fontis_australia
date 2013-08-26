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
 * Data helper
 */
class Fontis_Australia_Helper_Data extends Mage_Core_Helper_Abstract
{
    const MAX_QUERY_LEN = 100;

    protected $_queryText;

	/**
	 * Gets the query text for city lookups in the postcode database.
	 */
    public function getQueryText()
    {
        if (is_null($this->_queryText)) {
            if($this->_getRequest()->getParam('billing')) {
            	$tmp = $this->_getRequest()->getParam('billing');
            	$this->_queryText = $tmp['city'];
            } else if($this->_getRequest()->getParam('shipping')) {
            	$tmp = $this->_getRequest()->getParam('shipping');
            	$this->_queryText = $tmp['city'];
            } else {
            	$this->_queryText = $this->_getRequest()->getParam('city');
            }
            $this->_queryText = trim($this->_queryText);
            if (Mage::helper('core/string')->strlen($this->_queryText) > self::MAX_QUERY_LEN) {
                $this->_queryText = Mage::helper('core/string')->substr($this->_queryText, 0, self::MAX_QUERY_LEN);
            }
        }
        return $this->_queryText;
    }
    
    public function getQueryCountry()
    {
    	return $this->_queryText = $this->_getRequest()->getParam('country');
    }

	public function getCitySuggestUrl()
	{
		return $this->_getUrl('australia/ajax/suggest');
	}

} 
