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

class Fontis_Australia_Model_ShopBot_Cron extends Fontis_Australia_Model_FeedCronBase {

    public $config_path = 'shopbotfeed';
    public $generate_categories = false;
    protected $doc;
    protected $root_node;
    protected $required_fields = array(
        "name"  => "name",
        "final_price"   => "price",
        "link"  => "link",
        "availability"  => "availability",
        "price" => "Price"
    );

    public static function update() {
        // Static launcher fopr Magento's cron logic
        $obj = new self();
        $obj->generateFeed();
    }

    protected function setupStoreData() {
        $this->doc = new SimpleXMLElement('<store url="' . $this->info("store_url") . '" date="'. $this->info("date") .'" time="'. $this->info("time") .'" name="' . $this->info("shop_name") . '"></store>');
    }

    protected function populateFeedWithBatchData($batch_data) {
        $fields = $this->collectAttributeMapping();
        $this->root_node = $this->doc->addChild('products');
        foreach($batch_data as $product) {
            $product_node = $this->root_node->addChild('product');
            foreach($fields as $key => $feed_tag) {
                //$this->log("Mapping $key to $feed_tag with value: ".(substr($product[$key], 0, 20)));
                // remove carriage returns or HTML tags
                $safe_string = strip_tags($product[$key]);
                $safe_string = preg_replace("/\s*\n\s*/", " ", $safe_string);
                // ...we also need to make it XML safe
                $safe_string = htmlspecialchars($safe_string);
                $product_node->addChild($feed_tag, $safe_string);
            }
        }
    }

    protected function finaliseStoreData() {
        // Use DOM to nicely format XML
        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom_sxml = dom_import_simplexml($this->doc);
        $dom_sxml = $dom->importNode($dom_sxml, true);
        $dom->appendChild($dom_sxml);
        //$this->log("Generated XML:\n".$dom->saveXML());

        // Write dom to file
        $filename = $this->info("clean_store_name") . '-products.xml';
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $this->getPath()));
        $io->write($filename, $dom->saveXML());
        $io->close();
    }
}
