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
 * to license@magentocommerce.com and you will be sent a copy immediately.
 *
 * @category   Fontis
 * @package    Fontis_Australia
 * @author     Tom Greenaway
 * @copyright  Copyright (c) 2008 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (!isset($argv[1]) or !isset($argv[2]) or !isset($argv[3])) {
    exit;
}
$mage_path = $argv[1];
$product_ids = $argv[2];
$store_id = $argv[3];

// Start-up Magento stack
require_once $mage_path . '/app/Mage.php';
Mage::app($store_id);

Mage::log('Fontis/Australia_Model_Getprice_Child: Successfully entered child process');

$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $mage_path . "/app/code/community/Fontis/Australia/Model/Getprice/Child.php";

// Check and assign mode
if (Mage::getStoreConfig('fontis_feeds/getpricefeed/include_all_products') == 1) {
    $include_all = true;
} else {
    $include_all = false;
}

// This array is translated into XML when fed back to the cron parent PHP.
$products = array();
foreach(unserialize($product_ids) as $product_id) {

    // Load product, tax helper and generate final price information
    $product = Mage::getModel('catalog/product')->load($product_id);

    // Check for overrride attribute
    // Fetch override attribute
    $override_attribute_code = Mage::getStoreConfig('fontis_feeds/getpricefeed/custom_filter_attribute');

    Mage::log('Fontis/Australia_Model_Getprice_Child: Override attribute: ' . $override_attribute_code);

    // Check product for that attribute
    if ($override_attribute_code != '0') {
        Mage::log('Fontis/Australia_Model_Getprice_Child: Attempting to load attribute value');
        $attribute_value = $product->getResource()->getAttribute($override_attribute_code)->getFrontend()->getValue($product);
        Mage::log('Fontis/Australia_Model_Getprice_Child: Attribute value: ' . $attribute_value);

        if ($attribute_value === '' || $attribute_value === 0 || $attribute_value === '0' || strtolower($attribute_value) == 'no') {
            $override_attribute = false;
        } else {
            $override_attribute = true;
        }
    } else {
        Mage::log('Fontis/Australia_Model_Getprice_Child: No override attribute set');
        $override_attribute = false;
    }

    // If mode is includeall and override is true don't include this product
    if ($include_all && $override_attribute) {
        Mage::log('Fontis/Australia_Model_Getprice_Child: Skipping due to include_all && override_attribute');
        continue;
    }

    // If mode is excludeall and override is false don't include this product
    if (!$include_all && !$override_attribute) {
        Mage::log('Fontis/Australia_Model_Getprice_Child: Skipping due to !include_all && !override_attribute');
        continue;
    }
    
    $tax = Mage::helper('tax');
    $final_price = $tax->getPrice($product, $product->getFinalPrice(), true);

    // Array containing product data.
    $array = array();

    $array['num'] = $product->getEntityId();
    $array['attribute1'] = $product->getName();
    $array['upc'] = $product->getSku();
    $array['product_url'] = $product->getProductUrl();

    if (Mage::getStoreConfig('fontis_feeds/getpricefeed/manufacturer')) {
        $manufacturer_name = $product->getResource()->
            getAttribute('manufacturer')->getFrontend()->getValue($product);

        if ($manufacturer_name != 'No') {
            $array['manufacturer'] = $manufacturer_name;
        }
    }

    $category_found = false;
    $array['category_name'] = '';
    foreach($product->getCategoryCollection() as $c) {
        $children = $c->getData('children_count');
        if ($children <= 0) {
            $category_node[0] = $c->getName();

            $loaded_categories = Mage::getModel('catalog/category')
                ->getCollection()
                ->addIdFilter(array($c->getId()))
                ->addAttributeToSelect(array('name'), 'inner')->load();

            foreach($loaded_categories as $loaded_category) {
                $array['category_name'] = $loaded_category->getName();
            }
            $category_found = true;
        }
    }
    if (!$category_found) {
        $array['category_name'] = Mage::getStoreConfig('fontis_feeds/getpricefeed/defaultcategory');
    }

    $array['image'] = (string)Mage::helper('catalog/image')->init($product, 'image');
    $array['price'] = $final_price;
    $array['currency'] = Mage::getStoreConfig('fontis_feeds/getpricefeed/currency');

    $products[] = $array;
}

Mage::log('Fontis/Australia_Model_Getprice_Child: Finishing child processing. Returning encoded JSON data.');

// Serialize and print as a string for the cron parent PHP code to grab.
echo json_encode($products);
