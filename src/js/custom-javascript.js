// Add your JS customizations here
(function ($) {
	var $window = $(window);
	var $document = $(document);
	var $body = $("body");
	var $html = $("html");

	function bhCaptcha() {
		var form = $("#bullhorn-apply-form");
		var captcha = $("#bullhorn-apply-form__grecaptcha");
		if (!form.length || !captcha.length) {
			return false;
		}
		var settings = captcha.data();
		var widgetId = window.grecaptcha.render(captcha[0], settings);

		form.on("reset error", function () {
			window.grecaptcha.reset(widgetId);
		});

		form.on("submit", function (event) {
			event.preventDefault();
			var button = $(this).find(".bullhorn-apply-form__submit");
			button.prop("disabled", true);
			window.grecaptcha.ready(function () {
				window.grecaptcha
					.execute(widgetId, {
						action: "apply",
					})
					.then(function (token) {
						form.prepend(
							'<input type="hidden" name="token" value="' + token + '">'
						);
						form.prepend('<input type="hidden" name="action" value="apply">');
						form.off("submit").trigger("submit");
					});
			});
		});
	}
	$window.on("load", function () {
		bhCaptcha();
	});
})(jQuery);
