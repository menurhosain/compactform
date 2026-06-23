(() => {
  document.addEventListener(
    "wpcf7mailsent",
    (event) => {
      const redirectUrl = event.detail && event.detail.apiResponse && event.detail.apiResponse.redirectUrl;
      if (redirectUrl) {
        window.location.assign(redirectUrl);
      }
    },
    false,
  );
})();
