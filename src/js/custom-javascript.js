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

	function bhLangSwitcherJobs() {
		var pathname = window.location.pathname;
		console.log("pathname", pathname);

		if (pathname.startsWith("/ar/job/")) {
			$("a.plsfe-item").attr("href", function (i, href) {
				pathname = pathname.replace("/ar/job/", "");
				return href + pathname;
			});
		}
		if (pathname.startsWith("/job/")) {
			$("a.plsfe-item").attr("href", function (i, href) {
				pathname = pathname.replace("/job/", "");
				return href + pathname;
			});
		}
	}
	console.log('WORKS');

	$window.on("load", function () {
		bhCaptcha();
		bhLangSwitcherJobs();
	});
})(jQuery);
