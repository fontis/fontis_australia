<?php

use Guzzle\Http\Exception\BadResponseException;

class Fontis_Australia_Adminhtml_Australia_AddressValidation_DeliveryChoicesController extends Mage_Adminhtml_Controller_Action
{
    public function testLoginAction()
    {
        /** @var $apiClient \Auspost\DeliveryChoice\DeliveryChoiceClient */
        $apiClient = Mage::helper("australia/address_deliveryChoices")->getApiClient();
        $testCommand = $apiClient->getCommand("ValidateAddress", array(
            "address_line_1" => "",
            "state" => "",
            "suburb" => "",
            "postcode" => "",
            "country" => "",
        ));

        $return = array();

        try {
            $testCommand->execute();
            $response = $testCommand->getResponse();
            $return["status_code"] = $response->getStatusCode();
        } catch (BadResponseException $e) {
            $return["status_code"] = $e->getResponse()->getStatusCode();
        }

        $this->getResponse()->setHeader("Content-type", "application/json")
            ->setBody(Zend_Json::encode($return));
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        $actionName = strtolower($this->getRequest()->getActionName());
        switch ($actionName) {
            case "testlogin":
                return Mage::getSingleton("admin/session")->isAllowed("system/config/fontis_australia");
            default:
                return false;
        }
    }
}
