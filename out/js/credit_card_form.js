/*
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/oxid-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/oxid-ee/blob/master/LICENSE
 */
/* global WirecardPaymentPage */
var ModuleCreditCardForm = (function($) {
  var debug = true;

  var requestData = null;

  function getOrderButton() {
    return $("#orderConfirmAgbBottom button[type = 'submit']");
  }

  function callback(response) {
    $('#creditcard-form-div')
      .height(350)
      .fadeIn();
    getOrderButton().prop('disabled', false);
  }

  function logError(where, error) {
    if (error.status_code_1) {
      $('#wirecard-cc-error')
        .addClass('alert alert-danger')
        .html(error.status_code_1 + ' ' + error.status_description_1);
    }

    if (debug) {
      // eslint-disable-next-line no-console
      console.error('Error on ' + where + ':', error);
    }
  }

  function setParentTransactionId(response) {
    var form = $('#wirecard-cc-form');
    $.each(response, function(key, value) {
      form.append("<input type='hidden' name='" + key + "' value='" + value + "'>");
    });
    form.append("<input type='hidden' id='jsresponse' name='jsresponse' value='true'>");
    form.submit();
  }

  function submitPaymentForm(event) {
    if (!$('#wirecard-cc-form input#jsresponse').length) {
      event.preventDefault();
      WirecardPaymentPage.seamlessSubmitForm({
        onSuccess: setParentTransactionId,
        onError: function(error) {
          console.log('aaaa');
          logError('seamlessSubmitForm', error);
          console.log('bbbb');
          console.log('requestData', requestData);
          initSeamlessRenderForm();
        },
      });
    }
  }

  function setRequestData(_requestData) {
    requestData = _requestData;
  }

  function initSeamlessRenderForm() {
    WirecardPaymentPage.seamlessRenderForm({
      requestData: requestData,
      wrappingDivId: 'creditcard-form-div',
      onSuccess: callback,
      onError: function(error) {
        logError('seamlessRenderForm', error);
      },
    });
  }

  return {
    init: function(_requestData) {
      setRequestData(_requestData);

      initSeamlessRenderForm();

      var orderButton = getOrderButton();
      orderButton.prop('disabled', true);
      orderButton.on('click', function(event) {
        event.preventDefault();
        submitPaymentForm(event);
      });

      $('#wirecard-cc-form').submit(submitPaymentForm);
    },
  };
})(jQuery);
