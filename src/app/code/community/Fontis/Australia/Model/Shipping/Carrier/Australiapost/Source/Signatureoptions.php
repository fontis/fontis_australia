<?php

/**
 * Used for Australia Post configuration fields that need the options "never",
 * "required" and "optional"
 */
class Fontis_Australia_Model_Shipping_Carrier_Australiapost_Source_Signatureoptions
{
    const AUTHORITY_LEAVE = 'Y';
    const AUTHORITY_LEAVE_REQUEST = 'R';
    const REQUIRED = 'A';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $helper = Mage::helper('australia');
        return array(
            array('value' => self::AUTHORITY_LEAVE, 'label' => $helper->__('Authority To Leave')),
            array('value' => self::AUTHORITY_LEAVE_REQUEST, 'label' => $helper->__('Authority To leave can be requested')),
            array('value' => self::REQUIRED, 'label' => $helper->__('Signature required')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $helper = Mage::helper('australia');
        return array(
            self::AUTHORITY_LEAVE => $helper->__('Authority To Leave'),
            self::AUTHORITY_LEAVE_REQUEST => $helper->__('Authority To leave can be requested'),
            self::REQUIRED => $helper->__('Signature required'),
        );
    }
}
