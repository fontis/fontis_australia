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
// product ID to a static array and calls addProductXmlShopBot() to
// generate XML when the array reaches the batch size.
function addProductXmlCallbackShopBot($args) {
    Fontis_Australia_Model_ShopBot_Cron::$accumulator[] = $args['row']['entity_id'];

    $length = count(Fontis_Australia_Model_ShopBot_Cron::$accumulator);
    if($length >= Fontis_Australia_Model_ShopBot_Cron::BATCH_SIZE) {
        addProductXmlShopBot();
    }
}

// Runs a subprocesss to create feed XML for the product IDs in the static
// array, then empties the array.
function addProductXmlShopBot() {
    $length = count(Fontis_Australia_Model_ShopBot_Cron::$accumulator);
    if($length > 0) {
        Mage::log("Fontis/Australia_Model_Shopbot_Cron: Processing product IDs " . Fontis_Australia_Model_ShopBot_Cron::$accumulator[0] .
                " to " . Fontis_Australia_Model_ShopBot_Cron::$accumulator[$length - 1]);
        $store_id = Fontis_Australia_Model_ShopBot_Cron::$store->getId();

        $data = shell_exec("php " . Mage::getBaseDir() . "/app/code/community/Fontis/Australia/Model/Shopbot/Child.php " .
                Mage::getBaseDir() . " '" . serialize(Fontis_Australia_Model_ShopBot_Cron::$accumulator) .
                "' " . $store_id);
        Fontis_Australia_Model_ShopBot_Cron::$accumulator = array();

        $array = json_decode($data, true);

        if(is_array($array)) {
            $codes = array();
            foreach($array as $prod) {
                Fontis_Australia_Model_ShopBot_Cron::$debugCount += 1;
                $product_node = Fontis_Australia_Model_ShopBot_Cron::$root_node->addChild('product');
                foreach($prod as $key => $val) {
                    if($key == 'Code') {
                        $codes[] = $val;
                    }
                    $product_node->addChild($key, htmlspecialchars($val));
                }
            }
            if(!empty($codes)) {
                Mage::log("Fontis/Australia_Model_Shopbot_Cron: Codes: ".implode(",", $codes));
            }
        } else {
            Mage::log("Fontis/Australia_Model_Shopbot_Cron: Could not unserialize to array:");
            Mage::log($data);
            Mage::log($array);
        }

        Mage::log('Fontis/Australia_Model_Shopbot_Cron: ' . strlen($data) . " characters returned");
    }
}


class Fontis_Australia_Model_ShopBot_Cron {
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
        $config_path = Mage::getStoreConfig('fontis_feeds/shopbotfeed/output');

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
        Mage::log('Fontis/Australia_Model_Shopbot_Cron: Entered update function');
        if (Mage::getStoreConfig('fontis_feeds/shopbotfeed/active')) {
            $io = new Varien_Io_File();
            $io->setAllowCreateFolders(true);

            $io->open(array('path' => self::getPath()));

            foreach(Mage::app()->getStores() as $store) {
                Mage::log('Fontis/Australia_Model_Shopbot_Cron: Processing store: ' . $store->getName());
                $clean_store_name = str_replace('+', '-', strtolower(urlencode($store->getName())));

                Fontis_Australia_Model_ShopBot_Cron::$debugCount = 0;
                
                // Write the entire products xml file:
                Mage::log('Fontis/Australia_Model_Shopbot_Cron: Generating All Products XML File');
                $products_result = self::getProductsXml($store);

                $filename = $clean_store_name . '-products.xml';
                $io->write($filename, $products_result['xml']);
                Mage::log("Fontis/Australia_Model_Shopbot_Cron: Wrote " . Fontis_Australia_Model_ShopBot_Cron::$debugCount
                        . " records to " . self::getPath() . $filename);
            }

            $io->close();
        } else {
            Mage::log('Fontis/Australia_Model_Shopbot_Cron: Disabled');
        }
    }

    public function getProductsXml($store) {
        Fontis_Australia_Model_ShopBot_Cron::$store = $store;

        $result = array();

        $product = Mage::getModel('catalog/product');
        $products = $product->getCollection();
        $products->setStoreId($store);
        $products->addStoreFilter();
        $products->addAttributeToSelect('*');
        $products->addAttributeToFilter('status', 1);
        $products->addAttributeToFilter('visibility', 4);

        $attributes_select_array = array('name', 'price', 'image', 'status');
        $linkedAttributes = @unserialize(Mage::getStoreConfig('fontis_feeds/shopbotfeed/m_to_xml_attributes', $store->getId()));
        if(!empty($linkedAttributes)) {
            foreach($linkedAttributes as $la) {
                if (strpos($la['magento'], 'FONTIS') === false) {
                    $attributes_select_array[] = $la['magento'];
                }
            }
        }
        Mage::log(var_export($attributes_select_array, true));

        $products->addAttributeToSelect($attributes_select_array, 'left');

        $storeUrl = $store->getBaseUrl();
        $shopName = $store->getName();
        $date = date("d-m-Y", Mage::getModel('core/date')->timestamp(time()));
        $time = date("h:i:s", Mage::getModel('core/date')->timestamp(time()));

        self::$doc = new SimpleXMLElement('<store url="' . $storeUrl. '" date="'.$date.'" time="'.$time.'" name="' . $shopName . '"></store>');
        self::$root_node = self::$doc->addChild('products');

        Mage::log('Fontis/Australia_Model_Shopbot_Cron: Iterating: ' . $products->getSize() . ' products...');
        Mage::getSingleton('core/resource_iterator')->walk($products->getSelect(), array('addProductXmlCallbackShopBot'));

        // call XML generation function one last time to process remaining batch
        addProductXmlShopBot();
        Mage::log('Fontis/Australia_Model_Shopbot_Cron: Iteration complete');

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
