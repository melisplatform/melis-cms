var formData = {},
    currentStepForm = "",
    configFormPath = "";

$(function() {
    $body = $("body");

    /**
     * Get all input values into one array on clicking save button except for the site translation inputs
     */
    $body.on("click","#btn-save-meliscms-tool-sites", function() {
        var currentTabId = activeTabId.split("_")[0],
            dataString = $("#"+currentTabId+"_id_meliscms_tool_sites_edit_site form").serializeArray(),
            // serialize the new array and send it to server
            newEnabledModule = [];

            $.each(dataString, function( key, value ) {
                str1 = value.name;
                str2 = "moduleLoad";
                if(str1.indexOf(str2) != -1){
                    newEnabledModule.push(str1.replace('moduleLoad',''));
                }
            });

            var currentEnabledModule = $("#"+currentTabId+"_currentEnabledModule").val(),
                sitesUsingModules = $("#"+currentTabId+"_sitesUsingModules").val();

                currentEnabledModule = jQuery.parseJSON(currentEnabledModule);
                sitesUsingModules = jQuery.parseJSON(sitesUsingModules);

                var sitesUsingModulesStr = "";

                $.each(sitesUsingModules,function (key, val) {
                    sitesUsingModulesStr += "<br>- "+ val;
                });

                var moduleDiff = arrayDiff(currentEnabledModule,newEnabledModule),
                    siteModuleName = $("#"+currentTabId+"_siteModuleName").val(),
                    isAdmin = $("#not-admin-notice").length < 1 ? true : false;

                    if ( moduleDiff.length > 0 && isAdmin ) {
                        melisCoreTool.confirm(
                            translations.tr_meliscms_common_save,
                            translations.tr_meliscms_tool_sites_cancel,
                            translations.tr_meliscms_tool_site_module_load_update_title,
                            translations.tr_meliscms_tool_site_module_load_update_confirm.replace(/%s/g, sitesUsingModulesStr),
                            function(){
                                dataString = $.param(dataString);
                                saveSite(dataString, currentTabId, siteModuleName);
                            }
                        );
                    } else {
                        dataString = $.param(dataString);
                        saveSite(dataString, currentTabId, siteModuleName);
                    }
    });

    /**
     * Function to save site
     * @param dataString
     * @param currentTabId
     * @param siteModuleName
     */
    function saveSite(dataString, currentTabId, siteModuleName) {
        $.ajax({
            type        : 'POST',
            url         : '/melis/MelisCms/Sites/saveSite?siteId='+currentTabId,
            data        : dataString,
            dataType    : 'json',
            encode		: true,
            beforeSend  : function() {
                melisCoreTool.pending("#btn-save-meliscms-tool-sites");
            }
        }).done(function(data) {
            if ( data.success === 1 ) {
                // call melisOkNotification
                melisHelper.melisOkNotification(data.textTitle, data.textMessage, '#72af46' );
                // update flash messenger values
                melisCore.flashMessenger();

                melisCoreTool.done("#btn-save-meliscms-tool-sites");

                melisHelper.zoneReload(
                    currentTabId + '_id_meliscms_tool_sites_edit_site',
                    'meliscms_tool_sites_edit_site',
                    {
                        siteId: currentTabId,
                        moduleName: siteModuleName,
                        cpath: 'meliscms_tool_sites_edit_site'
                    }
                );

                //refresh table tool sites
                $("#tableToolSites").DataTable().ajax.reload();

                //refresh site tree view
                $("input[name=left_tree_search]").val('');
                $("#id-mod-menu-dynatree").fancytree("destroy");
                mainTree();
            } else {
                var container = currentTabId + "_id_meliscms_tool_sites_edit_site";
                var errors = prepareErrs(data.errors, container);

                highlightErrs(data.success, data.errors, container);

                // error modal
                melisHelper.melisKoNotification(data.textTitle, data.textMessage, errors);
                melisCoreTool.done("#btn-save-meliscms-tool-sites");
            }

            // update flash messenger values
            melisCore.flashMessenger();
            melisCoreTool.done("#btn-save-meliscms-tool-sites");
        }).fail(function(xhr, textStatus, errorThrown) {
            alert( translations.tr_meliscore_error_message );
        });
    }

    /**
     * This Function gets the difference between two arrays
     * difference in terms of order and value
     * @param array1
     * @param array2
     */
    function arrayDiff(a1,a2) {
        var result = [];
        if(a1 != null) {
            if (a1.length > 0) {
                for (var i = 0; i < a1.length; i++) {

                    if (a2[i] !== a1[i]) {
                        result.push(a1[i]);
                    }

                }
                if (result.length < 1) {
                    for (var i = 0; i < a2.length; i++) {

                        if (a2[i] !== a1[i]) {
                            result.push(a1[i]);
                        }

                    }
                }
            }
        }
        return result;
    }

    function highlightErrs(success, errors, container) {
        if (success === 0 || success === false) {
            $("#" + container + " .form-group label").css("color", "#686868");

            $.each(errors, function (key, error) {
                $("#" + container + " .form-control[name='" + key + "']").parents(".form-group").children("label").css("color", "red");
            });
        } else {
            $("#" + container + " .form-group label").css("color", "#686868");
        }
    }

    function prepareErrs(errors, container) {
        var errs = {};

        $.each(errors, function (key, error) {
            var $input      = $("#" + container + " #" + key),
                lang        = $input.data('lang'),
                label       = $input.siblings('label').text(),
                lastChar    = label.substr(label.length - 1),
                exploded    = key.split('_');

            if ( lang != undefined ) {
                label = $input.closest("div").siblings('label').text().slice(0, -1);
                errs[lang + ' ' + label] = error;
            } else {
                if ( label === "" ) {
                    label = $input.closest("div").siblings('label').text().slice(0, -2);
                    errs[label] = error;
                } else {
                    if ( lastChar === '*' ) {
                        label = $input.siblings('label').text().slice(0, -2);
                    }

                    if ( exploded[1] === 'sdom' ) {
                        if ( lastChar === '*' )
                            label = $input.siblings('label').text().slice(0, -2) + '(' + exploded[0] + ')';
                        else
                            label = $input.siblings('label').text() + '(' + exploded[0] + ')';
                    }
                    errs[label] = error;
                }
            }
        });

        return errs;
    }

    /**
     * This will open a new tab when editing a site
     */
    $body.on("click", ".btnEditSites", function() {
        var $this       = $(this),
            tableId     = $this.closest('tr').attr('id'),
            name        = $this.closest('tr').find("td:nth-child(2)").text(),
            siteLang    = $this.closest('tr').find("td:nth-child(4)").text(),
            siteModule  = $this.closest('tr').find("td:nth-child(3)").text(),
            selId       = $this.closest('tr').attr("id");

            openSiteEditTab(updateSiteTitle(selId, name, siteModule, siteLang), tableId, siteModule);
    });


    /**
     * ======================================================================================
     * =============================== START CREATE SITES ===================================
     * ======================================================================================
     */
    // var formData                    = {},
     var selectedLanguages           = '',
        domainType                  = '',
        createFile                  = true,
        newSite                     = true,
        owlStep                     = null,
        // currentStepForm             = '',
        siteName                    = '',
        siteLabel                   = '',
        selectedDomainValue         = [],
        isUserSelectModuleOption    = false,
        domainSingleOpt             = '';

    /**
     * This will delete the site
     */
    $body.on("click", "#tableToolSites .btnDeleteSite", function(e) {
        var $this   = $(this),
            siteId  = $this.parents("tr").attr("id");

            melisCoreTool.confirm(
                translations.tr_meliscore_common_yes,
                translations.tr_meliscore_common_no,
                translations.tr_meliscms_tool_site_delete_confirm_title,
                translations.tr_meliscms_tool_site_delete_confirm,
                function() {
                    $.ajax({
                        type        : "POST",
                        url         : "/melis/MelisCms/Sites/deleteSite",
                        data		: {siteId: siteId},
                        dataType    : 'json',
                        encode		: true
                    }).done(function(data) {
                        melisCoreTool.pending(".btnDeleteSite");
                        if(data.success) {
                            melisHelper.tabClose(siteId + "_id_meliscms_tool_sites_edit_site");
                            melisHelper.melisOkNotification(data.textTitle, data.textMessage);
                            // melisHelper.zoneReload("id_meliscms_tool_site", "meliscms_tool_site");
                            //refresh site table
                            $("#tableToolSites").DataTable().ajax.reload();
                        }
                        else {
                            melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors, 0);
                        }
                        melisCore.flashMessenger();
                        melisCoreTool.done(".btnDeleteSite");
                    }).fail(function(xhr, textStatus, errorThrown) {
                        alert( translations.tr_meliscore_error_message );
                    });
                });
    });

    var modalUrl = "/melis/MelisCms/Sites/renderToolSitesModalContainer";

    $body.on('click', "#btn-new-meliscms-tool-sites", function () {
        melisCoreTool.pending("#btn-new-meliscms-tool-sites");
        melisHelper.createModal('id_meliscms_tool_sites_modal_container','meliscms_tool_sites_modal_add_content',true,[],modalUrl,function () {
            melisCoreTool.done("#btn-new-meliscms-tool-sites");
            currentStepForm = '';
        });
    });

    /**
     * Initialize owl carousel for step by step
     * process of creating site
     * @type {null}
     */
    window.initializeStep = function() {
        owlStep = $('.sites-steps-owl').owlCarousel({
            items: 1,
            touchDrag: false,
            mouseDrag: false,
            dotsSpeed: 500,
            navSpeed: 500,
            dots: false,
            pagination: false,
            loop: false,
            rewindNav: false,
            autoHeight: true,
            itemsMobile : false,
            itemsTablet: false,
            itemsDesktopSmall : false,
            itemsDesktop : false,
            afterMove: function (elem) {
                var current = this.currentItem,
                    functionToCall    = elem.find(".item").eq(current).attr("data-afterMove");

                //hide the prev button when we are on the first step
                if ( current === 0 ) {
                    hideElement("#btn-prev-step");
                } else {
                    showElement("#btn-prev-step");
                }

                /**
                 * call function one by one
                 */
                functionToCall = $.parseJSON(functionToCall);
                if(Array.isArray(functionToCall)){
                    $.each(functionToCall, function(i, funcName){
                        executeFunction(funcName);
                    });
                }
            },
            beforeMove: function(elem) {
                var current = this.currentItem,
                    step    = elem.find(".item").eq(current).attr("data-step"),
                    functionToCall    = elem.find(".item").eq(current).attr("data-beforeMove");
                //set config form path
                configFormPath = elem.find(".item").eq(current).attr("data-form-path");

                /**
                 * call function one by one
                 */
                functionToCall = $.parseJSON(functionToCall);
                if(Array.isArray(functionToCall)){
                    $.each(functionToCall, function(i, funcName){
                        executeFunction(funcName);
                    });
                }
                updateActiveStep(step);
            },
            afterInit: function(){
                $(".sites-steps-owl .tool-sites_container_fixed_width").find("label").not(":has(input)").removeClass("melis-radio-box");
                /**
                 * tooltip data container to body
                 */
                setTimeout(function(){
                    $(".sites-steps-owl .tool-sites_container_fixed_width").find("i.tip-info").attr("data-container", "body");
                }, 100);

                isUserSelectModuleOption = false;
                domainSingleOpt = "";
                currentStepForm = '';
            }
        });
    };

    $body.on("click", "#btn-next-step", function(e) {
        if ( owlStep !== null ) {
            /**
             * check if form is not empty before
             * proceeding to the next slide
             */
            if ( currentStepForm != "" && currentStepForm != "skip" ) {
                var form = getSerializedForm(currentStepForm);
                if ( isFormEmpty(form, currentStepForm)) {
                    // $("#siteAddAlert").removeClass("hidden");
                } else {
                    $("#siteAddAlert").addClass("hidden");
                    removeFormError(currentStepForm);
                    //owlStep.trigger('owl-next');
                    owlStep.trigger('owl.next');
                    //$(".owl-next").trigger("click");
                }
            } else {
                $("#siteAddAlert").addClass("hidden");
                removeFormError(currentStepForm);
                //owlStep.trigger('owl-next');
                owlStep.trigger('owl.next');
                //$(".owl-next").trigger("click");
            }
        }
    });

    $body.on("click", "#btn-prev-step", function(e) {
        if ( owlStep !== null ) {
            $("#siteAddAlert").addClass("hidden");
            removeFormError(currentStepForm);
            //owlStep.trigger('owl-prev');
            owlStep.trigger('owl.prev');
            //$(".owl-prev").trigger("click");
        }
    });

    /**
     * This will process each step
     * BEFORE proceeding to next slide
     * @param functionToCall
     */
    function executeFunction(functionToCall) {
        if(functionToCall != undefined && functionToCall != "") {
            eval(functionToCall + "()");
        }
    }

    /**
     * Multi lingual step on creating site
     */
    function multiLingualProcess()
    {
        //skip step 1 form
        cmsSiteHelper.setCurrentStepForm('skip');
        // currentStepForm = 'skip';
        /**
         * Hide the step 2 forms
         */
        hideElement(".step2-forms .sites_step2-multi-language");
        hideElement(".step2-forms .sites_step2-single-language");
    }

    /**
     * Language step on creating site
     */
    function languagesProcess()
    {
        //check if multi language
        var isMultiLang = $('#is_multi_language').bootstrapSwitch('status');
        //include the from in step1
        var step1Obj = {};
        step1Obj.isMultiLang = isMultiLang ? true : false;
        step1Obj.data = getSerializedForm("#step2form-is_multi_lingual");
        // formData.multiLang = step1Obj;
        cmsSiteHelper.setSitesStepData(step1Obj, 'multiLang');
        /**
         * determine what should we display
         * depending if multi language or not
         */
        if(isMultiLang){
            showElement(".step2-forms .sites_step2-multi-language");
            hideElement(".step2-forms .sites_step2-single-language");

            // currentStepForm = "#step2form-multi_language";
            cmsSiteHelper.setCurrentStepForm("#step2form-multi_language");
        }else{
            showElement(".step2-forms .sites_step2-single-language");
            hideElement(".step2-forms .sites_step2-multi-language");

            // currentStepForm = "#step2form-single_language";
            cmsSiteHelper.setCurrentStepForm("#step2form-single_language");
        }
        /**
         * Hide the step 3 forms
         */
        hideElement(".sites_step3-single-domain");
        hideElement(".sites_step3-multi-domain");

        domainSingleOpt = "";
    }

    /**
     * Domain step on creating site
     */
    function domainsProcess()
    {
        //check if multi language
        var isMultiLang = $('#is_multi_language').bootstrapSwitch('status');
        //clear selected languages
        var lang = '';
        selectedLanguages = '';
        /**
         * On this step, we are in step 3
         * but we are still processing the data from
         * the step 2 so that we can determine what
         * we are displaying on step 3
         */

        var langData = {};
        var domainData = {};
        /**
         * check if site is multi lingual
         */
        if(isMultiLang){
            /**
             * Load the multi lingual form
             */
            var multiLangFormData = getSerializedForm("#step2form-multi_language");
            //include the step2 data into the object
            langData = processSiteLanguage(multiLangFormData, lang);

            var multiDomainsContainer = $(".sites_step3-multi-domain #multi-domains_container");
            var div = $("<div/>",{
                class: "form-group"
            });

            multiDomainsContainer.empty();
            $.each(multiLangFormData, function(){
                if(this.name == "site_selected_lang"){
                    var langData = this.value.split("-");
                    /**
                     * prepare lang info
                     */
                    if(lang == ""){
                        lang = langData[2];
                    }else {
                        lang = lang + " / " + langData[2];
                    }
                    /**
                     * This will create a text input for language
                     * depending the selected language
                     * of the user
                     */
                    var label = $("<label/>").html(langData[2]+"<sup>*</sup>").addClass("err_site-domain-"+this.value);
                    div.append(label);
                    var domainName = "site-domain-"+this.value;
                    var input = $("<input/>",{
                        type: "text",
                        class: "form-control",
                        name: domainName,
                        value: applyDomainValue(domainName),
                        required: "required",
                        title: ''
                    }).attr("data-langId", langData[0]);
                    div.append(input);
                    multiDomainsContainer.append(div);
                }else if(this.name == "sites_url_setting"){
                    /**
                     * get the value of the site url setting to
                     * determine whether it is multi domain
                     * or not
                     */
                    if(this.value == 2){
                        //this will load the multi domain form
                        showElement(".sites_step3-multi-domain");
                        hideElement(".sites_step3-single-domain");
                        domainData.isMultiDomain = true;

                        // currentStepForm = "#step3form-multi_domain";
                        cmsSiteHelper.setCurrentStepForm("#step3form-multi_domain");

                        domainSingleOpt = '';
                    }else{
                        //load the single domain form
                        showElement(".sites_step3-single-domain");
                        hideElement(".sites_step3-multi-domain");
                        domainData.isMultiDomain= false;

                        // currentStepForm = "#step3form-single_domain";
                        cmsSiteHelper.setCurrentStepForm("#step3form-single_domain");

                        if(this.value == 1){
                            domainSingleOpt = " ("+translations.tr_melis_cms_sites_tool_add_step5_single_dom_opt_1_msg+")";
                        }else if(this.value == 3){
                            domainSingleOpt = " ("+translations.tr_melis_cms_sites_tool_add_step5_single_dom_opt_3_msg+")";
                        }
                    }
                }
            });
        }else{
            // currentStepForm = "#step3form-single_domain";
            cmsSiteHelper.setCurrentStepForm("#step3form-single_domain");
            /**
             * Load the single domain if the site is not
             * multi lingual
             */
            showElement(".sites_step3-single-domain");
            hideElement(".sites_step3-multi-domain");

            domainData.isMultiDomain = false;
            //add step  2 data
            var singLangFormData = getSerializedForm("#step2form-single_language");
            langData = processSiteLanguage(singLangFormData, lang);
            lang = langData.langDetails;
        }
        // formData.languages = langData.data;
        // formData.domains = domainData;
        cmsSiteHelper.setSitesStepData(langData.data,"languages");
        cmsSiteHelper.setSitesStepData(domainData,"domains");

        selectedLanguages = '- '+translations.tr_melis_cms_sites_tool_add_header_title_lang+' : ' + lang;

        /**
         * hide the step 4 forms
         */
        hideElement('.step-4-datas');
    }

    /**
     * Modules step on creating site
     */
    function modulesProcess()
    {
        showElement('.step-4-datas');
        // currentStepForm = "#step4form_module";
        cmsSiteHelper.setCurrentStepForm("#step4form_module");
        /**
         * Process the domain form
         * to get the data
         * @type {string}
         */
        var domain = '';
        var domainFormData = {};
        if(formData.domains.isMultiDomain){
            domainFormData = getSerializedForm("#step3form-multi_domain");
            domain = 'Multiple';
        }else{
            domainFormData = getSerializedForm("#step3form-single_domain");
            domain = 'Single'+domainSingleOpt;
        }

        domainType = '- '+translations.tr_melis_cms_sites_tool_add_header_title_domains+' : ' + domain;
        // formData.domains.data = processSiteDomain(domainFormData);
        cmsSiteHelper.setSitesStepData(processSiteDomain(domainFormData), "domains.data");

        /**
         * Hide the finish button when
         * your are on step4 ang below
         */
        showElement("#btn-next-step");
        hideElement("#btn-finish-step");
    }

    /**
     * Summary step on creating site
     * It should be the final step
     */
    function summaryProcess()
    {
        //get the data of step4
        var step4Obj = {};
        step4Obj.data = processSiteModule();
        step4Obj.newSite = newSite;
        step4Obj.createFile = createFile;
        // formData.module = step4Obj;
        cmsSiteHelper.setSitesStepData(step4Obj, "module");

        /**
         * Hide the next button and
         * show the finish button
         * when you are on the last
         * step
         */
        showElement("#btn-finish-step");
        hideElement("#btn-next-step");

        /**
         * prepare to display the user selected
         * options on site creation
         */
        var text = translations.tr_melis_cms_sites_tool_add_step5_new_site_using_existing_module;
        if(newSite){
            text = translations.tr_melis_cms_sites_tool_add_step5_new_site_using_new_module;
        }
        var siteSumText = text.replace(/%siteModule/g, siteName).replace(/%siteName/g, siteLabel);
        var sumText = selectedLanguages + "<br />" + domainType + "<br/><p class='step5-message'>"+siteSumText+"</p>";
        cmsSiteHelper.setSummaryStepText(sumText, 'append', true);
        // $(".site_creation_info").empty().append(selectedLanguages, "<br />",domainType,
        //     "<br/><p class='step5-message'>"+siteSumText+"</p>");
    }

    /**
     * This will send a request to
     * create a new site
     */
    $body.on("click", "#btn-finish-step", function(e) {
        $.ajax({
            url: "/melis/MelisCms/Sites/createNewSite",
            method: "POST",
            data: {"data" : formData},
            dataType: "JSON",
            beforeSend: function(){
                melisCoreTool.pending("#btn-finish-step");
            }
        }).done(function(data) {
            if(data.success){
                // call melisOkNotification
                melisHelper.melisOkNotification(data.textTitle, data.textMessage, '#72af46' );

                $('#id_meliscms_tool_sites_modal_container_container').modal('hide');
                //re init variables
                initVariables();
                //refresh site table
                $("#tableToolSites").DataTable().ajax.reload();
                //open tabs for newly created site
                $.each(data.siteIds, function(i, id){
                    openSiteEditTab(updateSiteTitle(id, data.siteName, data.siteModuleName), id,data.siteModuleName);
                });
                //refresh site tree view
                $("input[name=left_tree_search]").val('');
                $("#id-mod-menu-dynatree").fancytree("destroy");
                mainTree();
                //execute callback
                cmsSiteHelper.finishCallback();
            }else{
                melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
            }
            melisCore.flashMessenger();
            melisCoreTool.done("#btn-finish-step");
        }).fail(function(xhr, textStatus, errorThrown) {
            alert( translations.tr_melis_cms_sites_tool_add_create_site_unknown_error );
        });

        e.preventDefault();
    });

    $body.on("change", "#is_create_new_module_for_site input[name='is_create_module']", function() {
        $(".step4-forms").removeClass("hidden");

        var value = $(this).val(),
            step4_form = $("#step4form_module");

        if ( value == "yes" ) {
            newSite = false;
            createFile = false;
            //show the list of modules
            showElement(step4_form.find(".form-group.siteSelectModuleName"));
            //hide the other input
            hideElement(step4_form.find(".form-group.siteCreateModuleName"));
            hideElement(step4_form.find(".form-group.create_sites_file"));
            //add required field
            addAttribute(step4_form.find(".form-group select[name='siteSelectModuleName']"), "required", "required");
            //remove required field on create_sites_file
            removeAttribute(step4_form.find(".form-group input[name='create_sites_file']"), "required");
            removeAttribute(step4_form.find(".form-group input[name='siteCreateModuleName']"), "required");
        } else {
            newSite = true;
            //show the field to creat new module
            showElement(step4_form.find(".form-group.siteCreateModuleName"));
            showElement(step4_form.find(".form-group.create_sites_file"));
            //hide the other input
            hideElement(step4_form.find(".form-group.siteSelectModuleName"));
            //add required field
            addAttribute(step4_form.find(".form-group input[name='create_sites_file']"), "required", "required");
            addAttribute(step4_form.find(".form-group input[name='siteCreateModuleName']"), "required", "required");
            //remove required fields
            removeAttribute(step4_form.find(".form-group select[name='siteSelectModuleName']"), "required");
        }
        updateSliderHeight();
        isUserSelectModuleOption = true;
    });

    /**
     * Process site lang data(Single language)
     * @param form
     * @param lang
     */
    function processSiteLanguage(form, lang) {
        var langData    = {},
            data        = {};

            $.each(form, function(i, v){
                if(this.name == "site_selected_lang") {
                    var langInfo = this.value.split("-");
                    /**
                     * prepare lang info
                     */
                    lang = langInfo[2];

                    langData[langInfo[1]] = langInfo[0];
                }else{
                    langData[v.name] = v.value;
                }

                data.data = langData;
                data.langDetails = lang;
            });
            return data;
    }

    /**
     * Process site domain data
     * @param form
     */
    function processSiteDomain(form) {
        var domainData = {};
        //clear domain values
        selectedDomainValue = [];
        $.each(form, function(i, v){
            selectedDomainValue.push(v);
            if (v.name.indexOf("site-domain") >= 0){
                var dom = v.name.split("-");
                var langName = dom[3];
                domainData[langName] = {"sdom_domain" : v.value};
            }else{
                domainData[v.name] = v.value;
            }
        });
        return domainData;
    }

    /**
     * Process the site module data
     * @returns {*|jQuery}
     */
    function processSiteModule() {
        var form = getSerializedForm("#step4form_module");
        $.each(form, function(i, v){
            if(v.name == "create_sites_file"){
                //we decide only to create a file if it is a new site
                if(newSite) {
                    if (v.value == "yes") {
                        createFile = true;
                    } else {
                        createFile = false;
                    }
                }
                delete form[i];
            }else if(v.name == "siteSelectModuleName"){
                delete form[i];
                if(!newSite){
                    form.push({'site_name':v.value});
                    siteName = v.value;
                }
            }else if(v.name == "siteCreateModuleName"){
                delete form[i];
                if(newSite) {
                    form.push({'site_name': v.value});
                    siteName = v.value;
                }
            }else if(v.name == "site_label"){
                siteLabel = v.value;
            }
        });
        return form;
    }

    /**
     * Apply the selected domain
     * @param domainName
     * @returns {string}
     */
    function applyDomainValue(domainName) {
        var value = "";
        $.each(selectedDomainValue, function(i, v){
            if(v.name == domainName){
                value = v.value;
                return value;
            }
        });
        return value;
    }

    /**
     * Check if form is empty
     * @param form
     * @param currentStepForm
     * @returns {boolean}
     */
    function isFormEmpty(form, currentStepForm) {
        if(configFormPath != "") {
            /**
             * Send ajax to validate form
             */
            form.push({ name: "form_config_path", value: configFormPath});
            $.ajax({
                type : 'POST',
                url : '/melis/MelisCms/Sites/validateSiteCreationForm',
                data : form,
                beforeSend : function () {
                    melisCoreTool.pending("#btn-next-step");
                    $("#siteAddAlert").empty().addClass("hidden");
                }
            }).done(function(data){
                $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_create_site_required_field);
                melisCoreTool.done("#btn-next-step");
                if(!data.success){
                    melisHelper.highlightMultiErrors(data.success, data.errors, currentStepForm);
                    //display errors
                    $("#siteAddAlert").removeClass("hidden");
                    $.each(data.errors, function (key, fieldErrors) {
                        $.each(fieldErrors, function(k, errMsg){
                            if(k !== 'label'){
                                $("#siteAddAlert").append("<br/><span>"+fieldErrors.label+": <small>"+errMsg+"</small></span>");
                            }
                        });
                    });

                    return true;
                }else{
                    owlStep.trigger('owl.next');
                    return false;
                }
            }).fail(function(){
                melisCoreTool.done("#btn-next-step");
                alert( translations.tr_meliscore_error_message );
                return false;
            });
            return true;
        }else{
            /**
             * Custom js validation
             * @type {Array}
             */
            var fromInputNames = [];
            $.each(form, function (i, v) {
                if (v.value != "") {
                    fromInputNames.push(v.name)
                }
            });
            return showFormError(currentStepForm, fromInputNames);
        }
    }

    /**
     * Show error on form
     * @param form
     * @param fieldNames
     * @returns {boolean}
     */
    function showFormError(form, fieldNames) {
        var newModuleLabel  = "",
            newModuleValue  = "",
            newSDOmLabel    = "",
            newSDOmValue    = "",
            multiDomainErr  = {},
            errCtr          = 0,
            curForm         = $(form+" input, "+form+" select"),
            domains         = {},
            domainsArr      = [],
            duplicates      = [];

        /**
         * Bring back the original message
         */
        $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_create_site_required_field).addClass('hidden');

        /**
         * if user didn't select the module option
         * return an error
         */
        if(form == "#step4form_module") {
            if (!isUserSelectModuleOption) {
                $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_step4_select_module_option_err).removeClass("hidden");
                return true;
            }
        }

        curForm.each(function(){
            if($(this).prop('required')){
                var inputName = $(this).attr("name");
                var inputId = $(this).attr("id");
                // var errlabel = $(this).closest("form").find("label.err_" + inputName).not(":has(input)");
                var errlabel = $(this).closest("div.form-group").find("label:first");
                if (jQuery.inArray(inputName, fieldNames) === -1) {
                    errlabel.addClass("fieldErrorColor");
                    errCtr++;
                } else {
                    errlabel.removeClass("fieldErrorColor");
                }

                if(inputName == "siteCreateModuleName"){
                    newModuleValue = $(this).val();
                    newModuleLabel = $(this).closest("form").find("label.err_" + inputName).not(":has(input)");
                }

                if(inputName == "sdom_domain"){
                    newSDOmValue = $(this).val();
                    newSDOmLabel = $(this).closest("form").find("label.err_" + inputName).not(":has(input)");
                }

                if(inputName.indexOf("site-domain-") !== -1){
                    var sdomMultiVal = $(this).val();
                    multiDomainErr[inputName] = sdomMultiVal;
                }
            }
        });

        if (errCtr > 0) {
            $("#siteAddAlert").removeClass("hidden");
            return true;
        }

        /**
         * This will avoid the user to input
         * space and special characters to
         * create a new module name
         */
        if(newModuleValue != "") {
            if (/^[A-Za-z]*$/.test(newModuleValue) === false) {
                newModuleLabel.addClass("fieldErrorColor");
                $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_step4_create_module_error).removeClass("hidden");
                return true;
            }
        }

        var domainNameErrCtr = 0;
        /**
         * Check if domain name is valide
         */
        //for single domain
        if(newSDOmValue != "") {
            if(validatedDomainName(newSDOmLabel, newSDOmValue)){
                domainNameErrCtr++;
            }
        }

        //for multi domain
        $.each(multiDomainErr, function(lbl, value){
            var sdomMultiLbl = $("#step3form-multi_domain").find("label.err_" + lbl).not(":has(input)");
            if(validatedDomainName(sdomMultiLbl, value)){
                domainNameErrCtr++;
            }

            // check if there are duplicate domains
            if ($.inArray(value, domainsArr) === -1) {
                domains[lbl] = value;
                domainsArr.push(value);
            } else {
                duplicates.push(lbl);
             }
        });

        if (domainNameErrCtr > 0) {
            $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_step3_invalid_domain_name).removeClass("hidden");
            return true;
        } else {
            // multi domain
            if (!$.isEmptyObject(domains)) {
                if (duplicates.length !== 0) {
                    $.each(duplicates, function (key, lbl) {
                        var sdomMultiLbl = $("#step3form-multi_domain").find("label.err_" + lbl).not(":has(input)");
                        sdomMultiLbl.addClass("fieldErrorColor");
                    });

                    $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_step3_domain_unique_error).removeClass("hidden");
                    return true;
                } else {
                    $.ajax({
                        type: 'POST',
                        url: '/melis/MelisCms/SitesDomains/checkDomain',
                        data: {domain: domains},
                        beforeSend: function () {
                            melisCoreTool.pending("#btn-next-step");
                        }
                    }).done(function(data) {
                        if (!$.isEmptyObject(data.result)) {
                            $("#siteAddAlert").text('');
                            var length = data.result.length;
                            var counter = 1;

                            $.when(
                                $.each(data.result, function (id, val) {
                                    var sdomMultiLbl = $("#step3form-multi_domain").find("label.err_" + id).not(":has(input)");
                                    sdomMultiLbl.addClass("fieldErrorColor");
                                    var lang = sdomMultiLbl.text().slice(0, -1);

                                    $("#siteAddAlert").append(lang + ' - ' + translations.tr_melis_cms_sites_tool_add_step3_domain_error1 + val + translations.tr_melis_cms_sites_tool_add_step3_domain_error2);

                                    if (counter != length) {
                                        $("#siteAddAlert").append('</br>');
                                    }

                                    counter++;
                                })
                            ).then(function () {
                                melisCoreTool.done("#btn-next-step");
                                $("#siteAddAlert").removeClass('hidden');
                                return true;
                            });
                        } else {
                            melisCoreTool.done("#btn-next-step");
                            //owlStep.trigger('owl-next');
                            owlStep.trigger('owl.next');
                            //$(".owl-next").trigger("click");
                            return false;
                        }
                    }).fail(function(xhr, textStatus, errorThrown) {
                        melisCoreTool.done("#btn-next-step");
                        alert( translations.tr_meliscore_error_message );
                    });

                    return true;
                }
            }

            // single domain
            if (newSDOmValue != "") {
                $.ajax({
                    type : 'POST',
                    url : '/melis/MelisCms/SitesDomains/checkDomain',
                    data : {domain : newSDOmValue},
                    beforeSend : function () {
                        melisCoreTool.pending("#btn-next-step");
                    }
                }).done(function(data) {
                    if (!$.isEmptyObject(data.result)) {
                        newSDOmLabel.addClass("fieldErrorColor");
                        $("#siteAddAlert").text(translations.tr_melis_cms_sites_tool_add_step3_domain_error1 + data.result[0] + translations.tr_melis_cms_sites_tool_add_step3_domain_error2);
                        $("#siteAddAlert").removeClass('hidden');
                        melisCoreTool.done("#btn-next-step");
                        return true;
                    } else {
                        //owlStep.trigger('owl-next');
                        owlStep.trigger('owl.next');
                        //$(".owl-next").trigger("click");
                        melisCoreTool.done("#btn-next-step");
                        return false;
                    }
                }).fail(function(xhr, textStatus, errorThrown) {
                    melisCoreTool.done("#btn-next-step");
                });

                return true;
            }
            return false;
        }
    }

    /**
     * Remove errors from form
     * @param form
     */
    function removeFormError(form) {
        if(form != "" && form != "skip") {
            var curForm = $(form + " input, "+form+" select");
            curForm.each(function () {
                var inputName = $(this).attr("name");
                var errlabel = $(this).closest("form").find("label.err_" + inputName).not(":has(input)");
                errlabel.removeClass("fieldErrorColor");
            });
        }
    }

    function validatedDomainName(label, value) {
        if (/^(www\.)?(([a-zA-Z0-9-])+\.)?(([a-zA-Z0-9-])+\.)?(([a-zA-Z0-9-])+\.)?(([a-zA-Z0-9-])+\.)?[a-zA-Z0-9\-]{1,}(\.([a-zA-Z]{2,}))$/.test(value) === false) {
            label.addClass("fieldErrorColor");
            return true;
        }
        return false;
    }

    /**
     * Function to update the slider
     * height
     */
    function updateSliderHeight() {
        setInterval(function () {
            $(".sites-steps-owl").each(function () {
                $(this).data('owlCarousel').autoHeight();
            });
        });
    }

    /**
     * Open site edition tab
     * @param name
     * @param siteId
     * @param moduleName
     */
    function openSiteEditTab(name, siteId, moduleName) {
        melisHelper.tabOpen(name, 'fa-book', siteId+'_id_meliscms_tool_sites_edit_site', 'meliscms_tool_sites_edit_site',  { siteId : siteId, moduleName : moduleName }, 'id_meliscms_tool_sites', function(){
            $("#" + siteId + "_id_meliscms_tool_sites_edit_site_header site-title-tag").text(" / " + name);
        });
    }

    function updateActiveStep(step) {
        var currStep = step.split("_");
        var ul = $("ul.create-site-step");
        ul.find("span.step-current").text(currStep[1]);

        //remove all active tab
        ul.each(function(){
            $(this).find("li").removeClass("active");
        });

        //set active tab
        ul.each(function(){
            $(this).find("li."+step).addClass("active");
        });

        ul.find("span.step-name").text(ul.find("li.active").attr("data-stepName"));
    }

    function initVariables() {
        formData = {};
        selectedLanguages = '';
        domainType = '';
        createFile = true;
        newSite = true;
        owlStep = null;
        currentStepForm = '';
        siteName = '';
        siteLabel = '';
        selectedDomainValue = [];
        isUserSelectModuleOption = false;
    }

    function updateSiteTitle(selId, siteName, siteModule, siteLang) {
        $("#tableToolSites tbody tr").each(function(){
            var siteNames = $(this).find("td:nth-child(2)").text();
            var siteModules = $(this).find("td:nth-child(3)").text();
            var lang = $(this).find("td:nth-child(4)").text();
            siteLang = (siteLang == undefined ? lang : siteLang);
            var id = $(this).attr("id");
            if(selId != id) {
                if (siteName == siteNames) {
                    if (siteModule == siteModules) {
                        siteName += " - " + siteLang;
                        return siteName;
                    }
                }
            }
        });
        return siteName;
    }

    /**
     *
     * @param form
     * @returns {*|jQuery}
     */
    function getSerializedForm(form) {
        return $(form).serializeArray();
    }

    /**
     *
     * @param elem
     */
    function showElement(elem) {
        $(elem).show();
    }

    /**
     *
     * @param elem
     */
    function hideElement(elem) {
        $(elem).hide();
    }

    /**
     *
     * @param elem
     * @param attr
     * @param value
     */
    function addAttribute(elem, attr, value) {
        elem.attr(attr, value);
    }

    /**
     *
     * @param elem
     * @param attr
     */
    function removeAttribute(elem, attr) {
        elem.removeAttr(attr);
    }

    // Disable enter on step 3 domains
    $body.on('keypress', '#step3form-single_domain  #sdom_domain', function(e) {
        return e.which !== 13;
    });

    /**
     * ================================================================================
     * ============================== END SITE CREATION ===============================
     * ================================================================================
     */

    /**
     * ================================================================================
     * ============================== START PROPERTIES TAB ============================
     * ================================================================================
     */
    $body.on("click", "#s404_page_id_button span", function() {
        var $this   = $(this),
            formId  = $this.closest('form').attr('id');

            melisLinkTree.createInputTreeModal('#' + formId + ' ' + '#siteprop_s404_page_id');
    });

    $body.on("click", "#site_main_page_id_button span", function() {
        var $this   = $(this),
            formId  = $this.closest('form').attr('id');

            melisLinkTree.createInputTreeModal('#' + formId + ' ' + '#siteprop_site_main_page_id');
    });

    $body.on("click", ".pageSelect span.input-group-addon", function() {
        var $this = $(this),
            id = $this.siblings('input').attr('id'),
            formId = $this.closest('form').attr('id');

            melisLinkTree.createInputTreeModal('#' + formId + ' ' + '#' + id);
    });
    /**
     * ================================================================================
     * ============================== END PROPERTIES TAB ===============================
     * ================================================================================
     */

    /**
     * ================================================================================
     * ============================== START DOMAINS TAB ===============================
     * ================================================================================
     */
    // Disable enter on domain input
    $body.on('keypress', '#meliscms_tool_sites_domain_form  input.form-control', function(e) {
        return e.which !== 13;
    });
    /**
     * ================================================================================
     * ================================ END DOMAINS TAB ===============================
     * ================================================================================
     */

    /**
     * ================================================================================
     * ============================== START LANGUAGES TAB =============================
     * ================================================================================
     */
    $body.on('change', '.sites-tool-lang-tab-checkbox', function () {
        var $this = $(this),
            input = $this.siblings('.sites-tool-lang-tab-checkbox-lang');

        if ( $this.data('active') === 'active' && !this.checked ) {
            melisCoreTool.confirm(
                translations.tr_meliscore_common_yes,
                translations.tr_meliscore_common_no,
                translations.tr_melis_cms_sites_tool_languages_title,
                translations.tr_melis_cms_sites_tool_languages_prompt_delete_data,
                function() {
                    input.val('true');
                }
            );
        } else {
            input.val('false');
        }
    });

    // Toggle single checkbox
    $body.on("click", ".cb-cont input[type=checkbox]", function () {
        if ($(this).is(':checked')) {
            $(this).prop("checked", true);
            $(this).prev("span").find(".cbmask-inner").addClass('cb-active');
        } else {
            $(this).not(".requried-module").prop("checked", false);
            $(this).not(".requried-module").prev("span").find(".cbmask-inner").removeClass('cb-active');
        }
    });

    $body.on("click", "#step2form-multi_language .cb-cont input[type=checkbox]", function() {
        if ($(this).is(':checked')) {
            $(this).prop("checked", true);
            $(this).prev("span").find(".cbmask-inner").addClass('cb-active');
        } else {
            $(this).not(".requried-module").prop("checked", false);
            $(this).not(".requried-module").prev("span").find(".cbmask-inner").removeClass('cb-active');
        }
    });
    /**
     * ================================================================================
     * ============================== END LANGUAGES TAB ===============================
     * ================================================================================
     */

    window.generatePageLink = function(pageId, inputTarget){
        var pageId = (typeof pageId !== "undifined") ? pageId : null;

            inputTarget.data("idPage", pageId);

            dataString = inputTarget.data();

            if ( pageId ) {
                $.ajax({
                    type        : 'GET',
                    url         : '/melis/MelisCms/Page/getPageLink',
                    data		: dataString,
                    dataType    : 'json',
                    encode		: true
                }).done(function(res) {
                    inputTarget.val(res.link);
                }).fail(function(xhr, textStatus, errorThrown) {
                    alert( translations.tr_meliscore_error_message );
                });
            } else {
                alert( "PageId is null" );
            }
    };

    // Add Event to "Minify Button"
    $body.on("click", ".btnMinifyAssets", function(){
        var _this 	= $(this),
            siteId 	= _this.parents("tr").attr("id");

        $.ajax({
            type        : 'POST',
            url         : '/minify-assets',
            data		: {siteId : siteId},
            dataType    : 'json',
            encode		: true,
            beforeSend  : function(){
                _this.attr('disabled', true);
            }
        }).done(function(data) {
            if ( data.success ) {
                melisHelper.melisOkNotification(data.title, 'tr_front_minify_assets_compiled_successfully');
            } else {
                var errorTexts = '<h3>'+ melisHelper.melisTranslator(data.title) +'</h3>';
                errorTexts += '<p><strong>Error: </strong>  ';
                errorTexts += '<span>'+ data.message + '</span>';
                errorTexts += '</p>';

                var div = "<div class='melis-modaloverlay overlay-hideonclick'></div>";
                div += "<div class='melis-modal-cont KOnotif'>  <div class='modal-content'>"+ errorTexts +" <span class='btn btn-block btn-primary'>"+ translations.tr_meliscore_notification_modal_Close +"</span></div> </div>";
                $body.append(div);
            }

            _this.attr('disabled', false);
        }).fail(function(xhr, textStatus, errorThrown) {
            alert( translations.tr_meliscore_error_message );
        });
    });

    var meliscmsSiteSelectorInputDom = '';
    // to solved console DOM non-unique id
    $body.on("click", ".meliscms-site-selector", function(){
        // initialation of local variable
        zoneId = 'id_meliscms_page_tree_id_selector';
        melisKey = 'meliscms_page_tree_id_selector';
        modalUrl = 'melis/MelisCms/Page/renderPageModal';

        $('#melis-modals-container').find('#id_meliscms_page_tree_id_selector_container').remove();
        meliscmsSiteSelectorInputDom = $(this).parents(".input-group").find("input");

        // remove last modal prevent from appending infinitely
        $("body").on('hide.bs.modal', "#id_meliscms_page_tree_id_selector_container", function () {
            $("#id_meliscms_page_tree_id_selector_container").remove();
            if($("body").find(".modal-backdrop").length == 2) {
                $("body").find(".modal-backdrop").last().remove();
            }
        });

        melisHelper.createModal(zoneId, melisKey, false, {}, modalUrl, function(){
            // Removing Content menu of Fancytree
            $.contextMenu("destroy", ".fancytree-title");
        });
    });

    $body.on("click", "#meliscms-site-selector", function(){
        // initialation of local variable
        zoneId = 'id_meliscms_page_tree_id_selector';
        melisKey = 'meliscms_page_tree_id_selector';
        modalUrl = 'melis/MelisCms/Page/renderPageModal';

        $('#melis-modals-container').find('#id_meliscms_page_tree_id_selector_container').remove();
        meliscmsSiteSelectorInputDom = $(this).parents(".input-group").find("input");

        // remove last modal prevent from appending infinitely
        $("body").on('hide.bs.modal', "#id_meliscms_page_tree_id_selector_container", function () {
            $("#id_meliscms_page_tree_id_selector_container").remove();
            if($("body").find(".modal-backdrop").length == 2) {
                $("body").find(".modal-backdrop").last().remove();
            }
        });

        melisHelper.createModal(zoneId, melisKey, false, {}, modalUrl, function(){
            // Removing Content menu of Fancytree
            $.contextMenu("destroy", ".fancytree-title");
        });
    });

    $body.on("click", "#selectPageId", function(){

        var tartGetId = $('#find-page-dynatree .fancytree-active').parent('li').attr('id');

        if(typeof tartGetId !== "undefined"){
            // Getting the id from Id attribute
            var pageId = tartGetId.split("_")[1];

            if(meliscmsSiteSelectorInputDom.length){

                // Assigning id to page id input
                meliscmsSiteSelectorInputDom.val(pageId);

                if(meliscmsSiteSelectorInputDom.data("callback")){
                    callback = meliscmsSiteSelectorInputDom.data("callback");

                    if(typeof window[callback] === "function"){
                        window[callback](pageId, meliscmsSiteSelectorInputDom);
                    }else{
                        //console.log("callback "+meliscmsSiteSelectorInputDom.data("callback")+" is not a function.")
                    }
                }

                // Close modal
                $(this).closest(".modal").modal("hide");
            }else{
                melisHelper.melisKoNotification("tr_meliscms_menu_sitetree_Name", "tr_meliscore_error_message");
            }
        }else{
            melisHelper.melisKoNotification("tr_meliscms_menu_sitetree_Name", "tr_meliscms_page_tree_no_selected_page");
        }
    });

    $body.on("change", "#siteVarietySelect", function() {
        $("#tableToolSites").DataTable().ajax.reload();
    });
});

/**
 * Sites table callback
 */
window.sitesTableCallback = function(){
    /**
     * Disable the minify button if
     * module is not found
     */
    var minifBtn = $("#tableToolSites tbody tr[data-mod-found='false']").find(".btnMinifyAssets");
    minifBtn.prop("disabled", true);
    minifBtn.attr("disabled", true);
    minifBtn.attr("title", translations.tr_melis_cms_minify_assets_no_module_button_title);
};

window.initSitesList = function(data, tblSettings) {
    var $siteVarietySelect = $('#siteVarietySelect');
    if ( $siteVarietySelect.length ) {
        data.site_variety = $siteVarietySelect.val();
    }
}

var cmsSiteHelper = (function() {
    /**
     * This function is used in creating sites,
     * so we can validated a certain form before it goes
     * to the next step
     * @param formId
     */
    function setCurrentStepForm(formId){
        currentStepForm = formId;
    }

    /**
     * This function is used to set steps data
     * when creating site
     * @param data
     * @param key
     */
    function setSitesStepData(data, key){
        formData[key] = data;
    }

    /**
     * Function to get all provided sites data
     * in every step
     *
     * @returns {{}}
     */
    function getSitesStepData(){
        return formData;
    }

    /**
     * This function will return the summary
     * text in the summary step when creating site
     *
     * @returns {jQuery}
     */
    function getSummarySteText()
    {
        return $(".site_creation_info").text();
    }

    /**
     * This function will set a summary text
     * in the summary step when creating site
     * @param text
     * @param type
     * @param empty
     */
    function setSummaryStepText(text, type, empty)
    {
        type = (type == undefined) ? 'append' : type;
        empty = (empty == undefined) ? false : empty;

        // siteCreationSummaryText += text;
        if(type == 'append') {
            if(empty) {
                $(".site_creation_info").empty().append(text);
            }else{
                $(".site_creation_info").append(text);
            }
        }else{
            if(empty) {
                $(".site_creation_info").empty().prepend(text);
            }else{
                $(".site_creation_info").prepend(text);
            }
        }
    }

    /**
     * This function is called after site is successfully created
     * You can override this inside your js file to execute a certain command
     */
    function finishCallback(){}

    return {
        setCurrentStepForm: setCurrentStepForm,
        setSitesStepData: setSitesStepData,
        getSitesStepData: getSitesStepData,
        finishCallback: finishCallback,
        getSummarySteText: getSummarySteText,
        setSummaryStepText: setSummaryStepText
    };
})();
