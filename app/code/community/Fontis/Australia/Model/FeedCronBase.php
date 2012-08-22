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

abstract class Fontis_Australia_Model_FeedCronBase {
    protected $BATCH_SIZE = 100;
    protected $accumulator;
    protected $product_category_accumulator;
    protected $store;  
    protected $required_fields = array();
    protected $generate_product_category = false;
    
    protected function getPath() {
        $path = "";
        $output_path = Mage::getStoreConfig('fontis_feeds/'.$this->config_path.'/output');

        if (substr($output_path, 0, 1) == "/") {
            $path = $output_path . '/';
        } else {
            $path = $this->info("base_dir") . '/' . $output_path . '/';
        }

        return str_replace('//', '/', $path);
    }

    public function generateFeed() {
        $this->log('Entering update function');
    	if (!Mage::getStoreConfig('fontis_feeds/'.$this->config_path.'/active')) {
            $this->log('Module disabled');
    	} else {
    	    $this->setupAppData();
    	    
    	    $stores = Mage::app()->getStores();
            $this->log("Found " . count($stores) . " stores - starting to process.");
            foreach($stores as $this->store) {
                $this->log('Processing store: ' . $this->store->getName());
                if ($this->generate_product_category) {
                    // Create the entire products xml file
                    $this->batchProcessStore();
                    // Create for each leaf category, their products xml file
                    $categories = $this->getCategoriesXml();
                    foreach($categories['link_ids'] as $category_id) {
                        $this->log('Generating Product Category XML File: ' . $category_id);
                        $this->batchProcessStore($category_id);
                    }
                } else {
                    $this->batchProcessStore();
                }                         
            }            
            $this->finaliseAppData();
        }
        $this->log('Finished update function');
    }
    
    private function batchProcessStore($cat_id = null) {
        $this->setupStoreData();
        
        $product_class = Mage::getModel('catalog/product');
        $product_collection = $product_class->getCollection();
        $product_collection->setStoreId($this->store);
        $product_collection->addStoreFilter();
        $product_collection->addAttributeToSelect('*');
        $product_collection->addAttributeToFilter('status', 1);
        $product_collection->addAttributeToFilter('visibility', 4);

        if ($cat_id != -1 && $cat_id) {
            $product_collection->getSelect()->where("e.entity_id IN (
                SELECT product_id FROM catalog_category_product WHERE category_id = ". $cat_id ."
                )");
            $product_collection->getSelect()->order('product_id');
        }

        $skip_query = false;
        $include_all = Mage::getStoreConfig('fontis_feeds/'.$this->config_path.'/include_all_products');
        $override_attribute_code = Mage::getStoreConfig('fontis_feeds/'.$this->config_path.'/custom_filter_attribute');
        if ($override_attribute_code != '0') {
            // TODO: Find out why 'no' was originally included in these filters...
            if ($include_all) {
                // Include all with exceptions - test for exclusion by requiring a 'null' value
                $this->log("Include all products, but exclude where $override_attribute_code = true");
                $product_collection->addAttributeToFilter($override_attribute_code, array('in' => array(0, '0', '', false, 'no')));
            } else {
                // Exclude all with exceptions - test for inclusion by requiring something other than a 'null' value
                $this->log("Exclude all products, but include where $override_attribute_code = true");
                $product_collection->addAttributeToFilter($override_attribute_code, array('nin' => array(0, '0', '', false, 'no')));
            }
        } else {
            if ($include_all) {
                // Include all with no exceptions - the simple case
                $this->log("Include all products");
            } else {
                // Exclude all with no exceptions - no results
                $this->log("Exclude all products");
                $skip_query = true;
            }
        }

        if (!$skip_query) {
            //$this->log("Iterating using SQL:\n" . $product_collection->getSelect());
            Mage::getSingleton('core/resource_iterator')->walk($product_collection->getSelect(), array(array($this, "productBatchCallback")));
            $this->log('Iterating: ' . $product_collection->getSize() . ' products...');
            // Finish off anything left in the array (if we didn't process a multiple of BATCH_SIZE)
            $this->collectDataForAccumulatedEntities();
        }

        $this->finaliseStoreData($cat_id);
    }

    public function productBatchCallback ($args) {
    	$this->accumulator[] = $args['row']['entity_id'];
    	if(count($this->accumulator) >= $this->BATCH_SIZE) {
	    $this->collectDataForAccumulatedEntities();
	}
    }
    
    private function collectDataForAccumulatedEntities() {
        $total = count($this->accumulator);
    	// $this->log("Submitting ".$total." entity ids: ". join(array_slice($this->accumulator, 0, 8), ", "). "... ". $this->accumulator[$total-1]);
    	
    	// Build file descriptor list for talking to sub process
    	$descriptorspec = array(
                    0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
                    1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
                    2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
                    );
		$pipes = array();
		
		// Build child path from current file location and Magento path delimiter
		$exec_path = escapeshellarg(dirname(__FILE__) . DS . "Child.php");

    	// Open child process with proc_open
        //$this->log("Opening child: ".$exec_path);
		$process = proc_open('php '.$exec_path, $descriptorspec, &$pipes);

        if (!is_resource($process)) {
            $this->log("Error opening child");
            fclose($pipes[0]);
        } else {
            // Write entity id/attribute info to pipe
            $data = array(
                "magento_path" => $this->info("base_dir"),
                "store_id" => $this->info("store_id"),
                "entity_ids" => $this->accumulator,
                "attributes" => array(),
                "generate_categories" => $this->generate_categories,
                "config_path" => $this->config_path
            );
            $this->accumulator = array();
            
            fwrite($pipes[0], json_encode($data));
            fclose($pipes[0]);
    		
    		// Read child's output until it finishes or child is no longer running
    		// TODO: Set time limit (in case child never finishes)
    		// TODO: Add stream blocking using stream_select
    		$result = "";
	        do {
	        	$result .= fgets($pipes[1]);
	        	$state = proc_get_status($process);
	        } while (!feof($pipes[1]) && $state['running']);

            // read JSON-encoded data from child process
            $batch_data = json_decode($result, true);
    
            if(is_array($batch_data)) {
                $this->populateFeedWithBatchData($batch_data);
            } else {
                $this->log("Could not unserialize to array:");
                $this->log($result);
                $this->log($batch_data);
            }
	        
           // Close child process with proc_close (should already be finished, but just in case)
           $exit_status = proc_close($process);
           //self::log("Child exit value: ".$exit_status);
        }   
         	
    	// close remaining file handles
    	@fclose($pipes[1]);
    }

    protected function collectLinkedAttributes () {
        $result = @unserialize(Mage::getStoreConfig('fontis_feeds/'.$this->config_path.'/m_to_xml_attributes', $this->info('store_id')));
        if (is_array($result)) {
            //$this->log("Found mapping: " . Mage::getStoreConfig('fontis_feeds/'.$this->config_path.'/m_to_xml_attributes', $this->store->getId()));
            return $result;
        } else {
            return array();
        }
    }    

    protected function collectAttributeMapping() {
        $fields = $this->required_fields;
        $flipped = array_flip($fields);
        foreach ($this->collectLinkedAttributes() as $id => $field_data) {
            $attr = $field_data['magento'];
            $feed_tag = $field_data['xmlfeed'];
            //$this->log("Comparing $attr, $feed_tag with:\n" . var_dump($flipped));
            // Remapping default fields: If the feed tag exists in the required fields we remove it and replace it with the user-configured one.
            if (array_key_exists($feed_tag, $flipped)) {
                $existing_key = $flipped[$feed_tag];
                //$this->log("Found $existing_key => $feed_tag - deleting");
                unset($fields[$existing_key]);
            }
            $fields[$attr] = $feed_tag;
        }
        //$this->log("Final mapping: ". var_dump($fields));

        return $fields;
    }
    
    // NOTE: Subclasses can use any or all of the methods below to do setup/teardown, and to process
    // generated data at the batch, store or application level.    
    protected function setupAppData() {
        // For feeds generating a single file, set it up here
    }    
    protected function setupStoreData() {
        // For feeds generating a single file, set it up here
    }    
    protected function populateFeedWithBatchData($batch_data) {
        // Process returned data at the batch level
    }    
    protected function finaliseStoreData() {
        // Process returned data at the store level
    }    
    protected function finaliseAppData() {
        // Process returned data at the application level
    }
    
    protected function info($info_to_get) {
        $store = $this->store;
        $core_date = Mage::getModel('core/date')->timestamp(time());

        switch($info_to_get) {
            case "store_id":
                $info = $store->getId();
                break;
            case "store_url":
                $info = $store->getBaseUrl();
                break;
            case "shop_name":
                $info = $store->getName();
                break;
            case "date":
                $info = date("d-m-Y", $core_date);
                break;
            case "time":
                $info = date("h:i:s", $core_date);
                break;
            case "clean_store_name":
                $info = str_replace('+', '-', strtolower(urlencode($store->getName())));
                break;
            case "base_dir":
                $info = Mage::getBaseDir();
                break;
        }
        return $info;
    }
    
    public function log($mesg) {
    	//print $mesg."\n";
        Mage::log(get_class($this).": ".$mesg);
    }
}
