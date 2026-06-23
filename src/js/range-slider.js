
(function () {
  "use strict";

  var SEL = ".fcf7-range";

  function decimalsOf(step) {
    var text = String(step);
    var dot = text.indexOf(".");
    return dot === -1 ? 0 : text.length - dot - 1;
  }

  function format(value, decimals) {
    return decimals > 0 ? Number(value).toFixed(decimals) : String(Math.round(value));
  }

  function positionOf(ratio) {
    return (
      "calc(var(--fcf7-range-handle-size) / 2 + " +
      Math.round(ratio * 100000) / 100000 +
      " * (100% - var(--fcf7-range-handle-size)))"
    );
  }

  function Slider(root) {
    this.root = root;
    this.track = root.querySelector(".fcf7-range-track");
    this.inputs = Array.prototype.slice.call(root.querySelectorAll(".fcf7-range-input"));
    this.from = root.querySelector(".fcf7-range-input--from");
    this.to = root.querySelector(".fcf7-range-input--to");
    this.field = root.querySelector(".fcf7-range-value-field");
    this.readouts = Array.prototype.slice.call(root.querySelectorAll(".fcf7-range-readout-item"));

    this.min = parseFloat(root.getAttribute("data-min"));
    this.max = parseFloat(root.getAttribute("data-max"));
    this.step = parseFloat(root.getAttribute("data-step")) || 1;
    this.double = root.getAttribute("data-handles") === "2";
    this.separator = root.getAttribute("data-separator") || "-";
    this.decimals = decimalsOf(this.step);
  }

  Slider.prototype.span = function () {
    var span = this.max - this.min;
    return span > 0 ? span : 1;
  };

  Slider.prototype.ratio = function (value) {
    var ratio = (value - this.min) / this.span();
    return ratio < 0 ? 0 : ratio > 1 ? 1 : ratio;
  };

  Slider.prototype.constrain = function (moved) {
    if (!this.double || !this.from || !this.to) {
      return;
    }
    var from = parseFloat(this.from.value);
    var to = parseFloat(this.to.value);
    if (from <= to) {
      return;
    }
    if (moved === this.from) {
      this.from.value = to;
    } else {
      this.to.value = from;
    }
  };

  Slider.prototype.prioritise = function (event) {
    if (event.buttons) {
      return;
    }

    var rect = this.track.getBoundingClientRect();
    if (!rect.width) {
      return;
    }

    var ratio = (event.clientX - rect.left) / rect.width;
    if (window.getComputedStyle && window.getComputedStyle(this.track).direction === "rtl") {
      ratio = 1 - ratio;
    }

    var nearFrom =
      Math.abs(ratio - this.ratio(parseFloat(this.from.value))) <=
      Math.abs(ratio - this.ratio(parseFloat(this.to.value)));

    this.from.style.zIndex = nearFrom ? 4 : 3;
    this.to.style.zIndex = nearFrom ? 3 : 4;
  };

  Slider.prototype.render = function () {
    var to = parseFloat(this.to.value);
    var from = this.double && this.from ? parseFloat(this.from.value) : this.min;

    var fromRatio = this.double ? this.ratio(from) : 0;
    var toRatio = this.ratio(to);

    this.root.style.setProperty(
      "--fcf7-range-from",
      this.double ? positionOf(fromRatio) : "0%"
    );
    this.root.style.setProperty("--fcf7-range-to", positionOf(toRatio));

    this.readouts.forEach(function (item) {
      var isFrom = item.getAttribute("data-handle") === "from";
      var number = item.querySelector(".fcf7-range-number");

      if (number) {
        number.textContent = format(isFrom ? from : to, this.decimals);
      }
      item.style.setProperty("--pos", positionOf(isFrom ? fromRatio : toRatio));
    }, this);

    if (this.double && this.field) {
      var next =
        format(from, this.decimals) + " " + this.separator + " " + format(to, this.decimals);

      if (this.field.value !== next) {
        this.field.value = next;
        this.field.dispatchEvent(new Event("input", { bubbles: true }));
        this.field.dispatchEvent(new Event("change", { bubbles: true }));
      }
    }
  };

  Slider.prototype.init = function () {
    if (!this.track || !this.to || isNaN(this.min) || isNaN(this.max)) {
      return;
    }

    var self = this;

    this.inputs.forEach(function (input) {
      input.addEventListener("input", function () {
        self.constrain(input);
        self.render();
      });
    });

    if (this.double && this.from) {
      ["pointerdown", "pointermove"].forEach(function (type) {
        self.track.addEventListener(type, function (event) {
          self.prioritise(event);
        });
      });
    }

    this.render();
  };

  function init(scope) {
    var roots = (scope || document).querySelectorAll(SEL);

    Array.prototype.forEach.call(roots, function (root) {
      if (root.fcf7RangeSlider) {
        return;
      }
      var slider = new Slider(root);
      root.fcf7RangeSlider = slider;
      slider.init();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      init(document);
    });
  } else {
    init(document);
  }

  document.addEventListener("wpcf7reset", function (e) {
    var roots = e.target.querySelectorAll(SEL);
    Array.prototype.forEach.call(roots, function (root) {
      if (root.fcf7RangeSlider) {
        root.fcf7RangeSlider.render();
      }
    });
  });

  document.addEventListener("wpcf7submit", function () {
    init(document);
  });

  document.addEventListener("fcf7:repeater-row-added", function (e) {
    init(e.detail.row);
  });
})();
