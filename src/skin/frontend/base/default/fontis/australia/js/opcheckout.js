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

/**
 * Returns the ID of a region using the region code
 */
function getRegionId(regionCode, progress) {
    for (var regionId in australiaCountryRegions.AU) {
        if (!australiaCountryRegions.AU.hasOwnProperty(regionId)) {
            continue;
        }
        if (australiaCountryRegions.AU[regionId].code == regionCode) {
            return regionId;
        }
    }
    return $(progress + ':region_id').value;
}

function validateAddress(progress, section) {
    checkout.setLoadWaiting(progress);
    var request = new Ajax.Request(
        section.saveUrl,
        {
            method: 'post',
            onComplete: section.onComplete,
            onSuccess: section.onSave,
            onFailure: checkout.ajaxFailure.bind(checkout),
            parameters: Form.serialize(section.form)
        }
    );
}

var addressValidationModal;
function createModal(title, content) {
    var settings = {
        draggable: false,
        resizable: false,
        closable: true,
        className: "magento",
        windowClassName: "address-validator",
        title: title,
        top: 40,
        width: 520,
        zIndex: 1000,
        recenterAuto: false,
        hideEffect: Element.hide,
        showEffect:Element.show,
        id:"address-validator-window"
    };
    addressValidationModal = Dialog.info(content, settings);
}

function save(progress, section) {
    if (checkout.loadWaiting !== false) {
        return;
    }

    var validator = new Validation(section.form);
    if (validator.validate()) {
        checkout.setLoadWaiting(progress);

        if ($(progress + '-new-address-form').visible() && $(progress + ':country_id').value == 'AU') {
            var request = new Ajax.Request(
                addressValidationUrl,
                {
                    method: 'post',
                    onComplete: section.onComplete,
                    onSuccess: function(transport) {
                        var params = request.parameters;
                        var street1 = params[progress + '[street][]'][0];
                        var street2 = params[progress + '[street][]'][1] === '' ? '' : ' ' + params[progress + '[street][]'][1];
                        var city = params[progress + '[city]'];
                        var postcode = params[progress + '[postcode]'];
                        var country = params[progress + '[country_id]'];
                        var region = australiaCountryRegions[country][params[progress + '[region_id]']].code;
                        var address = street1 + street2 + ', ' + city + ', ' + region + ' ' + postcode + ', ' + country;

                        var response = transport.responseText.evalJSON();
                        if (response.ValidAustralianAddress) {
                            var validStreet = response.Address.AddressLine;
                            var validCountry = response.Address.Country.CountryCode;
                            var validPostcode = response.Address.PostCode;
                            var validRegion = response.Address.StateOrTerritory;
                            var validCity = response.Address.SuburbOrPlaceOrLocality;
                            var validAddress = validStreet + ', ' + validCity + ', ' + validRegion + ' ' + validPostcode + ', ' + validCountry;

                            var invalidAddress = false;
                            if (
                                street1 != validStreet ||
                                city != validCity ||
                                region != validRegion ||
                                postcode != validPostcode
                            ) {
                                invalidAddress = true;
                            }
                            if (invalidAddress) {
                                var addressSuggest = addressSuggestTemplate;
                                addressSuggest = addressSuggest.replace('Provided Address', address);
                                addressSuggest = addressSuggest.replace('Validated Address', validAddress);
                                addressSuggest = addressSuggest.replace('submit-valid-x-address', 'submit-valid-' + progress + '-address');
                                addressSuggest = addressSuggest.replace('submit-user-x-address', 'submit-user-' + progress + '-address');
                                addressSuggest = addressSuggest.replace('cancel-x-address', 'cancel-' + progress + '-address');
                                createModal('Address Validation', addressSuggest);

                                // Put observers on the buttons in the modal
                                $('submit-valid-' + progress + '-address').observe('click', function() {
                                    addressValidationModal.close();
                                    $(progress + ':street1').value = validStreet;
                                    $(progress + ':street2').value = '';
                                    $(progress + ':city').value = validCity;
                                    $(progress + ':region_id').value = getRegionId(validRegion, progress);
                                    $(progress + ':postcode').value = validPostcode;
                                    validateAddress(progress, section);
                                });

                                $('submit-user-' + progress + '-address').observe('click', function() {
                                    addressValidationModal.close();
                                    validateAddress(progress, section);
                                });

                                $('cancel-' + progress + '-address').observe('click', function() {
                                    addressValidationModal.close();
                                });
                            } else {
                                validateAddress(progress, section);
                            }
                        } else {
                            var addressFailure = addressFailureTemplate;
                            addressFailure = addressFailure.replace('Incorrect Address', address);
                            addressFailure = addressFailure.replace('submit-invalid-x-address', 'submit-invalid-' + progress + '-address');
                            addressFailure = addressFailure.replace('cancel-invalid-x-address', 'cancel-invalid-' + progress + '-address');
                            createModal('Address Validation', addressFailure);

                            // Put observers on the buttons in the modal
                            $('submit-invalid-' + progress + '-address').observe('click', function() {
                                addressValidationModal.close();
                                validateAddress(progress, section);
                            });

                            $('cancel-invalid-' + progress + '-address').observe('click', function() {
                                addressValidationModal.close();
                            });
                        }
                    },
                    onFailure: checkout.ajaxFailure.bind(checkout),
                    parameters: Form.serialize(section.form)
                }
            );
        } else {
            validateAddress(progress, section);
        }
    }
}

Billing.prototype.save = function() {
    save('billing', this);
};

Shipping.prototype.save = function() {
    save('shipping', this);
};
