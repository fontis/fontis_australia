<?php

$installer = $this;
$installer->startSetup();

Mage::getModel('tax/calculation_rule')->load(2)->delete();
$oRule = Mage::getModel('tax/calculation_rule')->load(1);  //load the default Rule created with the Mage install.
$rateModel = Mage::getModel('tax/calculation_rate')->load('AU-*-*-GST', 'code');

$rateData = array(
    'code' => 'AU-*-*-GST',
    'tax_country_id' => 'AU',
    'tax_region_id' => '*',
    'tax_postcode' => '*',
    'rate' => 10,
);

foreach ($rateData as $dataName => $dataValue) {
    $rateModel->setData($dataName, $dataValue);
}

$rateModel->save();

$iGSTRateId = $rateModel->getId();
$oRule->setTaxRate(array($iGSTRateId))  //note the single element array
    ->setTaxCustomerClass(array(3))  //hard-coded to default retail customer tax class
    ->setTaxProductClass(array(2)) //hard-coded to default product Taxable class
    ->save();

//Configuration values:

$installer->setConfigData('general/country/default', 'AU');
$installer->setConfigData('general/country/allow', 'AU');

// Configuration / Currency Setup
$installer->setConfigData('currency/options/base', 'AUD');
$installer->setConfigData('currency/options/default', 'AUD');
$installer->setConfigData('currency/options/allow', 'AUD');

// Configuration / Shipping Settings
$installer->setConfigData('shipping/origin/country_id', 'AU');

// Configuration / Tax
$installer->setConfigData('tax/classes/shipping_tax_class', 2);

$installer->setConfigData('tax/calculation/price_includes_tax', 1);
$installer->setConfigData('tax/calculation/shipping_includes_tax', 1);
$installer->setConfigData('tax/calculation/apply_after_discount', 1);
$installer->setConfigData('tax/calculation/discount_tax', 1);
$installer->setConfigData('tax/calculation/apply_tax_on', 0);

$installer->setConfigData('tax/defaults/country', 'AU');

$installer->setConfigData('tax/display/type', 2);
$installer->setConfigData('tax/display/shipping', 2);

$installer->setConfigData('tax/cart_display/price', 2);
$installer->setConfigData('tax/cart_display/subtotal', 2);
$installer->setConfigData('tax/cart_display/shipping', 2);

$installer->setConfigData('tax/sales_display/price', 2);
$installer->setConfigData('tax/sales_display/subtotal', 2);
$installer->setConfigData('tax/sales_display/shipping', 2);

////  delete other incorrect tax Rates:

$aRates = Mage::getModel('tax/calculation_rate')->getCollection();
foreach($aRates as $oRate):
    if($oRate->getCode() != 'AU-*-*-GST'){
        $aRates = Mage::getModel('tax/calculation')
            ->getCollection()
            ->addFieldToFilter('tax_calculation_rate_id', array(
                    'eq' => $oRate->getId()
                ))
            ->walk(function($item) {
                $item->delete();
            });
        $oRate->delete();
    }
endforeach;

$installer->endSetup();