(function () {
	// CompactForm - Date Picker (flatpickr)
	function init(scope) {
		var allowedFormats = ["d-m-Y", "Y-m-d", "m/d/Y", "d/m/Y"];

		// HTML5 date inputs deliver YYYY-MM-DD; convert to the chosen display format.
		function formatDateString(dateStr, format) {
			if (!dateStr) return "";
			var parts = dateStr.split("-");
			if (parts.length !== 3) return dateStr;
			var Y = parts[0], M = parts[1], D = parts[2];

			switch (format) {
				case "d-m-Y":
					return D + "-" + M + "-" + Y;
				case "m/d/Y":
					return M + "/" + D + "/" + Y;
				case "d/m/Y":
					return D + "/" + M + "/" + Y;
				case "Y-m-d":
				default:
					return Y + "-" + M + "-" + D;
			}
		}

		(scope || document).querySelectorAll(".fcf7-field-picker-wrapper input.fcf7-datepicker").forEach(function (el) {
			if (el.dataset.fcf7Init) {
				return;
			}
			el.dataset.fcf7Init = "1";

			var configStr = el.getAttribute("data-config");
			var config = {};

			if (configStr) {
				try {
					config = JSON.parse(configStr);
				} catch (e) {
					config = {};
				}
			}

			var dateFormat = allowedFormats.indexOf(config.date_format) !== -1 ? config.date_format : "d-m-Y";
			var mode = config.mode || "single";

			var minDate = formatDateString(config.min_date || "", dateFormat);
			var maxDate = formatDateString(config.max_date || "", dateFormat);
			var defaultDate = formatDateString(config.default_date || "", dateFormat);

			flatpickr(el, {
				mode: mode,
				dateFormat: dateFormat,
				minDate: minDate || undefined,
				maxDate: maxDate || undefined,
				defaultDate: defaultDate || undefined,
			});
		});
	}

	window.fcf7DatePicker = { init: init };

	document.addEventListener("DOMContentLoaded", function () {
		init(document);
	});

	document.addEventListener("fcf7:repeater-row-added", function (e) {
		init(e.detail.row);
	});
})();
