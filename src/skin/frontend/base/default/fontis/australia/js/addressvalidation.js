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
 * @author     Andrew Rollason
 * @copyright  Copyright (c) 2015 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

function FAusAddressValidation(addressValidationUrl) {
    this.addressValidationUrl = addressValidationUrl;
    this.init();
}

FAusAddressValidation.prototype = {
    init: function() {
        this.addressValidationModal = false;
        this.addressFieldNames = [
            'street',
            'city',
            'region_id',
            'postcode',
            'country_id'
        ];
        this.arrayFields = [
            'street'
        ];
        this.addressValidated = false;
    },

    /**
     * Validate an Address using the AusPost API.
     *
     * @param {Object} address An object representing an Address
     * @param {String} addressType
     * @param {function} sucessCallback
     * @param {function} failureCallback
     */
    validateAddress: function(address, addressType, sucessCallback, failureCallback) {
        var addressValidationRequest = new Ajax.Request(this.addressValidationUrl,
            {
                onSuccess: function(transport) {
                    sucessCallback(transport.responseJSON, addressType);
                },
                onFailure: function(transport) {
                    failureCallback(transport.responseJSON, addressType);
                },
                parameters: address
            }
        );
    },

    /**
     * Display a modal with address suggestions.
     *
     * @param {String} original Original address supplied to the AusPost API
     * @param {String} suggestion Suggested address returned from the AusPost API
     * @param {Object} address
     * @param {String} type
     */
    displaySuccessModal: function(original, suggestion, address, type) {
        // Create a new modal
        var settings = {
            draggable: false,
            resizable: false,
            closable: true,
            className: "magento",
            windowClassName: "address-validator",
            title: 'Suggested Address',
            top: 40,
            width: 520,
            zIndex: 1000,
            recenterAuto: false,
            hideEffect: Element.hide,
            showEffect: Element.show,
            id:"address-validator-window"
        };

        var addressSuggest = addressSuggestTemplate.replace('Provided Address', original)
            .replace('Validated Address', suggestion);

        // Set the content of the modal with the suggestions and show it
        this.addressValidationModal = Dialog.info(addressSuggest, settings);

        var self = this;
        // Put observers on the buttons in the modal
        $('submit-valid-address').observe('click', function() {
            self.useSuggestedAddress(address, type);
        });

        $('submit-user-address').observe('click', function() {
            self.useEnteredAddress();
        });

        $('cancel-address').observe('click', function() {
            self.editAddress();
        });

        $('address-validator-window_close').observe('click', function() {
            self.editAddress();
        });
    },

    /**
     * @param {String} original
     */
    displayFailureModal: function(original) {
        // Create a new modal
        var settings = {
            draggable: false,
            resizable: false,
            closable: true,
            className: "magento",
            windowClassName: "address-validator",
            title: 'Suggested Address',
            top: 40,
            width: 520,
            zIndex: 1000,
            recenterAuto: false,
            hideEffect: Element.hide,
            showEffect: Element.show,
            id:"address-validator-window"
        };

        var addressSuggest = addressFailureTemplate.replace('Incorrect Address', original);

        // Set the content of the modal with the suggestions and show it
        this.addressValidationModal = Dialog.info(addressSuggest, settings);

        var self = this;

        $('submit-invalid-address').observe('click', function() {
            self.useEnteredAddress();
        });

        $('cancel-invalid-address').observe('click', function() {
            self.editAddress();
        });

        $('address-validator-window_close').observe('click', function() {
            self.editAddress();
        });
    },

    useEnteredAddress: function() {
        this.addressValidated = true;
        this.addressValidationModal.close();
        document.fire('fontis_australia:address_validation_complete', {updated: false});
    },

    useSuggestedAddress: function(address, type) {
        this.addressValidated = true;
        this.addressValidationModal.close();
        $(type + ':street1').value = address.street1;
        $(type + ':street2').value = '';
        $(type + ':city').value = address.city;
        $(type + ':region_id').value = address.regionId;
        $(type + ':postcode').value = address.postcode;
        document.fire('fontis_australia:address_validation_complete', {updated: true});
    },

    editAddress: function() {
        this.addressValidated = false;
        this.addressValidationModal.close();
        document.fire('fontis_australia:address_validation_cancelled');
    },

    /**
     * Validate address fields for a given type.
     *
     * @param {string} addressType The type of address fields to validate (billing or shipping).
     */
    validateAddressForm: function(addressType) {
        // Build an address object from the given address type
        var address = {};

        var self = this;
        this.addressFieldNames.each(function(fieldName) {
            var fields;
            // Street is handled in an array of fields and needs an array accessor.
            if (self.arrayFields.indexOf(fieldName) !== -1) {
                fields = $$("[name='" + addressType + "[" + fieldName + "][]']");
                var fieldValue = [];

                fields.each(function(field) {
                    fieldValue.push(field.getValue());
                });

                address[fieldName + '[]'] = fieldValue;
            } else {
                fields = $$("[name='" + addressType + "[" + fieldName + "]']");
                address[fieldName] = fields[0].getValue();
            }
        });

        return this.validateAddress(
            address,
            addressType,
            this._validateAddressSuccessCallback.bind(this),
            this._validateAddressFailureCallback.bind(this));
    },

    /**
     *
     * @param {Object} response
     * @param {String} addressType
     * @private
     */
    _validateAddressSuccessCallback: function(response, addressType) {
        // Only display the modal if the address is not valid.
        if (response.success && !response.validAddress && response.ValidAustralianAddress) {
            this.displaySuccessModal(response.original, response.suggestion, response.suggestedAddress, addressType);
        } else if (response.success && !response.ValidAustralianAddress) {
            this.displayFailureModal(response.original);
        } else {
            this.addressValidated = true;
            document.fire('fontis_australia:address_validation_complete', {updated: false});
        }
    },

    /**
     *
     * @param {Object} response
     * @param {String} addressType
     * @private
     */
    _validateAddressFailureCallback: function(response, addressType) {
        // If the server has reached a failure state we don't want to interrupt the checkout, so we let the customer
        // continue as though their address was valid even though we don't know if it is.
        this.addressValidated = true;
        document.fire('fontis_australia:address_validation_complete', {updated: false});
    },

    /**
     * Update address fields for a given type using a suggestion from the AusPost API.
     *
     * @param {string} addressType
     * @param {Object} suggestion
     */
    updateAddressForm: function(addressType, suggestion) {

    }
};
