/*
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/oxid-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/oxid-ee/blob/master/LICENSE
 */
/* global ElasticPaymentPage */
var ModuleCreditCardForm = (function($) {
  var debug = false;

  var requestData = null;

  function getOrderButton() {
    return $("#orderConfirmAgbBottom button[type = 'submit']");
  }

  function callback(response) {
    $("#cc-spinner").fadeOut();
    $("#creditcard-form-div")
      .height(350)
      .fadeIn();
    getOrderButton().prop("disabled", false);

    if (debug) {
      // eslint-disable-next-line no-console
      console.log(response);
    }
  }

  function logError(where, error) {
    if (error.status_code_1) {
      $("#wirecard-cc-error")
        .addClass("alert alert-danger")
        .html(error.status_code_1 + " " + error.status_description_1);
    }

    if (debug) {
      // eslint-disable-next-line no-console
      console.error("Error on " + where + ":", error);
    }
  }

  function setParentTransactionId(response) {
    var form = $("#wirecard-cc-form");
    $.each(response, function(key, value) {
      form.append("<input type='hidden' name='" + key + "' value='" + value + "'>");
    });
    form.append("<input type='hidden' id='jsresponse' name='jsresponse' value='true'>");
    form.submit();
  }

  function initSeamlessRenderForm() {
    ElasticPaymentPage.seamlessRenderForm({
      requestData: requestData,
      wrappingDivId: "creditcard-form-div",
      onSuccess: callback,
      onError: function(error) {
        logError("seamlessRenderForm", error);
      },
    });
  }

  function setRequestData(_requestData) {
    requestData = _requestData;
  }

  function parseCreditCardFormDataRespone(responseString) {
    try {
      var dataObj = JSON.parse(responseString);
      return JSON.parse(dataObj["requestData"]);
    } catch (ex) {
      return null;
    }
  }

  function createNewTransaction(cb) {
    var ccRequestDataAjaxUrl = $("#ccRequestDataAjaxUrl").val();

    $.get(ccRequestDataAjaxUrl, function(data) {
      var _requestData = parseCreditCardFormDataRespone(data);

      setRequestData(_requestData);

      // execute callback function if one was passed
      if (typeof cb === "function") {
        cb();
      }
    });
  }

  function loadCCForm() {
    createNewTransaction(function() {
      $("#creditcard-form-div").fadeOut();
      $("#cc-spinner").fadeIn();
      initSeamlessRenderForm();
    });
  }

  function submitPaymentForm(event) {
    if (!$("#wirecard-cc-form input#jsresponse").length) {
      event.preventDefault();
      ElasticPaymentPage.seamlessSubmitForm({
        onSuccess: setParentTransactionId,
        onError: function(error) {
          logError("seamlessSubmitForm", error);
          document.getElementById("wirecard-cc-error").scrollIntoView();
          // if it was not just a local form validation error, reload the seamless credit card form to create a new transaction
          if (!error["form_validation_result"]) {
            loadCCForm();
          }
        },
      });
    }
  }

  return {
    init: function() {
      loadCCForm();

      var orderButton = getOrderButton();
      orderButton.prop("disabled", true);
      orderButton.on("click", function(event) {
        event.preventDefault();
        submitPaymentForm(event);
      });

      $("#wirecard-cc-form").submit(submitPaymentForm);
    },
  };
}(jQuery));