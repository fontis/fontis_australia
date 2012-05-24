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
 * @author     Chris Norton
 * @copyright  Copyright (c) 2008 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Australia AJAX Controller
 *
 * The primary purpose of this controller is to allow for AJAX requests to query the postcode database.
 *
 * @category   Fontis
 * @package    Fontis_Australia
 * @module     Australia
 */
class Fontis_Australia_AjaxController extends Mage_Core_Controller_Front_Action
{
    public function suggestAction()
    {
        $this->getResponse()->setBody($this->getLayout()->createBlock('australia/autocomplete')->toHtml());
    }
}
