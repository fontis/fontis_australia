var FAddressValidation = Class.create();
FAddressValidation.prototype = {
    initialize: function () {},

    submit : function() {
        // Use an event handler so that our onFormSubmit function gets the formId so that we can insert our
        // hidden input
        varienGlobalEvents.attachEventHandler('formSubmit', this.onFormSubmit.bind(this));
        editForm.submit();
    },

    onFormSubmit : function(formId) {
        $(formId).insert(new Element('input', {
                type  : 'hidden',
                name  : 'override_validation',
                value : 'true'
            }
        ));
    }
};

var fAddressValidationForm = new FAddressValidation();
