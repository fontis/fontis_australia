<?php

class Fontis_Australia_Block_Adminhtml_System_Config_AddressValidation_DeliveryChoices_TestLogin extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @return Fontis_Australia_Block_Adminhtml_System_Config_AddressValidation_DeliveryChoices_TestLogin
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate("fontis/australia/system/config/address_validation/delivery_choices/test_login.phtml");
        return $this;
    }

    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * @return string
     */
    public function getTestUrl()
    {
        return $this->getUrl("*/australia_addressValidation_deliveryChoices/testLogin");
    }

    /**
     * @return string
     */
    public function getFieldPrefix()
    {
        return "fontis_australia_address_validation_delivery_choices_account";
    }

    /**
     * @return int[]
     */
    public function getGoodStatusCodes()
    {
        return array(200);
    }
}
