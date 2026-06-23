(() => {
  const executeToken = (field) =>
    new Promise((resolve) => {
      const siteKey = field.getAttribute("data-sitekey");
      const action = field.getAttribute("data-action");

      grecaptcha.ready(() => {
        grecaptcha.execute(siteKey, { action }).then((token) => {
          field.value = token;
          resolve();
        });
      });
    });

  document.addEventListener(
    "submit",
    (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      const field = form.querySelector(".fcf7-recaptcha-token");
      if (!field || typeof grecaptcha === "undefined") {
        return;
      }

      if (form.dataset.fcf7RecaptchaReady === "1") {
        delete form.dataset.fcf7RecaptchaReady;
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();

      executeToken(field).then(() => {
        form.dataset.fcf7RecaptchaReady = "1";
        if (typeof form.requestSubmit === "function") {
          form.requestSubmit();
        } else {
          form.dispatchEvent(new Event("submit", { cancelable: true, bubbles: true }));
        }
      });
    },
    true,
  );
})();
