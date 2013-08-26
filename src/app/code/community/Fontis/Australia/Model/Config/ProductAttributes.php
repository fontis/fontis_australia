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
 * @author     Tom Greenaway
 * @copyright  Copyright (c) 2010 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * List all available product attributes
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Model_Config_ProductAttributes {
    public function toOptionArray() {
        $eav_config = Mage::getModel('eav/config');
        $attributes = $eav_config->getEntityAttributeCodes('catalog_product');

        $options = array();
        $options[0] = "No Attribute";

        foreach($attributes as $att_code)
        {
            $attribute = $eav_config->getAttribute('catalog_product', $att_code);
            Mage::log($attribute);

            if ($att_code != '')
            {
                $options[$att_code] = $att_code;
            }
        }

        return $options;
    }
}

