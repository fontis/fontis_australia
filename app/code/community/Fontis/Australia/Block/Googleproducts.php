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
class Fontis_Australia_Block_Googleproducts extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    // Map in some of the values not normally visible
    protected $magentoOptions = array('is_salable' => 'is_saleable', 'manufacturer_name' => 'manufacturer_name', 'final_price' => 'final_price');
    
    public function __construct()
    {
        $this->addColumn('magento', array(
            'label' => Mage::helper('adminhtml')->__('Magento product attribute'),
            'size'  => 28,
        ));
        $this->addColumn('xmlfeed', array(
            'label' => Mage::helper('adminhtml')->__('Google Products feed tag'),
            'size'  => 28
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add linked attribute');
        
        parent::__construct();
        $this->setTemplate('fontis/australia/system/config/form/field/array_dropdown.phtml');
        
        // product options
        $eav_config_model = Mage::getModel('eav/config');
        $attributes = $eav_config_model->getEntityAttributeCodes('catalog_product');

        foreach($attributes as $att_code)
        {
            $attribute = $eav_config_model->getAttribute('catalog_product', $att_code);
            if ($att_code != '')
            {
                $this->magentoOptions[$att_code] = $att_code;
            }
        }
        asort($this->magentoOptions);
    }

    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if($columnName == 'magento')
        {
            $rendered = '<select name="'.$inputName.'">';
            foreach($this->magentoOptions as $att => $name)
            {
                $rendered .= '<option value="'.$att.'">'.$name.'</option>';
            }
            $rendered .= '</select>';
        }
        else
        {
            $rendered = '<select name="' . $inputName . '">';
            $model = Mage::getModel('australia/googleproducts');
            foreach ($model->available_fields as $field) {
                $rendered .= '<option value="'.$field.'">'.str_replace("_", " ", $field)."</option>";
            }            
            $rendered .= '</select>';
        }
        
        return $rendered;
    }
    
    
}
