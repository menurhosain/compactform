
(function () {
  "use strict";

  function apply(event) {
    var detail = event.detail || {};
    var response = detail.apiResponse || {};
    var stamp = response.fcf7_ts;

    if (!stamp) {
      return;
    }

    var form = event.target || document;
    var input = form.querySelector
      ? form.querySelector('input[name="_fcf7_ts"]')
      : null;

    if (input) {
      input.value = stamp;
    }
  }
  document.addEventListener("wpcf7submit", apply);
})();
