// CompactForm - Star Rating
(function () {
  function indexForValue(stars, value) {
    var index = 0;
    stars.forEach(function (star, i) {
      if (star.getAttribute("data-value") === value) {
        index = i + 1;
      }
    });
    return index;
  }

  function highlight(stars, count) {
    stars.forEach(function (star, i) {
      star.classList.toggle("is-active", i < count);
    });
  }

  function initRating(wrap) {
    var input = wrap.querySelector(".fcf7-rating-input");
    var stars = Array.prototype.slice.call(wrap.querySelectorAll(".fcf7-star"));

    if (!input || !stars.length) {
      return;
    }

    highlight(stars, indexForValue(stars, input.value));

    stars.forEach(function (star, i) {
      star.addEventListener("mouseenter", function () {
        highlight(stars, i + 1);
      });

      star.addEventListener("mouseleave", function () {
        highlight(stars, indexForValue(stars, input.value));
      });

      star.addEventListener("click", function () {
        input.value = star.getAttribute("data-value");
        input.dispatchEvent(new Event("change", { bubbles: true }));
        input.dispatchEvent(new Event("input", { bubbles: true }));
        highlight(stars, i + 1);
      });

      star.addEventListener("keydown", function (e) {
        if (e.key === "Enter" || e.key === " " || e.key === "Spacebar") {
          e.preventDefault();
          star.click();
        }
      });
    });
  }

  function init(scope) {
    (scope || document)
      .querySelectorAll(".fcf7-rating")
      .forEach(function (wrap) {
        initRating(wrap);
      });
  }

  document.addEventListener("DOMContentLoaded", function () {
    init(document);
  });

  document.addEventListener("wpcf7reset", function (e) {
    init(e.target);
  });

  document.addEventListener("fcf7:repeater-row-added", function (e) {
    init(e.detail.row);
  });
})();
