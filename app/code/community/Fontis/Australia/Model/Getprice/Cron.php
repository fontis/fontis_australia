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

// Callback used by the Magento resource iterator walk() method. Adds a
// product ID to a static array and calls addProductXmlgetPrice() to
// generate XML when the array reaches the batch size.
function addProductXmlCallbackGetPrice($args) {
    Fontis_Australia_Model_GetPrice_Cron::$accumulator[] = $args['row']['entity_id'];

    $length = count(Fontis_Australia_Model_GetPrice_Cron::$accumulator);
    if($length >= Fontis_Australia_Model_GetPrice_Cron::BATCH_SIZE) {
        addProductXmlGetPrice();
    }
}

// Runs a subprocesss to create feed XML for the product IDs in the static
// array, then empties the array.
function addProductXmlGetPrice() {
    $length = count(Fontis_Australia_Model_GetPrice_Cron::$accumulator);
    if($length > 0) {
        Mage::log("Fontis/Australia_Model_Getprice_Cron: Processing product IDs " . Fontis_Australia_Model_GetPrice_Cron::$accumulator[0] .
                " to " . Fontis_Australia_Model_GetPrice_Cron::$accumulator[$length - 1]);
        $store_id = Fontis_Australia_Model_GetPrice_Cron::$store->getId();

        $data = shell_exec("php " . Mage::getBaseDir() . "/app/code/community/Fontis/Australia/Model/Getprice/Child.php " .
                Mage::getBaseDir() . " '" . serialize(Fontis_Australia_Model_GetPrice_Cron::$accumulator) .
                "' " . $store_id);
        Fontis_Australia_Model_GetPrice_Cron::$accumulator = array();

        $array = json_decode($data, true);

        if(is_array($array)) {
            $codes = array();
            foreach($array as $prod) {
                Fontis_Australia_Model_GetPrice_Cron::$debugCount += 1;
                $product_node = Fontis_Australia_Model_GetPrice_Cron::$root_node->addChild('product');
                foreach($prod as $key => $val) {
                    if($key == 'Code') {
                        $codes[] = $val;
                    }
                    $product_node->addChild($key, htmlspecialchars($val));
                }
            }
            if(!empty($codes)) {
                Mage::log("Fontis/Australia_Model_Getprice_Cron: Codes: ".implode(",", $codes));
            }
        } else {
            Mage::log("Fontis/Australia_Model_Getprice_Cron: Could not unserialize to array:");
            Mage::log($data);
            Mage::log($array);
        }

        Mage::log('Fontis/Australia_Model_Getprice_Cron: ' . strlen($data) . ' characters returned');
    }
}

class Fontis_Australia_Model_GetPrice_Cron {
    const BATCH_SIZE = 100;

    public static $doc;
    public static $root_node;
    public static $store;
    public static $accumulator;
    public static $debugCount = 0;

    protected function _construct() {
        self::$accumulator = array();
    }

    protected function getPath() {
        $path = "";
        $config_path = Mage::getStoreConfig('fontis_feeds/getpricefeed/output');

        if (substr($config_path, 0, 1) == "/") {
            $path = $config_path . '/';
        } else {
            $path = Mage::getBaseDir() . '/' . $config_path . '/';
        }

        return str_replace('//', '/', $path);
    }

    public function nonstatic() {
        self::update();
    }

    public static function update() {
        Mage::log('Fontis/Australia_Model_Getprice_Cron: Entered update function');

        if (Mage::getStoreConfig('fontis_feeds/getpricefeed/active')) {
            $io = new Varien_Io_File();
            $io->setAllowCreateFolders(true);

            $io->open(array('path' => self::getPath()));

            // Loop through all stores:
            foreach(Mage::app()->getStores() as $store) {
                Mage::log('Fontis/Australia_Model_Getprice_Cron: Processing store: ' . $store->getName());
                $clean_store_name = str_replace('+', '-', strtolower(urlencode($store->getName())));

                // Write the entire products xml file:
                Mage::log('Fontis/Australia_Model_Getprice_Cron: Generating All Products XML File');
                $products_result = self::getProductsXml($store);
                $io->write($clean_store_name . '-products.xml', $products_result['xml']);
                Mage::log('Fontis/Australia_Model_Getprice_Cron: Wrote to file: ' . $clean_store_name . '-products.xml', $products_result['xml']);

                // Write the leaf categories xml file:
                Mage::log('Fontis/Australia_Model_Getprice_Cron: Generating Categories XML File');
                $categories_result = self::getCategoriesXml($store);
                $io->write($clean_store_name . '-categories.xml', $categories_result['xml']);
                Mage::log('Fontis/Australia_Model_Getprice_Cron: Wrote to file: ' . $clean_store_name . '-categories.xml', $categories_result['xml']);

                // Write for each leaf category, their products xml file:
                foreach($categories_result['link_ids'] as $link_id) {
                    Mage::log('Fontis/Australia_Model_Getprice_Cron: Generating Product Category XML File: ' . $link_id);
                    $subcategory_products_result = self::getProductsXml($store, $link_id);
                    $io->write($clean_store_name . '-products-'.$link_id.'.xml', $subcategory_products_result['xml']);
                    Mage::log('Fontis/Australia_Model_Getprice_Cron: Wrote to file: ' . $clean_store_name . '-products-'.$link_id.'.xml', $subcategory_products_result['xml']);
                }
            }

            $io->close();
        } else {
            Mage::log('Fontis/Australia_Model_Getprice_Cron: Disabled');
        }
    }

    public function getCategoriesXml($store) {
        $clean_store_name = str_replace('+', '-', strtolower(urlencode($store->getName())));

        $result = array();
        $categories = Mage::getModel('catalog/category')->getCollection()
                ->setStoreId($store->getId())
                ->addAttributeToFilter('is_active', 1);

        $categories->load()->getItems();

        $full_categories = array();

        foreach($categories as $category) {
            $id = $category->getId();
            $category = Mage::getModel('catalog/category')->load($id);

            $children = $category->getAllChildren(true);
            if (count($children) <= 1) {
                $full_categories[] = $category;
            }
        }

        $storeUrl = $store->getBaseUrl();
        $shopName = $store->getName();
        $date = date("d-m-Y", Mage::getModel('core/date')->timestamp(time()));
        $time = date("h:i:s", Mage::getModel('core/date')->timestamp(time()));

        $doc = new SimpleXMLElement('<store url="' . $storeUrl. '" date="'.$date.'" time="'.$time.'" name="' . $shopName . '"></store>');

        foreach($full_categories as $category) {
            $category_node = $doc->addChild('cat');

            $title_node = $category_node->addChild('name');
            $title_node[0] = htmlspecialchars($category->getName());

            $link_node = $category_node->addChild('link');
            $link_node[0] = Mage::getStoreConfig('web/unsecure/base_url') .
                    Mage::getStoreConfig('fontis_feeds/getpricefeed/output') . "/$clean_store_name-products-" . $category->getId() . '.xml';

            $result['link_ids'][] = $category->getId();
        }

        $result['xml'] = self::formatSimpleXML($doc);
        return $result;
    }

    public function getProductsXml($store, $cat_id = -1) {
        Fontis_Australia_Model_GetPrice_Cron::$store = $store;
        $result = array();

        $product = Mage::getModel('catalog/product');
        $products = $product->getCollection();
        $products->setStoreId($store);
        $products->addStoreFilter();
        $products->addAttributeToSelect('*');
        $products->addAttributeToSelect(array('name', 'price', 'image', 'status', 'manufacturer'), 'left');
        $products->addAttributeToFilter('status', 1);
        $products->addAttributeToFilter('visibility', 4);

        if ($cat_id != -1) {
            $products->getSelect()->where("e.entity_id IN (
                SELECT product_id FROM catalog_category_product WHERE category_id = ".$cat_id."
                )");
        }

        $products->getSelect()->order('product_id');

        $storeUrl = $store->getBaseUrl();
        $shopName = $store->getName();
        $date = date("d-m-Y", Mage::getModel('core/date')->timestamp(time()));
        $time = date("h:i:s", Mage::getModel('core/date')->timestamp(time()));

        self::$doc = new SimpleXMLElement('<store url="' . $storeUrl. '" date="'.$date.'" time="'.$time.'" name="' . $shopName . '"></store>');
        self::$root_node = self::$doc->addChild('products');

        Mage::log('Fontis/Australia_Model_Getprice_Cron: Iterating: ' . $products->getSize() . ' products...');
        Mage::getSingleton('core/resource_iterator')->walk($products->getSelect(), array('addProductXmlCallbackGetPrice'), array('product' => $product));
        
        // call XML generation function one last time to process remaining batch
        addProductXmlGetPrice();
        Mage::log('Fontis/Australia_Model_Getprice_Cron: Iteration complete');

        $result['xml'] = self::formatSimpleXML(self::$doc);
        return $result;
    }

    public static function formatSimpleXML($doc) {
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom_sxml = dom_import_simplexml($doc);
        $dom_sxml = $dom->importNode($dom_sxml, true);
        $dom->appendChild($dom_sxml);
        return $dom->saveXML();
    }
}
