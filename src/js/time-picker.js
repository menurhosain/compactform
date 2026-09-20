(function () {
	// CompactForm - Time Picker (flatpickr, time only)
	function init(scope) {
		(scope || document).querySelectorAll(".fcf7-field-picker-wrapper input.fcf7-timepicker").forEach(function (el) {
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

			var timeFormat = config.time_format || "H:i";

			flatpickr(el, {
				enableTime: true,
				noCalendar: true,
				dateFormat: timeFormat, // e.g. "H:i", "H:i:s" or "h:i K"
				time_24hr: timeFormat.indexOf("H") !== -1,
				minuteIncrement: parseInt(config.interval, 10) || 1,
				defaultDate: config.default_time ? config.default_time : null,
				minTime: config.min_time ? config.min_time : null,
				maxTime: config.max_time ? config.max_time : null,
			});
		});
	}

	window.fcf7TimePicker = { init: init };

	document.addEventListener("DOMContentLoaded", function () {
		init(document);
	});

	document.addEventListener("fcf7:repeater-row-added", function (e) {
		init(e.detail.row);
	});
})();
