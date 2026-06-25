(function () {
	"use strict";

	function setStatus(form, message) {
		var node = form.querySelector("[data-gps-status]");
		if (node) {
			node.textContent = message;
		}
	}

	function submitAfterGps(form) {
		if (form.dataset.gpsSubmitted === "1") {
			return true;
		}

		form.dataset.gpsSubmitted = "1";

		if (!navigator.geolocation) {
			setStatus(form, "GPS non disponible sur ce navigateur.");
			form.submit();
			return false;
		}

		setStatus(form, "Demande de position GPS...");

		navigator.geolocation.getCurrentPosition(
			function (position) {
				var lat = position.coords.latitude.toFixed(7);
				var lng = position.coords.longitude.toFixed(7);
				var mode = form.dataset.gpsMode || "";
				var latField = form.querySelector("[data-gps-lat]");
				var lngField = form.querySelector("[data-gps-lng]");
				var startLat = form.querySelector("[data-gps-start-lat]");
				var startLng = form.querySelector("[data-gps-start-lng]");
				var endLat = form.querySelector("[data-gps-end-lat]");
				var endLng = form.querySelector("[data-gps-end-lng]");

				if (latField) latField.value = lat;
				if (lngField) lngField.value = lng;

				if (mode === "manual") {
					if (startLat) startLat.value = lat;
					if (startLng) startLng.value = lng;
					if (endLat) endLat.value = lat;
					if (endLng) endLng.value = lng;
				}

				setStatus(form, "Position GPS ajoutée.");
				form.submit();
			},
			function () {
				setStatus(form, "GPS refusé ou expiré. Le cours sera enregistré sans position.");
				form.submit();
			},
			{ enableHighAccuracy: true, timeout: 2500, maximumAge: 60000 }
		);

		return false;
	}

	document.addEventListener("submit", function (event) {
		var form = event.target;
		if (!form || !form.matches("[data-gps-form]")) {
			return;
		}

		event.preventDefault();
		submitAfterGps(form);
	});
}());
