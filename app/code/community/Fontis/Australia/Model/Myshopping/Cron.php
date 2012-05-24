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
 * @copyright  Copyright (c) 2009 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

// Callback used by the Magento resource iterator walk() method. Adds a
// product ID to a static array and calls addProductXmlMyShopping() to
// generate XML when the array reaches the batch size.
function addProductXmlCallbackMyShopping($args) {
    Fontis_Australia_Model_MyShopping_Cron::$accumulator[] = $args['row']['entity_id'];
    
    $length = count(Fontis_Australia_Model_MyShopping_Cron::$accumulator);
    if($length >= Fontis_Australia_Model_MyShopping_Cron::BATCH_SIZE) {
        addProductXmlMyShopping();
    }
}

// Runs a subprocesss to create feed XML for the product IDs in the static
// array, then empties the array.
function addProductXmlMyShopping() {
    $length = count(Fontis_Australia_Model_MyShopping_Cron::$accumulator);
    if($length > 0) {
        Mage::log("Fontis/Australia_Model_Myshopping_Cron: Processing product IDs " . Fontis_Australia_Model_MyShopping_Cron::$accumulator[0] .
                " to " . Fontis_Australia_Model_MyShopping_Cron::$accumulator[$length - 1]);
        $store_id = Fontis_Australia_Model_MyShopping_Cron::$store->getId();

        $data = shell_exec("php " . Mage::getBaseDir() . "/app/code/community/Fontis/Australia/Model/Myshopping/Child.php " .
                Mage::getBaseDir() . " '" . serialize(Fontis_Australia_Model_MyShopping_Cron::$accumulator) .
                "' " . $store_id);
        Fontis_Australia_Model_MyShopping_Cron::$accumulator = array();

        $array = json_decode($data, true);

        if(is_array($array)) {
            $codes = array();
            foreach($array as $prod) {
                Fontis_Australia_Model_MyShopping_Cron::$debugCount += 1;
                $product_node = Fontis_Australia_Model_Myshopping_Cron::$root_node->addChild('product');
                foreach($prod as $key => $val) {
                    if($key == 'Code') {
                        $codes[] = $val;
                    }
                    $product_node->addChild($key, htmlspecialchars($val));
                }
            }
            if(!empty($codes)) {
                Mage::log("Fontis/Australia_Model_Myshopping_Cron: Codes: ".implode(",", $codes));
            }
        } else {
            Mage::log("Fontis/Australia_Model_Myshopping_Cron: Could not unserialize to array:");
            Mage::log($data);
            Mage::log($array);
        }

        Mage::log("Fontis/Australia_Model_Myshopping_Cron: " . strlen($data) . " characters returned");
    }
}

class Fontis_Australia_Model_MyShopping_Cron {
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
        $config_path = Mage::getStoreConfig('fontis_feeds/myshoppingfeed/output');

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
        Mage::log('Fontis/Australia_Model_MyShopping_Cron: Entered update function');

        if (Mage::getStoreConfig('fontis_feeds/myshoppingfeed/active')) {
            $io = new Varien_Io_File();
            $io->setAllowCreateFolders(true);

            $io->open(array('path' => self::getPath()));

            // Loop through all stores:
            foreach(Mage::app()->getStores() as $store) {
                Mage::log('Fontis/Australia_Model_MyShopping_Cron: Processing store: ' . $store->getName());
                $clean_store_name = str_replace('+', '-', strtolower(urlencode($store->getName())));

                Fontis_Australia_Model_MyShopping_Cron::$debugCount = 0;

                // Write the entire products xml file:
                Mage::log('Fontis/Australia_Model_MyShopping_Cron: Generating All Products XML File');
                $products_result = self::getProductsXml($store);
                
                $filename = $clean_store_name . '-products.xml';
                $io->write($filename, $products_result['xml']);
                Mage::log('Fontis/Australia_Model_MyShopping_Cron: Wrote ' . Fontis_Australia_Model_MyShopping_Cron::$debugCount
                        . " records to " . self::getPath() . $filename);
            }

            $io->close();
        } else {
            Mage::log('Fontis/Australia_Model_MyShopping_Cron: Disabled');
        }
    }

    public function getProductsXml($store) {
        Mage::log('new getproductsxml');
        Fontis_Australia_Model_MyShopping_Cron::$store = $store;

        $result = array();

        $product = Mage::getModel('catalog/product');
        $products = $product->getCollection();
        $products->setStoreId($store);
        $products->addStoreFilter();
        $products->addAttributeToSelect('*');
        $products->addAttributeToFilter('status', 1);
        $products->addAttributeToFilter('visibility', 4);

        $storeUrl = $store->getBaseUrl();
        $shopName = $store->getName();
        $date = date("d-m-Y", Mage::getModel('core/date')->timestamp(time()));
        $time = date("h:i:s", Mage::getModel('core/date')->timestamp(time()));

        self::$doc = new SimpleXMLElement('<productset></productset>');
        self::$root_node = self::$doc;

        Mage::log('Fontis/Australia_Model_MyShopping_Cron: Iterating: ' . $products->getSize() . ' products...');
        Mage::getSingleton('core/resource_iterator')->walk($products->getSelect(), array('addProductXmlCallbackMyShopping'));

        // call XML generation function one last time to process remaining batch
        addProductXmlMyShopping();
        Mage::log('Fontis/Australia_Model_MyShopping_Cron: Iteration complete');

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom_sxml = dom_import_simplexml(self::$doc);
        $dom_sxml = $dom->importNode($dom_sxml, true);
        $dom->appendChild($dom_sxml);
        $result['xml'] = $dom->saveXML();
        return $result;
    }
}
