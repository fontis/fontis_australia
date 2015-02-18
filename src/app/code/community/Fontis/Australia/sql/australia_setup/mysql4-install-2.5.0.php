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
 *
 * @category   Fontis
 * @package    Fontis_Australia
 * @author     Chris Norton
 * @author     Jonathan Melnick
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

set_time_limit(0);

$installer = $this;
$installer->startSetup();


// Insert a list of states into the regions database. Magento will then pick
// these up when displaying addresses and allow the user to select from a drop-down
// list, rather than having to type them in manually.
$regions = array(
    array('code' => 'ACT', 'name' => 'Australia Capital Territory'),
    array('code' => 'NSW', 'name' => 'New South Wales'),
    array('code' => 'NT', 'name' => 'Northern Territory'),
    array('code' => 'QLD', 'name' => 'Queensland'),
    array('code' => 'SA', 'name' => 'South Australia'),
    array('code' => 'TAS', 'name' => 'Tasmania'),
    array('code' => 'VIC', 'name' => 'Victoria'),
    array('code' => 'WA', 'name' => 'Western Australia')
);

$db = Mage::getSingleton('core/resource')->getConnection('core_read');

foreach($regions as $region) {
    // Check if this region has already been added
    $result = $db->fetchOne("SELECT code FROM " . $this->getTable('directory_country_region') . " WHERE `country_id` = 'AU' AND `code` = '" . $region['code'] . "'");
    if($result != $region['code']) {
        $installer->run(
            "INSERT INTO `{$this->getTable('directory_country_region')}` (`country_id`, `code`, `default_name`) VALUES
            ('AU', '" . $region['code'] . "', '" . $region['name'] . "');
            INSERT INTO `{$this->getTable('directory_country_region_name')}` (`locale`, `region_id`, `name`) VALUES
            ('en_US', LAST_INSERT_ID(), '" . $region['name'] . "'), ('en_AU', LAST_INSERT_ID(), '" . $region['name'] . "');"
        );
    }
}

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('australia_postcode')};
CREATE TABLE {$this->getTable('australia_postcode')} (
  `country_id` varchar(2) NOT NULL default 'AU',
  `postcode` varchar(4) NOT NULL default '',
  `region_code` varchar(6) NOT NULL default '0',
  `city` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`country_id`,`postcode`, `region_code`, `city`),
  KEY `country_id_2` (`country_id`,`region_code`),
  KEY `country_id_3` (`country_id`,`city`),
  KEY `country_id` (`country_id`),
  KEY `postcode` (`postcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


$postcodefile = dirname(__FILE__) . '/postcodes.csv';

// Here we import values in larger expressions, which is slower than LOAD DATA
// but should be available in all environments
$fp = fopen($postcodefile, 'r');

$_values = array();
$i = 0;

while ($row = fgets($fp)) {
    $_values[] = '(' . trim($row) . ')';

    // Process the file in batches
    if($i++ % 1000 == 0) {
        $insertValues = implode(',', $_values);
        $installer->run("INSERT INTO {$this->getTable('australia_postcode')} (country_id, postcode, region_code, city) VALUES ". $insertValues . ";");
        $_values = array();
    }
}

// Insert any remaining values
if(count($_values)) {
    $insertValues = implode(',', $_values);
    $installer->run("INSERT INTO {$this->getTable('australia_postcode')} (country_id, postcode, region_code, city) VALUES ". $insertValues . ";");
}

fclose($fp);


$installer->endSetup();
