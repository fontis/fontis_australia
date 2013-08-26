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
 * @author     Peter Spiller
 * @copyright  Copyright (c) 2008 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$input = "";
do {
    $input .= fgets(STDIN);
} while (!feof(STDIN));

$config = json_decode($input);

errlog("Child got: ".$input);

// Start-up Magento stack
require_once $config->magento_path . '/app/Mage.php';
// Copied from the original - apparently it fixes problems on some flavours of hosting...
// TODO: Explain why/what?
$_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $config->magento_path . "/app/code/community/Fontis/Australia/Model/Child.php";

$store_id = $config->store_id;
Mage::app($store_id);


$products = array();
foreach($config->entity_ids as $entity_id) {
    errlog("Processing $entity_id");
    // Load product, tax helper and generate final price information
    $product = Mage::getModel('catalog/product')->load($entity_id);

    //Collect basic attributes
    $product_data = $product->getData();

    // Add generated attributes
    $tax = Mage::helper('tax');
    $final_price = $tax->getPrice($product, $product->getFinalPrice(), true);
    $product_data['final_price'] = $final_price;
    $product_data['manufacturer_name'] = $product->getResource()->getAttribute('manufacturer')->getFrontend()->getValue($product);
    $product_data['link'] = str_replace("Child.php/", "", $product->getProductUrl());
    $product_data['sku'] = $product->getSku();
    $product_data['image_url'] = (string)Mage::helper('catalog/image')->init($product, 'image');
    $product_data['instock'] = $product->isSaleable() ? "Y" : "N"; // myshopping
    $product_data['availability'] = $product->isSaleable() ? "yes" : "no"; // shopbot
    $product_data['product_id'] = $product->getId();
    $product_data['product_num'] = $product->getEntityId();
    $product_data['brand'] = $product_data['manufacturer_name'] == "No" ? "Generic" : $product_data['manufacturer_name']; // myshopping
    $product_data['currency'] = Mage::getStoreConfig('fontis_feeds/'. $config->config_path .'/currency');

    if (Mage::getStoreConfig('fontis_feeds/'. $config->config_path .'/manufacturer')) {
        if ($product_data['manufacturer_name'] != "No") {
            $product_data['manufacturer'] = $product_data['manufacturer_name']; // getprice
        }
    } 

    if ($config->generate_categories) {
        $category_found = false;
        foreach($product->getCategoryCollection() as $c) {
            $children = $c->getData('children_count');
            if ($children <= 0) {
                $product_data['category'] = utf8_encode($c->getName());

                $loaded_categories = Mage::getModel('catalog/category')
                        ->getCollection()
                        ->addIdFilter(array($c->getId()))
                        ->addAttributeToSelect(array('name'), 'inner')->load();

                foreach($loaded_categories as $loaded_category) {
                    $product_data['category'] = utf8_encode($loaded_category->getName());
                }
                $category_found = true;
            }
        }
        if (!$category_found) {
            $product_data['category'] = utf8_encode(Mage::getStoreConfig('fontis_feeds/'. $config->config_path .'/defaultcategory'));
        }
    }

    $products[] = $product_data;
}
fwrite (STDOUT, json_encode($products));

function errlog($mesg) {
    fwrite (STDERR, "Child: ".$mesg."\n");
} 
