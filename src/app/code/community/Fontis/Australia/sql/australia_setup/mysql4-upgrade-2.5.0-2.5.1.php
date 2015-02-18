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

$installer = $this;
$installer->startSetup();


#$installer->run("ALTER TABLE " . $this->getTable('directory_country_region') . " ENGINE = MyISAM;");
$installer->run("ALTER TABLE " . $this->getTable('australia_postcode') . " ENGINE = MyISAM;");
$installer->run("ALTER TABLE " . $this->getTable('australia_postcode') . " DROP PRIMARY KEY;");
$installer->run("ALTER TABLE " . $this->getTable('australia_postcode') . " CHANGE postcode postcode CHAR(4) NOT NULL default '    ';");
$installer->run("ALTER TABLE " . $this->getTable('australia_postcode') . " CHANGE country_id country_id CHAR(2) NOT NULL default 'AU';");
$installer->run("DROP INDEX `country_id_2` ON " . $this->getTable('australia_postcode') . ";");
$installer->run("DROP INDEX `country_id_3` ON " . $this->getTable('australia_postcode') . ";");
$installer->run("CREATE INDEX `region_code_idx` ON " . $this->getTable('australia_postcode') . " (region_code);");
$installer->run("CREATE FULLTEXT INDEX `city_idx` ON " . $this->getTable('australia_postcode') . " (city);");


$installer->endSetup();
