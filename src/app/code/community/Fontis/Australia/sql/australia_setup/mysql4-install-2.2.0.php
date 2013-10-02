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
 * @author     Chris Norton
 * @copyright  Copyright (c) 2010 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

set_time_limit(0);

$installer = $this;
$installer->startSetup();

// Create a database table for eParcel table rates.
// This table uses the same structure as the normal table rate table.
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('australia_eparcel')};
CREATE TABLE {$this->getTable('australia_eparcel')} (
  `pk` int(10) unsigned NOT NULL auto_increment,
  `website_id` int(11) NOT NULL default '0',
  `dest_country_id` varchar(4) NOT NULL default '0',
  `dest_region_id` int(10) NOT NULL default '0',
  `dest_zip` varchar(10) NOT NULL default '',
  `condition_name` varchar(20) NOT NULL default '',
  `condition_from_value` decimal(12,4) NOT NULL default '0.0000',
  `condition_to_value` decimal(12,4) NOT NULL default '0.0000',
  `price` decimal(12,4) NOT NULL default '0.0000',
  `price_per_kg` decimal(12,4) NOT NULL default '0.0000',
  `cost` decimal(12,4) NOT NULL default '0.0000',
  `delivery_type` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`pk`),
  UNIQUE KEY `dest_country` ( `website_id` , `dest_country_id` , `dest_region_id` , `dest_zip` , `condition_name` , `condition_to_value` , `delivery_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

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

// Note: dirname(__FILE__) is used instead of __DIR__ as the latter was not
// available prior to PHP 5.3.0.
$postcodefile = dirname(__FILE__) . '/postcodes.csv';
$success = false;

try {
    // Try using LOAD DATA which is extremely fast
    $installer->run("LOAD DATA LOCAL INFILE '$postcodefile' INTO TABLE {$this->getTable('australia_postcode')}
                    FIELDS TERMINATED BY ','
                    OPTIONALLY ENCLOSED BY '\''
                    LINES TERMINATED BY '\\n'");

    $success = true;
} catch(Exception $e) {
    $success = false;
}

// Check if our LOAD DATA method worked (may not work in some environments)
if(!$success) {
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
}

$installer->endSetup();
