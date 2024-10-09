//public variable for site translation loaded table detection
var sitesTranslationLoadedTblLists = [];

$(function(){
    var $body           = $("body"),
        mst_id          = 0,
        mstt_id         = 0,
        transZoneKey    = "meliscms_tool_sites_site_translations";

        /**
         * This will trigger the language filter
         */
        $body.on("change", ".transLangFilter", function(){
            var tableId = $(this).parents().eq(6).find('table').attr('id');
            $("#"+tableId).DataTable().ajax.reload();
        });

        /**
         * This will refresh the table
         */
        $body.on("click", ".mt-tr-refresh", function(){
            var siteId = activeTabId.split("_")[0];
            melisHelper.zoneReload(siteId+'_id_meliscms_tool_sites_site_translations', transZoneKey, {siteId:siteId}, function(){
                $("#"+siteId+'_id_meliscms_tool_sites_site_translations').addClass("active");
            });
        });

        /**
         * This will edit the translation
         */
        $body.on("click", ".btnEditSiteTranslation", function(){
            var zoneId      = "id_meliscms_tool_sites_site_translations_modal_edit",
                melisKey    = "meliscms_tool_sites_site_translations_modal_edit",
                modalUrl    = "/melis/MelisCms/SitesTranslation/renderToolSitesSiteTranslationModal";

            var $this       = $(this),
                langId      = $this.closest("tr").attr('data-lang-id'),
                siteId      = $this.closest("tr").attr('data-site-id'),
                key         = $this.closest("tr").find('td:nth-child(2)').text(),
                mstt_id     = $this.closest("tr").attr('data-mstt-id'),
                mst_id      = $this.closest("tr").attr('data-mst-id');

                melisHelper.createModal(zoneId, melisKey, true, {translationKey:key, langId:langId, siteId:siteId},  modalUrl);
        });

        /**
         * This will delete the translation
         */
        $body.on("click", "#btnDeleteSiteTranslation", function(e){
            var siteId  = activeTabId.split("_")[0],
                t_id    = $(this).closest("tr").attr('data-mst-id'),
                tt_id   = $(this).closest("tr").attr('data-mstt-id'),
                id      = $(this).closest("tr").attr('data-site-id'),
                obj     = {};

                obj.mst_id = t_id;
                obj.siteId = id;

                if(t_id != 0 && t_id != "") {
                    melisCoreTool.confirm(
                        translations.tr_meliscore_common_yes,
                        translations.tr_meliscore_common_no,
                        translations.tr_melis_site_translation_name,
                        translations.tr_melis_site_translation_delete_confirm,
                        function() {
                            $.ajax({
                                type: 'POST',
                                url: '/melis/MelisCms/SitesTranslation/deleteTranslation',
                                data: $.param(obj)
                            }).done(function (data) {
                                //process the returned data
                                if (data.success) {//success
                                    melisHelper.melisOkNotification(translations.tr_meliscore_common_success, translations.tr_melis_site_translation_delete_success);
                                    melisHelper.zoneReload(siteId+'_id_meliscms_tool_sites_site_translations', transZoneKey, {siteId:siteId}, function(){
                                        $("#"+siteId+'_id_meliscms_tool_sites_site_translations').addClass("active");
                                    });
                                }
                            });
                        });
                }
                e.preventDefault();
        });

        /**
         * This will save the translation
         */
        $body.on("click", ".btnSaveSiteTranslation", function(e){
            var siteId = activeTabId.split("_")[0],
                //form = $("#site-translation-form"),
                form = $("form[name='sitestranslationform']").serializeArray();

                $.ajax({
                    type        : 'POST',
                    url         : '/melis/MelisCms/SitesTranslation/saveTranslation',
                    data		   : $.param(form)
                }).done(function(data) {
                    //process the returned data
                    if(data.success){//success
                        if(mst_id == 0) {
                            melisHelper.melisOkNotification(translations.tr_meliscore_common_success, translations.tr_melis_site_translation_inserting_success);
                        }else{
                            melisHelper.melisOkNotification(translations.tr_meliscore_common_success, translations.tr_melis_site_translation_update_success);
                        }
                        //remove highlighted label
                        // melisCoreTool.highlightErrors(1, null, "site-translation-form");
                        $("#modal-site-translation").modal("hide");
                        melisHelper.zoneReload(siteId+'_id_meliscms_tool_sites_site_translations', transZoneKey,{siteId:siteId}, function(){
                            mst_id = 0;
                            mstt_id = 0;
                            $("#"+siteId+'_id_meliscms_tool_sites_site_translations').addClass("active");
                        });
                    }else{//failed
                        //show errors
                        melisHelper.melisKoNotification(translations.tr_melis_site_translations, translations.tr_melis_site_translation_save_failed, data.errors);
                        //highlight errors
                        $.each(data.langErrorIds, function(i, langId){
                            melisCoreTool.highlightErrors(0, data.errors, langId+"_site-translation-form");
                        });
                    }
                });
                e.preventDefault();
        });

        /**
         * adjust table column to make it responsive on mobile
         * when the user click on sites translation tab
         */
        $body.on("shown.bs.tab", ".sites-tool-tabs a[data-bs-toggle='tab']", function(){
            if ($(window).width() <= 768) {
                var target = $(this).attr("href");
                target = target.replace("#", "");
                var cleanString = target.replace(/\d+/g, '');
                if (target != "") {
                    var transId = target.split("_");
                    if (cleanString == "_id_meliscms_tool_sites_site_translations") {
                        $("#" + transId[0] + "_tableMelisSiteTranslation").DataTable().columns.adjust().responsive.recalc();
                    }
                }
            }
        });
});

/**
 * Callback for site translation table
 *
 */
window.siteTransTableCallBack = function(data, tblSetting){
    /**
     * get the current site id
     */
    var siteId = activeTabId.split("_")[0];
    /**
     * Remove the delete button if the
     * translation is came from the file
     */
    $("#"+siteId+"_tableMelisSiteTranslation tbody tr[data-mst-id='0']").find("#btnDeleteSiteTranslation").remove();
};

/**
 * This will prepare to add the additional
 * data of the translation
 * @param data
 */
window.initAdditionalTransParam = function(data){
    var siteId = activeTabId.split("_")[0];
    data.site_translation_language_name = $('#'+siteId+'_siteTranslationLanguageName').val();
    data.siteId = siteId;
};

/* window.siteTranslationEditor = function() {
    // var $textarea   = $("form[name='sitestranslationform'] textarea"),
    //     selector    = "#"+$textarea.attr("id");

    //     console.log("selector: ", selector);

    //     melisTinyMCE.createTinyMCE("tool", selector, {
    //         toolbar : 'undo redo | styleselect | bold italic | alignleft aligncenter alignright | link'
    //     });
        
    //     console.log("siteTranslationEditor window loaded");

        $(".tiny-mce-init").each(function(i, v){
            var selector = $(this);

                console.log("selector: ", selector);
            
                // Initialize TinyMCE editor
                melisTinyMCE.createTinyMCE("tool", selector, {height: 200, relative_urls: false,  remove_script_host: false, convert_urls : false});
        });
}; */