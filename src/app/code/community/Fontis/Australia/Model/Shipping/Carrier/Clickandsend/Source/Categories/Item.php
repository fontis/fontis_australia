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
 * @author     Thai Phan
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Fontis_Australia_Model_Shipping_Carrier_Clickandsend_Source_Categories_Item
{
    const RETURNED_GOODS = 21;
    const GIFT = 31;
    const COMMERCIAL_SAMPLE = 32;
    const DOCUMENT = 91;
    const OTHER = 991;
    const PLANT_ANIMAL_OR_FOOD_PRODUCT = 999;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('australia');
        return array(
            array('value' => self::RETURNED_GOODS,               'label' => $helper->__('Returned Goods')),
            array('value' => self::GIFT,                         'label' => $helper->__('Gift')),
            array('value' => self::COMMERCIAL_SAMPLE,            'label' => $helper->__('Commercial Sample')),
            array('value' => self::DOCUMENT,                     'label' => $helper->__('Document')),
            array('value' => self::OTHER,                        'label' => $helper->__('Other')),
            array('value' => self::PLANT_ANIMAL_OR_FOOD_PRODUCT, 'label' => $helper->__('Plant, Animal or Food Product'))
        );
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $helper = Mage::helper('australia');
        return array(
            self::RETURNED_GOODS               => $helper->__('Returned Goods'),
            self::GIFT                         => $helper->__('Gift'),
            self::COMMERCIAL_SAMPLE            => $helper->__('Commercial Sample'),
            self::DOCUMENT                     => $helper->__('Document'),
            self::OTHER                        => $helper->__('Other'),
            self::PLANT_ANIMAL_OR_FOOD_PRODUCT => $helper->__('Plant, Animal or Food Product')
        );
    }
}
