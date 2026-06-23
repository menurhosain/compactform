(function ($) {
	// CompactForm - Date & Time Picker (flatpickr)
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

		$(scope || document).find(".fcf7-field-picker-wrapper input.fcf7-datetimepicker").each(function () {
			var $this = $(this);
			if ($this.data("fcf7-init")) {
				return;
			}
			$this.data("fcf7-init", 1);

			var configStr = $this.attr("data-config");
			var config = {};

			if (configStr) {
				try {
					config = JSON.parse(configStr);
				} catch (e) {
					config = {};
				}
			}

			var dateFormat = allowedFormats.indexOf(config.date_format) !== -1 ? config.date_format : "d-m-Y";
			var timeFormat = config.time_format || "H:i";
			var mode = config.mode || "single";

			var minDate = formatDateString(config.min_date || "", dateFormat);
			var maxDate = formatDateString(config.max_date || "", dateFormat);
			var defaultDate = formatDateString(config.default_date || "", dateFormat);

			flatpickr($this[0], {
				enableTime: true,
				mode: mode,
				dateFormat: dateFormat + " " + timeFormat,
				time_24hr: timeFormat.indexOf("H") !== -1,
				minuteIncrement: parseInt(config.interval, 10) || 1,
				minDate: minDate ? minDate + " " + (config.default_time || "00:00") : undefined,
				maxDate: maxDate ? maxDate + " " + (config.default_time || "23:59") : undefined,
				defaultDate: defaultDate ? defaultDate + " " + (config.default_time || "00:00") : undefined,
			});
		});
	}

	window.fcf7DateTimePicker = { init: init };

	document.addEventListener("DOMContentLoaded", function () {
		init(document);
	});

	document.addEventListener("fcf7:repeater-row-added", function (e) {
		init(e.detail.row);
	});
})(jQuery);
