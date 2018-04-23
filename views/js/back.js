(function () {
  function ready(fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else if (document.addEventListener) {
      window.addEventListener('DOMContentLoaded', fn);
    } else {
      document.attachEvent('onreadystatechange', function () {
        if (document.readyState !== 'loading')
          fn();
      });
    }
  }

  function inputChanged(event) {
    var express = document.querySelector('[name=PAYPAL_EC_ENABLED]');
    if (express == null) {
      return;
    }
    express = express.checked;

    var selectVisibility = {
      PAYPAL_EC_ENABLED: express,
      PAYPAL_EC_CREDIT: express && document.querySelector('[name=PAYPAL_EC_CREDIT]').checked,
      PAYPAL_EC_CARDS: express && document.querySelector('[name=PAYPAL_EC_CARDS]').checked,
      PAYPAL_EC_SEPA: express && document.querySelector('[name=PAYPAL_EC_SEPA]').checked,
      PAYPAL_LOGIN_TPL: document.querySelector('[name=PAYPAL_LOGIN_ENABLED]').checked,
    };

    Object.keys(selectVisibility).forEach(function (targetSelector) {
      try {
        var elem = document.querySelector('[name=' + targetSelector + ']');
        if (targetSelector !== 'PAYPAL_EC_ENABLED') {
          elem.disabled = !selectVisibility['PAYPAL_EC_ENABLED'];
        }
        if (targetSelector !== 'PAYPAL_LOGIN_TPL') {
          var image = elem.parentNode.parentNode.querySelector('label > img');
          image.style.filter = 'grayscale(' + (selectVisibility[targetSelector] ? '0' : '100') + '%)';
          image.style.WebkitFilter = 'grayscale(' + (selectVisibility[targetSelector] ? '0' : '100') + '%)';
        }
      } catch (e) {
      }
    });
    [].slice.call(document.querySelectorAll('[name=PAYPAL_LOGIN_TPL]')).forEach(function (elem) {
      var enabled = selectVisibility['PAYPAL_LOGIN_TPL'];
      elem.disabled = !enabled;
      elem.parentNode.querySelector('img').style.filter = 'grayscale(' + (enabled ? '0' : '100') + '%)';
      elem.parentNode.querySelector('img').style.WebkitFilter = 'grayscale(' + (enabled ? '0' : '100') + '%)';
    });
  }
  
  function bindInputs() {
    [
      'PAYPAL_EC_ENABLED',
      'PAYPAL_EC_CREDIT',
      'PAYPAL_EC_CARDS',
      'PAYPAL_EC_SEPA',
      'PAYPAL_LOGIN_ENABLED',
    ].forEach(function (item) {
      [].slice.call(document.querySelectorAll('[name=' + item + ']')).forEach(function (elem) {
        elem.addEventListener('change', inputChanged);
      });
    });
  }

  // var currentDesign = {
  //   stripe_input_placeholder_color: window.stripe_input_placeholder_color,
  //   stripe_button_background_color: window.stripe_button_background_color,
  //   stripe_button_foreground_color: window.stripe_button_foreground_color,
  //   stripe_highlight_color: window.stripe_highlight_color,
  //   stripe_error_color: window.stripe_error_color,
  //   stripe_error_glyph_color: window.stripe_error_glyph_color,
  //   stripe_payment_request_foreground_color:  window.stripe_payment_request_foreground_color,
  //   stripe_payment_request_background_color:  window.stripe_payment_request_background_color,
  //   stripe_input_font_family: window.stripe_input_font_family,
  //   stripe_checkout_font_family: window.stripe_checkout_font_family,
  //   stripe_checkout_font_size: window.stripe_checkout_font_size,
  //   stripe_payment_request_style: window.stripe_payment_request_style,
  // };
  //
  // function handleDemoIframe() {
  //   var newDesign = {
  //     stripe_input_placeholder_color: document.querySelector('input[name="STRIPE_INPUT_PLACEHOLDER_COLOR"]').value,
  //     stripe_button_background_color: document.querySelector('input[name="STRIPE_BUTTON_BACKGROUND_COLOR"]').value,
  //     stripe_button_foreground_color: document.querySelector('input[name="STRIPE_BUTTON_FOREGROUND_COLOR"]').value,
  //     stripe_highlight_color: document.querySelector('input[name="STRIPE_HIGHLIGHT_COLOR"]').value,
  //     stripe_error_color: document.querySelector('input[name="STRIPE_ERROR_COLOR"]').value,
  //     stripe_error_glyph_color: document.querySelector('input[name="STRIPE_ERROR_GLYPH_COLOR"]').value,
  //     stripe_payment_request_foreground_color: document.querySelector('input[name="STRIPE_PAYMENT_REQFGC"]').value,
  //     stripe_payment_request_background_color: document.querySelector('input[name="STRIPE_PAYMENT_REQBGC"]').value,
  //     stripe_input_font_family: document.getElementById('STRIPE_INPUT_FONT_FAMILY').value,
  //     stripe_checkout_font_family: document.getElementById('STRIPE_CHECKOUT_FONT_FAMILY').value,
  //     stripe_checkout_font_size: document.querySelector('input[name="STRIPE_CHECKOUT_FONT_SIZE"]').value,
  //     stripe_payment_request_style: document.getElementById('STRIPE_PRB_STYLE').value,
  //   };
  //
  //   if (JSON.stringify(currentDesign) !== JSON.stringify(newDesign)) {
  //     currentDesign = newDesign;
  //
  //     var request = new XMLHttpRequest();
  //     request.open('POST', window.stripe_color_url, true);
  //
  //     request.onreadystatechange = function () {
  //       if (this.readyState === 4) {
  //         document.getElementById('stripe-demo-iframe').contentWindow.location.reload();
  //       }
  //     };
  //
  //     request.setRequestHeader('Content-Type', 'application/json; charset=UTF-8');
  //     request.send(JSON.stringify(newDesign));
  //     request = null;
  //   }
  // }

  ready(function () {
    // setInterval(handleDemoIframe, 500);
    bindInputs();
    inputChanged();
  });
}());
