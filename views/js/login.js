window.paypal.loginUtils = {
	translations: {
		"zh-cn": {lang: "Chinese (People's Republic of China)", content: "通过贝宝登录"},
		"zh-hk": {lang: "Chinese (Hong Kong)", content: "使用 PayPal 登入"},
		"zh-tw": {lang: "Chinese (Taiwan)", content: "使用 PayPal 登入"},
		"zh-xc": {lang: "Chinese (US)", content: "通过PayPal登录"},
		"da-dk": {lang: "Danish", content: "Log på med PayPal"},
		"nl-nl": {lang: "Dutch", content: "Inloggen met PayPal"},
		"en-gb": {lang: "English (Great Britain)", content: "Log In with PayPal"},
		"en-au": {lang: "English (Australia)", content: "Log In with PayPal"},
		"en-us": {lang: "English (US)", content: "Log In with PayPal"},
		"fr-fr": {lang: "French", content: "Connexion avec PayPal"},
		"fr-ca": {lang: "French (Canada)", content: "Connexion avec PayPal"},
		"fr-xc": {lang: "French (international)", content: "Connexion avec PayPal"},
		"de-de": {lang: "German", content: "Login mit PayPal"},
		"he-il": {lang: "Hebrew (Israel)", content: "היכנס עם PayPal&#8207;"},
		"id-id": {lang: "Indonesian", content: "Log In dengan PayPal"},
		"it-il": {lang: "Italian", content: "L'accesso a PayPal"},
		"ja-jp": {lang: "Japanese", content: "PayPalでログイン"},
		"no-no": {lang: "Norwegian", content: "Logg på med PayPal"},
		"pl-pl": {lang: "Polish", content: "Zaloguj się PayPal"},
		"pt-pt": {lang: "Portuguese", content: "Acesse o PayPal"},
		"pt-br": {lang: "Portuguese (Brazil)", content: "Acesse o PayPal"},
		"ru-ru": {lang: "Russian", content: "Войти через PayPal"},
		"es-es": {lang: "Spanish", content: "Identificarse con PayPal"},
		"es-xc": {lang: "Spanish (Mexico)", content: "Iniciar sesión con PayPal"},
		"sv-se": {lang: "Swedish", content: "Logga in med PayPal"},
		"th-th": {lang: "Thai", content: "เข้าสู่ระบบด้วย PayPal"},
		"tr-tr": {lang: "Turkish", content: "PayPal ile giriş yapın"}
	},
	createRandomNumber: function () {
		return Math.random() * 1e8 >> 0;
	},
	getTranslation: function (params) {
		var obj = params || {}, text = obj.text, locale = obj.locale, PAT = this.translations;
		if (!!text) {
			return text;
		} else if (!locale) {
			locale = navigator.language || navigator.userLanguage;
		}
		if (locale.length === 2) {
			locale = locale + "-" + locale;
		}
		locale = locale.replace(/_/, "-").toLowerCase();
		if (PAT[locale] && PAT[locale].content) {
			return PAT[locale].content;
		} else {
			return PAT["en-us"].content;
		}
	},
	getThisLoadedElement: function (tagName) {
		var el = tagName ? document.getElementsByTagName(tagName) : document.getElementsByTagName("script");
		return el[el.length - 1];
	},
	getCenteredPosition: function (elWidth, elHeight) {
		var viewportHeight = window.innerHeight || document.documentElement.clientHeight, viewportWidth = window.innerWidth || document.documentElement.clientWidth, viewportLeft = window.screenX || window.screenLeft, viewportTop = window.screenY || window.screenTop;
		return {left: viewportLeft + ~~(viewportWidth / 2) - ~~(elWidth / 2), top: viewportTop + ~~(viewportHeight / 2) - ~~(elHeight / 2)};
	},
	createQueryString: function (obj) {
		var pairs = [], i = 0, value = "";
		for (i in obj) {
			if (obj.hasOwnProperty(i)) {
				value = (typeof obj[i] === "function") ? obj[i]() : obj[i];
				pairs.push(encodeURIComponent(i) + "=" + encodeURIComponent(value));
			}
		}
		return pairs.join("&");
	},
	applyStyles: function () {
		this.styleFromString('/*!reset via github.com/premasagar/cleanslate*/\n.LIwPP,\n.LIwPP b,\n.LIwPP img,\n.LIwPP svg {\n  -webkit-box-sizing: content-box !important;\n  -moz-box-sizing: content-box !important;\n  box-sizing: content-box !important;\n  background-attachment: scroll!important;\n  background-color: transparent!important;\n  background-image: none!important;\n  background-position: 0 0!important;\n  background-repeat: repeat!important;\n  border-color: black!important;\n  border-color: currentColor!important;\n  border-radius: 0!important;\n  border-style: none!important;\n  border-width: medium!important;\n  direction: inherit!important;\n  display: inline!important;\n  float: none!important;\n  font-size: inherit!important;\n  height: auto!important;\n  letter-spacing: normal!important;\n  line-height: inherit!important;\n  margin: 0!important;\n  max-height: none!important;\n  max-width: none!important;\n  min-height: 0!important;\n  min-width: 0!important;\n  opacity: 1!important;\n  outline: invert none medium!important;\n  overflow: visible!important;\n  padding: 0!important;\n  position: static!important;\n  text-align: inherit!important;\n  text-decoration: inherit!important;\n  text-indent: 0!important;\n  text-shadow: none!important;\n  text-transform: none!important;\n  unicode-bidi: normal!important;\n  vertical-align: baseline!important;\n  visibility: inherit!important;\n  white-space: normal!important;\n  width: auto!important;\n  word-spacing: normal!important;\n}\n.LIwPP *[dir=rtl] {\n  direction: rtl!important;\n}\n.LIwPP {\n  direction: ltr!important;\n  font-style: normal!important;\n  text-align: left!important;\n  text-decoration: none!important;\n}\n.LIwPP {\n  background-color: #bfbfbf !important;\n  background-image: -webkit-linear-gradient(#e0e0e0 20%, #bfbfbf) !important;\n  background-image: linear-gradient(#e0e0e0 20%, #bfbfbf) !important;\n  background-repeat: no-repeat !important;\n  border: 1px solid #bbb !important;\n  border-color: #cbcbcb #b2b2b2 #8b8b8b !important;\n  -webkit-box-shadow: inset 0 1px #ececec !important;\n  box-shadow: inset 0 1px #ececec !important;\n  -webkit-box-sizing: border-box !important;\n  -moz-box-sizing: border-box !important;\n  box-sizing: border-box !important;\n  border-radius: 5px !important;\n  font-size: 16px !important;\n  height: 2em !important;\n  margin: 0 !important;\n  overflow: hidden !important;\n  padding: 0 !important;\n  position: relative !important;\n  text-align: center !important;\n  width: auto !important;\n  white-space: nowrap !important;\n}\n.LIwPP,\n.LIwPP b,\n.LIwPP i {\n  cursor: pointer !important;\n  display: inline-block !important;\n  -webkit-user-select: none !important;\n  -moz-user-select: none !important;\n  -ms-user-select: none !important;\n  user-select: none !important;\n}\n.LIwPP b {\n  border-left: 1px solid #b2b2b2 !important;\n  -webkit-box-shadow: inset 1px 0 rgba(255,255,255,.5) !important;\n  box-shadow: inset 1px 0 rgba(255,255,255,.5) !important;\n  color: #333333 !important;\n  font: normal 700 0.6875em/1.5 "Helvetica Neue", Arial, sans-serif !important;\n  height: 2.25em !important;\n  padding: .625em .66667em 0 !important;\n  text-shadow: 0 1px 0 #eee !important;\n  vertical-align: baseline !important;\n}\n.LIwPP .PPTM,\n.LIwPP i {\n  height: 100% !important;\n  margin: 0 .4em 0 .5em !important;\n  vertical-align: middle !important;\n  width: 1em !important;\n}\n.LIwPP i {\n  background: url(\'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAhCAMAAADnC9tbAAAA81BMVEX///////+ozeUAecEARXyozeUARXwAecH///8AecGozeX///8ARXz///+ozeUAecEARXzg7fYAbrPx9/v///8AecFGntKozeUAZqgARXwARXy11OmFv+EAecEAaav///8AecGozeUARXwAW5rZ6fTg7fb5+/3V5/LA2+z///8ARXyozeW92euy0+j5+/0AecEAc7kAbLAASIDR5fH///+ozeUAY6MAT4oARXwAecEARXz///+ozeX///8ARXwAecGozeUARXyozeX///8AecH///8AecEARXyozeWozeX///8ARXwAecGozeUAecH///8ARXwU3q5BAAAATXRSTlMAERERESIiIiIzMzMzRERERERERFVVVVVVVWZmZmZmZnd3d3d3d3d3d4iIiIiIiIiIiIiImZmZmZmqqqqqu7u7u8zMzMzd3d3d7u7u7nVO37gAAAEZSURBVHheTY5rX4IwGEf/aFhWoJV2sRugXcDu2QUMDA0QbdPv/2naw9B13jzb2X5nA1CbLyVxBwWd5RoPhLdU1EjEQjiAQ+K9DWysTmh2f4CmmBnKG89cJrLmySftI1+ISCXnx9wH1D7Y/+UN7JLIoih4uRhxfi5bsTUapZxzvw4goA/y1LKsRhVERq/zNkpk84lXlXC8j26aQoG6qD3iP5uHZwaxg5KtRcnM1UBcLtYMQQzoMJwUZo9EIkQLMEi82oBGC62cvSnQEjMB4JK4Y3KRuG7RGHznQKgemZyyN2ChvnHPcl3Gw9B9uOpPWb4tE8MvRkztCoChENdsfGSaOgpmQtwwE2uo1mcVJYyD3m0+hgJ6zpitxB/jvlwEGmtLjgAAAABJRU5ErkJggg==\') no-repeat !important;\n  height: 1em !important;\n}\n.LIwPP img.PPTM {\n  height: auto !important;\n}\n.LIwPP:hover,\n.LIwPP:active {\n  background-color: #a5a5a5 !important;\n  background-image: -webkit-linear-gradient(#e0e0e0 20%, #a5a5a5) !important;\n  background-image: linear-gradient(#e0e0e0 20%, #a5a5a5) !important;\n  background-repeat: no-repeat !important;\n}\n.LIwPP:active {\n  border-color: #8c8c8c #878787 #808080 !important;\n  -webkit-box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2) !important;\n  box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2) !important;\n  outline: 0 !important;\n}\n.LIwPP:hover b {\n  -webkit-box-shadow: inset 1px 0 rgba(255,255,255,.3) !important;\n  box-shadow: inset 1px 0 rgba(255,255,255,.3) !important;\n}\n.PPBlue {\n  background-color: #0079c1 !important;\n  background-image: -webkit-linear-gradient(#00a1ff 20%, #0079c1) !important;\n  background-image: linear-gradient(#00a1ff 20%, #0079c1) !important;\n  background-repeat: no-repeat !important;\n  border-color: #0079c1 #00588b #004b77 !important;\n  -webkit-box-shadow: inset 0 1px #4dbeff !important;\n  box-shadow: inset 0 1px #4dbeff !important;\n}\n.PPBlue b {\n  -webkit-box-shadow: inset 1px 0 rgba(77, 190, 255, .5) !important;\n  box-shadow: inset 1px 0 rgba(77, 190, 255, .5) !important;\n  border-left-color: #00588b !important;\n  color: #f9fcff !important;\n  -webkit-font-smoothing: antialiased !important;\n  font-smoothing: antialiased !important;\n  text-shadow: 0 -1px 0 #00629c !important;\n}\n.PPBlue i {\n  background-position: 0 100% !important;\n}\n.PPBlue .PPTM-btm {\n  fill: #a8cde5 !important;\n}\n.PPBlue .PPTM-top {\n  fill: #fff !important;\n}\n.PPBlue:hover,\n.PPBlue:active {\n  background-color: #005282 !important;\n  background-image: -webkit-linear-gradient(#0083cf 20%, #005282) !important;\n  background-image: linear-gradient(#0083cf 20%, #005282) !important;\n  background-repeat: no-repeat !important;\n  border-color: #006699 #004466 #003355 !important;\n}\n.PPBlue:hover b,\n.PPBlue:active b {\n  text-shadow: 0 -1px 0 #004466 !important;\n}\n');
	},
	styleFromString: function (css) {
		var doc = document, head = doc.head || doc.getElementsByTagName("head")[0], rand = this.createRandomNumber(), id = "paypal-css-" + rand, style = doc.createElement("style");
		style.type = "text/css";
		if (style.styleSheet) {
			style.styleSheet.cssText = css;
		} else {
			style.appendChild(doc.createTextNode(css));
		}
		head.appendChild(style);
	},
	loadAsync: function (url, callback) {
		var doc = document, head = doc.head || doc.getElementsByTagName("head")[0], rand = this.createRandomNumber(), fullUrl = url + "?" + "b=" + rand, id = "loader_" + rand, isJS = (/\.js$/.test(url)) ? true : false, newEl = (isJS) ? doc.createElement("script") : doc.createElement("link");
		newEl.id = id;
		if (isJS) {
			newEl.src = fullUrl;
			head.appendChild(newEl);
			newEl.onload = newEl.onreadystatechange = function () {
				var rs = this.readyState;
				if ((!rs || rs === "loaded" || rs === "complete")) {
					newEl.onload = newEl.onreadystatechange = null;
					head.removeChild(newEl);
					callback && typeof callback === "function" && callback();
				}
			};
		} else {
			newEl.rel = "stylesheet";
			newEl.type = "text/css";
			newEl.href = fullUrl;
			head.appendChild(newEl);
			callback && typeof callback === "function" && callback();
		}
	}
}

window.paypal.login = {
	build: 16,
	render: function (params) {
		if (!params) {
			throw new Error("loginUtils.login.render requires a returnUrl");
		}
		var popupWidth = 400, popupHeight = 560, authEndPoint = (params.authend) ? "https://www.sandbox.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize" : "https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize", appId = params.appid || null, returnUrl = params.returnurl || null, scopes = params.scopes || "basic", container = (params.containerid) ? document.getElementById(params.containerid) : (params.containerelement) ? params.containerelement : thisScriptEl, translationText = window.paypal.loginUtils.getTranslation(params), theme = (params.theme && params.theme.toLowerCase() === "neutral") ? "" : " PPBlue", query = {
			client_id: appId,
			response_type: "code",
			scope: scopes,
			redirect_uri: encodeURIComponent(returnUrl),
			nonce: window.paypal.loginUtils.createRandomNumber(),
			newUI: 'Y'
		}, popupUrl = authEndPoint + "?" + window.paypal.loginUtils.createQueryString(query), button = document.createElement("button"), checkSVG = (!!document.createElementNS && !!document.createElementNS("http://www.w3.org/2000/svg", "svg").createSVGRect), logo = (checkSVG) ? ('<svg class="PPTM" xmlns="http://www.w3.org/2000/svg" version="1.1" width="16px" height="17px" viewBox="0 0 16 17">' + '<path class="PPTM-btm" fill="#0079c1" d="m15.603 3.917c-0.264-0.505-0.651-0.917-1.155-1.231-0.025-0.016-0.055-0.029-0.081-0.044 0.004 0.007 0.009 0.014 0.013 0.021 0.265 0.506 0.396 1.135 0.396 1.891 0 1.715-0.712 3.097-2.138 4.148-1.425 1.052-3.418 1.574-5.979 1.574h-0.597c-0.45 0-0.9 0.359-1.001 0.798l-0.719 3.106c-0.101 0.438-0.552 0.797-1.002 0.797h-1.404l-0.105 0.457c-0.101 0.438 0.184 0.798 0.633 0.798h2.1c0.45 0 0.9-0.359 1.001-0.798l0.718-3.106c0.101-0.438 0.551-0.797 1.002-0.797h0.597c2.562 0 4.554-0.522 5.979-1.574 1.426-1.052 2.139-2.434 2.139-4.149 0-0.755-0.132-1.385-0.397-1.891z"/>' + '<path class="PPTM-top" fill="#00457c" d="m9.27 6.283c-0.63 0.46-1.511 0.691-2.641 0.691h-0.521c-0.45 0-0.736-0.359-0.635-0.797l0.628-2.72c0.101-0.438 0.552-0.797 1.002-0.797h0.686c0.802 0 1.408 0.136 1.814 0.409 0.409 0.268 0.611 0.683 0.611 1.244 0 0.852-0.315 1.507-0.944 1.97zm3.369-5.42c-0.913-0.566-2.16-0.863-4.288-0.863h-4.372c-0.449 0-0.9 0.359-1.001 0.797l-2.957 12.813c-0.101 0.439 0.185 0.798 0.634 0.798h2.099c0.45 0 0.901-0.358 1.003-0.797l0.717-3.105c0.101-0.438 0.552-0.797 1.001-0.797h0.598c2.562 0 4.554-0.524 5.979-1.575 1.427-1.051 2.139-2.433 2.139-4.148-0.001-1.365-0.439-2.425-1.552-3.123z"/>' + '</svg>') : '<i></i>', content = '<b>' + translationText + '</b>';
		button.id = "LIwPP" + query.nonce;
		button.className = "LIwPP" + theme;
		button.innerHTML = logo + content;
		function openPopup() {
			var centered = window.paypal.loginUtils.getCenteredPosition(popupWidth, popupHeight), popup = window.open(popupUrl, "__ppax__", "height=" + popupHeight + ", width=" + popupWidth + ", top=" + centered.top + ", left=" + centered.left + ", status=no, scrollbars=no, toolbar=no, menubar=no, resizable=yes");
			window.focus && popup.focus();
		}

		if (button.addEventListener) {
			button.addEventListener("click", openPopup, false);
		} else if (button.attachEvent) {
			button.attachEvent("onclick", openPopup);
		} else {
			button.onclick = openPopup;
		}
		container && container.appendChild(button);
		return button;
	}
}
