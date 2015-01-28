<?php

require_once __DIR__.'/Helper/Bpay.php';

class Fontis_Australia_Test_Model_Payment_Bpay extends EcomDev_PHPUnit_Test_Case
{

    /**
     * @dataProvider addressDataProvider
     * @backupGlobals disabled
     */
    public function testPoBoxValidations($number, $result)
    {
        $bpay = new Fontis_Australia_Test_Model_Payment_Bpay_Helper_Bpay();

        $this->assertEquals($result, $bpay->_caculateRefMod10v5($number));
    }

    public function addressDataProvider()
    {
        return array(
            array(100000004, 1000000047),
            array(12345, 123455),
            array(324324234324, 3243242343249),
        );
    }
}