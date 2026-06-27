(function () {
	"use strict";

	document.addEventListener("submit", function (event) {
		var form = event.target;

		if (!form || !form.matches("[data-gps-form]")) {
			return;
		}

		var button = form.querySelector("button[type='submit']");
		var status = form.querySelector("[data-gps-status]");

		if (button) {
			button.disabled = true;
			button.textContent = "Enregistrement...";
		}

		if (status) {
			status.textContent = "Enregistrement du cours...";
		}

		// Do not block the submit on GPS. Shared hosting reliability wins here;
		// coordinates can be added later without risking the core time tracking flow.
	});
}());
