
(function () {
  "use strict";

  var settings = window.fcf7SpamProtection || {};

  function paint(root) {
    var canvas = root.querySelector(".fcf7-sp-canvas");
    if (!canvas || !canvas.getContext) {
      return;
    }

    var src = canvas.getAttribute("data-image") || "";
    var ctx = canvas.getContext("2d");

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (!src) {
      return;
    }

    var img = new Image();
    img.onload = function () {
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    };
    img.src = src;
  }

  function setBusy(root, busy) {
    var button = root.querySelector(".fcf7-sp-refresh");
    if (button) {
      button.classList.toggle("is-busy", !!busy);
      button.disabled = !!busy;
    }
  }

  function refresh(root) {
    if (!settings.ajaxUrl || !settings.nonce) {
      return;
    }

    var body = new FormData();
    body.append("action", "fcf7_spam_protection_refresh");
    body.append("nonce", settings.nonce);
    body.append("method", root.getAttribute("data-method") || "text");
    body.append("ops", root.getAttribute("data-ops") || "plus");
    body.append("max", root.getAttribute("data-max") || "10");
    body.append("width", root.getAttribute("data-width") || "160");
    body.append("height", root.getAttribute("data-height") || "50");
    body.append("bg", root.getAttribute("data-bg") || "");
    body.append("color", root.getAttribute("data-color") || "");
    body.append("noise", root.getAttribute("data-noise") || "medium");
    body.append("form_id", root.getAttribute("data-form-id") || "0");

    setBusy(root, true);

    fetch(settings.ajaxUrl, {
      method: "POST",
      credentials: "same-origin",
      body: body,
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (payload) {
        if (!payload || !payload.success || !payload.data) {
          return;
        }

        var token = root.querySelector(".fcf7-sp-token");
        if (token) {
          token.value = payload.data.token;
          token.defaultValue = payload.data.token;
        }
        root.setAttribute("data-issued", String(payload.data.issued));

        var canvas = root.querySelector(".fcf7-sp-canvas");
        if (canvas && payload.data.image) {
          canvas.setAttribute("data-image", payload.data.image);
          paint(root);
        }

        var question = root.querySelector(".fcf7-sp-question");
        if (question && payload.data.question) {
          question.textContent = payload.data.question;
        }

        var answer = root.querySelector(".fcf7-sp-answer");
        if (answer) {
          answer.value = "";
        }
      })
      .catch(function () {
        /* Network failure leaves the baked-in question in place — still answerable. */
      })
      .finally(function () {
        setBusy(root, false);
      });
  }

  function init(root) {
    if (!root || root.dataset.fcf7SpInit === "1") {
      return;
    }
    root.dataset.fcf7SpInit = "1";

    paint(root);

    var button = root.querySelector(".fcf7-sp-refresh");
    if (button) {
      button.addEventListener("click", function (event) {
        event.preventDefault();
        refresh(root);
      });
    }

    var issued = parseInt(root.getAttribute("data-issued") || "0", 10);
    var maxAge = parseInt(root.getAttribute("data-max-age") || "3600", 10);
    var age = Math.floor(Date.now() / 1000) - issued;

    if (issued > 0 && maxAge > 0 && age > maxAge * 0.8) {
      refresh(root);
    }
  }

  function initAll(scope) {
    (scope || document).querySelectorAll(".fcf7-sp").forEach(init);
  }

  document.addEventListener("DOMContentLoaded", function () {
    initAll(document);
  });

  document.addEventListener("fcf7:repeater-row-added", function (event) {
    var row = event.detail && event.detail.row;
    if (!row) {
      return;
    }
    row.querySelectorAll(".fcf7-sp").forEach(function (root) {
      delete root.dataset.fcf7SpInit;
      init(root);
      refresh(root);
    });
  });

  document.addEventListener("wpcf7submit", function (event) {
    initAll(event.target);
    (event.target || document).querySelectorAll(".fcf7-sp").forEach(refresh);
  });
})();
