<?php

class Fontis_Australia_Model_Config_Condition
{
    public function toOptionArray()
    {
        return array(
            array('value' => 'new', 'label' => 'New'),
            array('value' => 'used', 'label' => 'Used'),
            array('value' => 'refurbished', 'label' => 'Refurbished'),
        );
    }
}