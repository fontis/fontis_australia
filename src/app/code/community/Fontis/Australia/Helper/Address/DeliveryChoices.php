<?php

use Auspost\Common\Auspost;

class Fontis_Australia_Helper_Address_DeliveryChoices extends Mage_Core_Helper_Abstract
{
    /**
     * @return \Auspost\DeliveryChoice\DeliveryChoiceClient
     */
    public function getApiClient()
    {
        $options = array(
            'email_address' => Mage::getStoreConfig('fontis_australia/address_validation/delivery_choices_account_email'),
            'password' => Mage::getStoreConfig('fontis_australia/address_validation/delivery_choices_account_password'),
        );

        return Auspost::factory($options)->get('deliverychoice');
    }
}
