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
     * Exports orders defined by id in post param "order_ids"
     * to csv and offers file directly for download when finished.
     */
    public function exportAction()
    {
        $orders = $this->getRequest()->getPost('order_ids', array());
        
        try
        {
        	$filePath = Mage::getModel('australia/shipping_carrier_eparcel_export')->exportOrders($orders);
        	
        	$this->_prepareDownloadResponse( basename($filePath), file_get_contents($filePath) );
        }
        catch(Dhmedia_EparcelExport_Exception $e)
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
