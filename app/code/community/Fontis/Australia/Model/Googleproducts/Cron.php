<?php

class Fontis_Australia_Model_Googleproducts_Cron extends Fontis_Australia_Model_FeedCronBase {
    public $config_path = 'googleproductsfeed';

    protected $doc;
    protected $required_fields = array(
        "sku"         => "g:id",
        "name"        => "title",
        "link"        => "g:link",
        "final_price" => "g:price",
        "description" => "description",
        "image"       => "g:image_link",
    );

    public static function update() {
        // Static launcher for Magento's cron logic
        $obj = new self();
        $obj->generateFeed();
    }

    protected function getConfig($key)
    {
        return Mage::getStoreConfig('fontis_feeds/' . $this->config_path . '/' . $key);
    }

    /*
     * Instantiate the XML object
     */
    protected function setupStoreData() {
        // have to use XMLWriter, because SimpleXML destroys the g: of a bunch of the attribute names
        $this->doc = new XMLWriter();
        $this->doc->openMemory();
		$this->doc->setIndent(true);
		$this->doc->setIndentString('    ');
        $this->doc->startDocument('1.0', 'UTF-8');
        $this->doc->startElement('feed');
        $this->doc->writeAttribute('xmlns', 'http://www.w3.org/2005/Atom');
        $this->doc->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');

        $this->doc->writeElement('title', $this->getConfig('title'));

        $this->doc->startElement('link');
        $this->doc->writeAttribute('rel', 'self');
        $this->doc->writeAttribute('href', $this->store->getBaseUrl());
        $this->doc->endElement();

        $date = new Zend_Date();
        $this->doc->writeElement('updated', $date->get(Zend_Date::ATOM));

        $this->doc->startElement('author');
        $this->doc->writeElement('name', $this->getConfig('author'));
        $this->doc->endElement();

        $url = $this->store->getBaseUrl();
        $day = $date->toString('yyyy-MM-dd');
        $path = Mage::getStoreConfig('fontis_feeds/'.$this->config_path.'/output');
        $filename = $path . '/' . str_replace('+', '-', strtolower(urlencode($this->store->getName()))) . '-products.xml';

        $this->doc->writeElement('id', 'tag:' . $url . ',' . $day . ':' . $filename);
    }

    /*
     * Add generated data to the feed element
     */
    protected function  populateFeedWithBatchData($batch_data) {
        $fields = $this->collectAttributeMapping();
        $defaultCondition = $this->getConfig('default_condition');

        foreach($batch_data as $product) {
            $this->doc->startElement('entry');

            foreach($fields as $key => $tag) {
                if(!is_array($tag)) {
                    $feed_tags = array($tag);
                } else {
                    $feed_tags = $tag;
                }
                foreach($feed_tags as $feed_tag) {
                    switch ($feed_tag) {
                        case 'g:price':
                            // Prices require two decimal places.
                            $safe_string = sprintf('%.2f', $product[$key]);
                            $this->doc->writeElement($feed_tag, $safe_string);
                            break;

                        case 'g:link':
                            // Links must be written as an attribute
                            $this->doc->startElement($feed_tag);
                            $this->doc->writeAttribute('href', $product[$key]);
                            $this->doc->endElement();
                            break;

                        case 'g:condition':
                            // allow condition override
                            if(isset($product[$key]) && $product[$key] != 0) {
                                $this->doc->writeElement($feed_tag, $product[$key]);
                            } else {
                                $this->doc->writeElement($feed_tag, $defaultCondition);
                            }
                            break;
                            
                        case 'g:image_link':
                            $safe_string = $product[$key];
                            // check if the link is a full URL
                            if(substr($product[$key], 0, 5) != 'http:') {
                                $safe_string = $this->store->getBaseUrl('media') . 'catalog/product' . $product[$key];
                            }

                            $this->doc->writeElement($feed_tag, $safe_string);
                            break;

                        default:
                            // Google doesn't like HTML tags in the feed
                            $safe_string = strip_tags($product[$key]);

                            $this->doc->writeElement($feed_tag, $safe_string);
                            break;
                    }
                }
            }

            $this->doc->endElement();
        }
    }

    protected function finaliseStoreData() {
        // Write the end of the xml document
        $this->doc->endElement();
        $this->doc->endDocument();

        // Write dom to file
        $clean_store_name = str_replace('+', '-', strtolower(urlencode($this->store->getName())));
        $filename = $clean_store_name . '-products.xml';
        $io = new Varien_Io_File();
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $this->getPath()));
        $io->write($filename, $this->doc->outputMemory());
        $io->close();
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
            
            // If the attribute already exists in the mapping list (not the
            // required list) then add the tag as an array. This supports
            // things like the g:image_link tag which allows for multiple
            // entities in each entry.
            if(isset($fields[$attr])) {
                if(is_array($fields[$attr])) {
                    $fields[$attr][] = $feed_tag;
                } else {
                    $cur = $fields[$attr];
                    $fields[$attr] = array($cur, $feed_tag);
                }
            } else {
                $fields[$attr] = $feed_tag;
            }
        }
        //$this->log("Final mapping: ". var_dump($fields));

        if(!in_array('g:condition', array_values($fields)))
        {
            $fields['UNSET_CONDITION'] = 'g:condition';
        }

        return $fields;
    }
}
