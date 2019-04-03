/**
 * Set the "GDPR cookie" for the first site visit,
 * to prevent the GDPR banner from showing every site visit
 */
let MelisCmsGdprBanner = (function () {
    /**
     * To make a "persistent cookie" (a cookie that "never expires"),
     * we need to set a date/time in a distant future (one that possibly exceeds the user's
     * machine life).
     *
     * src: https://stackoverflow.com/a/22479460/7870472
     */
    const MAX_COOKIE_AGE = 2147483647000;
    const BANNER_COOKIE_NAME = "melis-cms-gdpr-banner-cookie";

    /**
     * Usage : setCookie('user', 'John', {secure: true, 'max-age': 3600});
     * @param name
     * @param value
     * @param options
     */
    function setCookie(name, value, options = {}) {
        options = {
            path: '/',
            expires: new Date(MAX_COOKIE_AGE).toUTCString()
        };

        let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

        for (let optionKey in options) {
            updatedCookie += "; " + optionKey;
            let optionValue = options[optionKey];
            if (optionValue !== true) {
                updatedCookie += "=" + optionValue;
            }
        }

        document.cookie = updatedCookie;
    }

    function getCookie(name) {
        let matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace("/([\.$?*|{}\(\)\[\]\\\/\+^])/g", '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    }

    function deleteCookie(name) {
        setCookie(name, "", {
            'expires': 0
        })
    }

    return {
        setCookie: setCookie,
        getCookie: getCookie,
        deleteCookie: deleteCookie,
        getCookieName: BANNER_COOKIE_NAME,
    };
})();

$(function () {
    let $body = $("body");

    /** Agree to the site's cookie policy */
    $body.on('click', '.gdpr-banner-agree', function () {
        MelisCmsGdprBanner.deleteCookie(MelisCmsGdprBanner.getCookieName);

        let banner = $body.find(this).data("gdprBannerPluginId");
        if (banner) {
            banner = $body.find('#' + banner);
            if (banner.length > 0) {
                banner.slideToggle('fast');
            }
        }
    });
});

