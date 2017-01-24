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
 * @author     Jeremy Champion
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

document.observe('dom:loaded', function() {
    var addressValidation = new FAusAddressValidation(window.addressValidationUrl);

    var australiaOrigBillingSave = Billing.prototype.save;
    Billing.prototype.save = function() {
        if (checkout.loadWaiting != false) return;
        var validator = new Validation(this.form);
        if (validator.validate()) {
            checkout.setLoadWaiting('billing');

            var self = this;
            // FONTIS: Before we trigger the Ajax request to save the billing information we should first
            // do AusPost validation.
            addressValidation.validateAddressForm('billing');
            document.observe('fontis_australia:address_validation_complete', function() {
                checkout.setLoadWaiting(false);
                australiaOrigBillingSave.call(self);
            });

            document.observe('fontis_australia:address_validation_cancelled', function() {
                checkout.setLoadWaiting(false);
            });
        }
    };

    var australiaOrigShippingSave = Shipping.prototype.save;
    Shipping.prototype.save = function() {
        if (checkout.loadWaiting != false) return;
        var validator = new Validation(this.form);
        if (validator.validate()) {
            checkout.setLoadWaiting('shipping');

            var self = this;
            // FONTIS: Before we trigger the Ajax request to save the billing information we should first
            // do AusPost validation.
            addressValidation.validateAddressForm('shipping');
            document.observe('fontis_australia:address_validation_complete', function() {
                checkout.setLoadWaiting(false);
                australiaOrigShippingSave.call(self);
            });

            document.observe('fontis_australia:address_validation_cancelled', function() {
                checkout.setLoadWaiting(false);
            });
        }
    };
});
