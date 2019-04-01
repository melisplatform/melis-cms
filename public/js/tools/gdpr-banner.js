$(function () {
    var $body = $("body");

    /**
     * Site selector
     */
    $body.on("change", "#id_mcgdprbanner_site_id", function () {
        /** Removing red color from highlighted fields */
        melisCoreTool.highlightErrors(1, null, "id_melis_cms_gdpr_banner_header");

        melisHelper.zoneReload("id_melis_cms_gdpr_banner_details", "melis_cms_gdpr_banner_details", {siteId: this.value});
    });

    /**
     * Saves banner contents
     */
    $body.on("click", ".cms-gdpr-save", function () {
        if ($body.find(this).attr('disabled') === undefined) {
            melisCoreTool.pending(".cms-gdpr-save");
            var data = {};
            var languageIds = [];

            /** Get site */
            var site = $body.find("#cms_gdpr_banner_site_filter_form");
            if (site.length > 0) {
                data["filters"] = {"siteId": site.serializeArray()};
            }

            /** Get all the language options offered */
            $body.find(".mcms-gdpr-banner-language").each(function (i, language) {
                languageIds.push($body.find(language).data("langId"));
            });

            /** Get all the form data for the languages */
            var content = [];
            var bannerData = {};
            for (var i = 0; i < languageIds.length; i++) {
                content = $body.find("#id-cms-gdpr-banner-content-form-" + languageIds[i]);
                if (content.length > 0) {
                    bannerData[languageIds[i]] = content.serializeArray();
                }
            }
            data['bannerContent'] = bannerData;

            $.ajax({
                type: 'POST',
                url: '/melis/MelisCms/GdprBanner/saveBanner',
                data: data,
                dataType: 'json',
                encode: true
            }).success(function (data) {
                if (data.success) {
                    melisHelper.melisOkNotification(data.textTitle, data.textMessage);
                    $body.find("#id_mcgdprbanner_site_id").trigger("change");
                }
                else {
                    melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
                    //highlight errors
                    melisCoreTool.highlightErrors(0, data.errors, "id_melis_cms_gdpr_banner");
                }

                // update flash messenger component
                melisCore.flashMessenger();
                melisCoreTool.done(".cms-gdpr-save");
            }).error(function () {
                melisCoreTool.done(".cms-gdpr-save");
            });
        }
    });
});
