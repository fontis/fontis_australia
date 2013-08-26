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
 * @copyright  Copyright (c) 2008 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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
  UNIQUE KEY `dest_country` (`website_id`,`dest_country_id`,`dest_region_id`,`dest_zip`,`condition_name`,`condition_to_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Insert a list of states into the regions database. Magento will then pick
// these up when displaying addresses and allow the user to select from a drop-down
// list, rather than having to type them in manually.
$installer->run("
DELETE FROM `{$this->getTable('directory_country_region')}` WHERE country_id = 'AU';

INSERT INTO `{$this->getTable('directory_country_region')}` (`country_id`, `code`, `default_name`) VALUES 
	('AU', 'ACT', 'Australian Capital Territory');
	INSERT INTO `{$this->getTable('directory_country_region_name')}` (`locale`, `region_id`, `name`) VALUES
	('en_US', LAST_INSERT_ID(), 'Australian Capital Territory'), ('en_AU', LAST_INSERT_ID(), 'Australian Capital Territory');
	
INSERT INTO `{$this->getTable('directory_country_region')}` (`country_id`, `code`, `default_name`) VALUES 
	('AU', 'NSW', 'New South Wales');
	INSERT INTO `{$this->getTable('directory_country_region_name')}` (`locale`, `region_id`, `name`) VALUES
	('en_US', LAST_INSERT_ID(), 'New South Wales'), ('en_AU', LAST_INSERT_ID(), 'New South Wales');
	
INSERT INTO `{$this->getTable('directory_country_region')}` (`country_id`, `code`, `default_name`) VALUES 
	('AU', 'NT', 'Northern Territory');
	INSERT INTO `{$this->getTable('directory_country_region_name')}` (`locale`, `region_id`, `name`) VALUES
	('en_US', LAST_INSERT_ID(), 'Northern Territory'), ('en_AU', LAST_INSERT_ID(), 'Northern Territory');
	
INSERT INTO `{$this->getTable('directory_country_region')}` (`country_id`, `code`, `default_name`) VALUES 
	('AU', 'QLD', 'Queensland');
	INSERT INTO `{$this->getTable('directory_country_region_name')}` (`locale`, `region_id`, `name`) VALUES
	('en_US', LAST_INSERT_ID(), 'Queensland'), ('en_AU', LAST_INSERT_ID(), 'Queensland');
	
INSERT INTO `{$this->getTable('directory_country_region')}` (`country_id`, `code`, `default_name`) VALUES 
	('AU', 'SA', 'South Australia');
	INSERT INTO `{$this->getTable('directory_country_region_name')}` (`locale`, `region_id`, `name`) VALUES
	('en_US', LAST_INSERT_ID(), 'South Australia'), ('en_AU', LAST_INSERT_ID(), 'South Australia');
	
INSERT INTO `{$this->getTable('directory_country_region')}` (`country_id`, `code`, `default_name`) VALUES 
	('AU', 'TAS', 'Tasmania');
	INSERT INTO `{$this->getTable('directory_country_region_name')}` (`locale`, `region_id`, `name`) VALUES
	('en_US', LAST_INSERT_ID(), 'Tasmania'), ('en_AU', LAST_INSERT_ID(), 'Tasmania');
	
INSERT INTO `{$this->getTable('directory_country_region')}` (`country_id`, `code`, `default_name`) VALUES 
	('AU', 'VIC', 'Victoria');
	INSERT INTO `{$this->getTable('directory_country_region_name')}` (`locale`, `region_id`, `name`) VALUES
	('en_US', LAST_INSERT_ID(), 'Victoria'), ('en_AU', LAST_INSERT_ID(), 'Victoria');
	
INSERT INTO `{$this->getTable('directory_country_region')}` (`country_id`, `code`, `default_name`) VALUES 
	('AU', 'WA', 'Western Australia');
	INSERT INTO `{$this->getTable('directory_country_region_name')}` (`locale`, `region_id`, `name`) VALUES
	('en_US', LAST_INSERT_ID(), 'Western Australia'), ('en_AU', LAST_INSERT_ID(), 'Western Australia');");

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('au_postcode')};
CREATE TABLE {$this->getTable('au_postcode')} (
  `country_id` varchar(2) NOT NULL default 'AU',
  `postcode` varchar(4) NOT NULL default '',
  `region_code` varchar(32) NOT NULL default '0',
  `city` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`country_id`,`postcode`, `region_code`, `city`),
  KEY `country_id_2` (`country_id`,`region_code`),
  KEY `country_id_3` (`country_id`,`city`),
  KEY `country_id` (`country_id`),
  KEY `postcode` (`postcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

set_time_limit(0);

// Note: dirname(__FILE__) is used instead of __DIR__ as the latter was not
// available prior to PHP 5.3.0.
$fp = fopen( dirname(__FILE__) . '/postcodes.txt', 'r');

$_values = '';
$i =0;
while ($row = fgets($fp)) {
    if($i++==0){
        $_values = trim($row);
    } else {
        $_values = $_values . ", " . trim($row);
    }

}
//Import all values in a single expression and commit, _much_ faster and avoids timeouts on shared hosting accounts
$installer->run("INSERT INTO {$this->getTable('au_postcode')} (postcode, city, region_code) VALUES ". $_values . ";");

fclose($fp);

$installer->endSetup();
