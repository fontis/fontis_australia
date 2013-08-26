<?php
/**
 * @package    Fontis_Australia
 */

/**
 * Controller handling order export requests.
 */
class Fontis_Australia_EparcelController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Export Orders to CSV
     * 
     * This action exports orders to a CSV file and downloads the file. 
     * The orders to be exported depend on the HTTP POST param "order_ids".
     */
    public function exportAction()
    {
        $orders = $this->getRequest()->getPost('order_ids', array());
        
        try
        {
            /* Build the CSV and retrieve its path */
        	$filePath = Mage::getModel('australia/shipping_carrier_eparcel_export_csv')->exportOrders($orders);
        	
        	/* Download the file */
        	$this->_prepareDownloadResponse( basename($filePath), file_get_contents($filePath) );
        }
        catch(Fontis_Australia_Model_Shipping_Carrier_Eparcel_Export_Exception $e)
        {
        	Mage::getSingleton('core/session')->addError($e->getMessage());

        	$this->_redirect('adminhtml/sales_order/index');
        }
    	catch(Exception $e)
        {
        	Mage::getSingleton('core/session')->addError('An error occurred. ' . $e->getMessage());
        	
        	$this->_redirect('adminhtml/sales_order/index');
        }
    }
}
