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
 *
 * @category   Fontis
 * @package    Fontis_Australia
 * @author     Thai Phan
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Fontis_Australia_Helper_Eparcel extends Mage_Core_Helper_Abstract
{
    const XML_PATH_EMAIL_NOTIFICATION_ENABLED = 'doghouse_eparcelexport/email_notification/enabled';
    const XML_PATH_EMAIL_NOTIFICATION_LEVEL = 'doghouse_eparcelexport/email_notification/level';

    /* AUSTRALIA POST CHARGE CODES */
    private $chargeCodes = array(

        /* Domestic / Standard / Individual */
        'S1', // EPARCEL 1       Domestic
        'S2', // EPARCEL 2       Domestic
        'S3', // EPARCEL 3       Domestic
        'S4', // EPARCEL 4       Domestic
        'S5', // EPARCEL 5       Domestic
        'S6', // EPARCEL 6       Domestic
        'S7', // EPARCEL 7       Domestic
        'S8', // EPARCEL 8       Domestic

        /* Domestic / Standard / Business */
        'B1', // B TO B EPARCEL 1        Domestic
        'B2', // B TO B EPARCEL 2        Domestic
        'B5', // B TO B EPARCEL 5        Domestic

        /* Domestic / Express / Individual */
        'X1', // EXPRESS POST EPARCEL    Domestic
        'X2', // EXPRESS POST EPARCEL 2  Domestic

        /* Domestic / Express / Business */
        'XB1', // EXPRESS POST EPARCEL B2B        Domestic
        'XB2', // EXPRESS POST EPARCEL B2B 2      Domestic

        /* International / Standard */
        'AIR1', // INTERNATIONAL Airmail 1 International
        'AIR2', // INTERNATIONAL Airmail 2 International
        'AIR3', // INTERNATIONAL Airmail - 8 Zones International

        /* International / Express */
        'EPI1', // Express Post International      International
        'EPI2', // Express Post International      International
        'EPI3', // Express Post International – 8 zones    International

        'ECM1', // Express Courier Int'l Merchandise 1      International
        'ECM2', // Express Courier Int'l Merchandise 2     International
        'ECM3', // Express Courier Int'l Merch 8Zone       International

        'ECD1', // EXPRESS COURIER INT'L DOC 1     International
        'ECD2', // EXPRESS COURIER INT'L DOC 2     International
        'ECD3', // Express Courier Int'l Doc – 8 zones     International

        /* Other */

        'CFR', // eParcel Call For Return Domestic
        'PR', // eParcel Post Returns Service    Domestic

        'CS1', // CTC EPARCEL     Domestic
        'CS4', // CTC EPARCEL     Domestic
        'CS5', // CTC EPARCEL 5   Domestic
        'CS6', // CTC EPARCEL 6   Domestic
        'CS7', // CTC EPARCEL 7   Domestic
        'CS8', // CTC EPARCEL 8   Domestic

        'CX1', // CTC EXPRESS POST 500G BRK       Domestic
        'CX2', // CTC EXPRESS POST MULTI BRK      Domestic

        'RPI1', // Registered Post International   International
    );

    /**
     * Returns whether email notifications are enabled.
     *
     * @return bool
     */
    public function isEmailNotificationEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_EMAIL_NOTIFICATION_ENABLED);
    }

    /**
     * Returns the email notification level, i.e. none, notify when despatched,
     * or complete tracking.
     *
     * @return string
     */
    public function getEmailNotificationLevel()
    {
        return Mage::getStoreConfig(self::XML_PATH_EMAIL_NOTIFICATION_LEVEL);
    }

    /**
     * Determines whether a given string is a valid eParcel charge code.
     *
     * @param string $chargeCode
     * @return bool
     */
    public function isValidChargeCode($chargeCode)
    {
        return in_array($chargeCode, $this->chargeCodes);
    }
}
