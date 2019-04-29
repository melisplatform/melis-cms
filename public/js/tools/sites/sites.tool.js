$(document).ready(function() {
    $body = $("body");

    /**
     * Get all input values into one array on clicking save button except for the site translation inputs
     */
    $body.on("click","#btn-save-meliscms-tool-sites", function () {
        var currentTabId = activeTabId.split("_")[0];
        var dataString = $("#"+currentTabId+"_id_meliscms_tool_sites_edit_site form").serializeArray();
        // serialize the new array and send it to server
        dataString = $.param(dataString);

        $.ajax({
            type        : 'POST',
            url         : '/melis/MelisCms/Sites/saveSite?siteId='+currentTabId,
            data        : dataString,
            dataType    : 'json',
            encode		: true
        }).success(function (data) {
            if (data.success === 1) {
                // call melisOkNotification
                melisHelper.melisOkNotification( data.textTitle, data.textMessage, '#72af46' );
                // update flash messenger values
                melisCore.flashMessenger();

                melisHelper.zoneReload(
                    currentTabId + '_id_meliscms_tool_sites_edit_site',
                    'meliscms_tool_sites_edit_site',
                    {
                        siteId: currentTabId,
                        cpath: 'meliscms_tool_sites_edit_site'
                    }
                );
            } else {
                melisCoreTool.highlightErrors(data.success, data.errors, currentTabId+"_id_meliscms_tool_sites_edit_site");
                // error modal
                melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors );
            }

            // update flash messenger values
            melisCore.flashMessenger();
        }).error(function(xhr, textStatus, errorThrown) {
            alert( translations.tr_meliscore_error_message );
        });
    });

    /**
     * This will open a new tab when editing a site
     */
    $body.on("click", ".btnEditSites", function(){
        var tableId = $(this).closest('tr').attr('id');
        var name = $(this).closest('tr').find("td:nth-child(2)").text();
        openSiteEditTab(name, tableId);
    });


    /**
     * ======================================================================================
     * =============================== START CREATE SITES ===================================
     * ======================================================================================
     */
    var formData = {};
    var selectedLanguages = '';
    var domainType = '';
    var createFile = true;
    var newSite = true;
    var owlStep = null;
    var currentStepForm = '';
    var siteName = '';
    var selectedDomainValue = [];

    /**
     * This will delete the site
     */
    $body.on("click", ".btnDeleteSite", function(e) {
        var siteId = $(this).parents("tr").attr("id");
        melisCoreTool.confirm(
            translations.tr_meliscore_common_yes,
            translations.tr_meliscore_common_no,
            translations.tr_meliscms_tool_site_delete_confirm_title,
            translations.tr_meliscms_tool_site_delete_confirm,
            function(){
                $.ajax({
                    type        : "POST",
                    url         : "/melis/MelisCms/Sites/deleteSite",
                    data		: {siteId: siteId},
                    dataType    : 'json',
                    encode		: true,
                    success		: function(data){
                        melisCoreTool.pending(".btnDeleteSite");
                        if(data.success) {
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
                    }
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
    window.initializeStep = function () {
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
            responsive: false,
            afterMove: function (elem) {
                var current = this.currentItem;
                //hide the prev button when we are on the first step
                if(current === 0){
                    hideElement("#btn-prev-step");
                }else{
                    showElement("#btn-prev-step");
                }
            },
            beforeMove: function(elem){
                var current = this.currentItem;
                var step = elem.find(".item").eq(current).attr("data-step");
                checkStep(step);
                updateActiveStep(step);
            }
        });
    };

    $body.on("click", "#btn-next-step", function(e){
        if(owlStep !== null) {
            /**
             * check if form is not empty before
             * proceeding to the next slide
             */
            if(currentStepForm != "" && currentStepForm != "skip") {
                var form = getSerializedForm(currentStepForm);
                if (isFormEmpty(form, currentStepForm)) {
                    $("#siteAddAlert").removeClass("hidden");
                } else {
                    $("#siteAddAlert").addClass("hidden");
                    removeFormError(currentStepForm);
                    owlStep.trigger('owl.next');
                }
            }else{
                $("#siteAddAlert").addClass("hidden");
                removeFormError(currentStepForm);
                owlStep.trigger('owl.next');
            }
        }
    });

    $body.on("click", "#btn-prev-step", function(e){
        if(owlStep !== null) {
            $("#siteAddAlert").addClass("hidden");
            removeFormError(currentStepForm);
            owlStep.trigger('owl.prev');
        }
    });

    /**
     * This will process each step
     * BEFORE proceeding to next slide
     * @param step
     */
    function checkStep(step)
    {
        //check if multi language
        var isMultiLang = $('#is_multi_language').bootstrapSwitch('status');
        //process step
        switch(step){
            case "step_1":
                //skip step 1 form
                currentStepForm = 'skip';
                break;
            case "step_2":
                //include the from in step1
                var step1Obj = {};
                step1Obj.isMultiLang = isMultiLang ? true : false;
                step1Obj.data = getSerializedForm("#step2form-is_multi_lingual");
                formData.multiLang = step1Obj;
                /**
                 * determine what should we display
                 * depending if multi language or not
                 */
                if(isMultiLang){
                    showElement(".step2-forms .sites_step2-multi-language");
                    hideElement(".step2-forms .sites_step2-single-language");

                    currentStepForm = "#step2form-multi_language";
                }else{
                    showElement(".step2-forms .sites_step2-single-language");
                    hideElement(".step2-forms .sites_step2-multi-language");

                    currentStepForm = "#step2form-single_language";
                }
                break;
            case "step_3":
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
                                required: "required"
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

                                currentStepForm = "#step3form-multi_domain";
                            }else{
                                //load the single domain form
                                showElement(".sites_step3-single-domain");
                                hideElement(".sites_step3-multi-domain");
                                domainData.isMultiDomain= false;

                                currentStepForm = "#step3form-single_domain";
                            }
                        }
                    });
                }else{
                    currentStepForm = "#step3form-single_domain";
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
                formData.languages = langData.data;
                formData.domains = domainData;

                selectedLanguages = '- '+translations.tr_melis_cms_sites_tool_add_header_title_lang+': ' + lang;
                break;
            case "step_4":
                currentStepForm = "#step4form_module";
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
                    domain = 'Single';
                }

                domainType = '- '+translations.tr_melis_cms_sites_tool_add_header_title_domains+': ' + domain;
                formData.domains.data = processSiteDomain(domainFormData);

                /**
                 * Hide the finish button when
                 * your are on step4 ang below
                 */
                showElement("#btn-next-step");
                hideElement("#btn-finish-step");
                break;
            case "step_5":
                //get the data of step4
                var step4Obj = {};
                step4Obj.data = processSiteModule();
                step4Obj.newSite = newSite;
                step4Obj.createFile = createFile;
                formData.module = step4Obj;

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
                $(".site_creation_info").empty().append(selectedLanguages, "<br />",domainType,
                                                        "<br/><p class='step5-message'>"+text.replace(/%s/g, siteName)+"</p>");
                break;
            default:
                break;
        }
    }

    /**
     * This will send a request to
     * create a new site
     */
    $body.on("click", "#btn-finish-step", function(e){
        $.ajax({
            url: "/melis/MelisCms/Sites/createNewSite",
            method: "POST",
            data: {"data" : formData},
            dataType: "JSON",
            beforeSend: function(){
                melisCoreTool.pending("#btn-finish-step");
            },
            success: function(data){
                if(data.success){
                    $('#id_meliscms_tool_sites_modal_container_container').modal('hide');
                    $.each(data.siteIds, function(i, id){
                        openSiteEditTab(data.siteName, id);
                    });
                    //re init variables
                    initVariables();
                    //refresh site table
                    $("#tableToolSites").DataTable().ajax.reload();
                    //refresh site tree view
                    $("input[name=left_tree_search]").val('');
                    $("#id-mod-menu-dynatree").fancytree("destroy");
                    mainTree();
                }else{
                    melisHelper.melisKoNotification(data.textTitle, data.textMessage, data.errors);
                }
                melisCore.flashMessenger();
                melisCoreTool.done("#btn-finish-step");
            },
            error: function(){
                console.log(translations.tr_melis_cms_sites_tool_add_create_site_unknown_error);
            }
        });
        e.preventDefault();
    });

    /**
     * This will determine whether the user will create
     * a new site or not
     */
    $body.on("change", "#siteSelectModuleName", function(){
        if($(this).val() != ""){
            newSite = false;
            createFile = false;
            $("#siteCreateModuleName").removeAttr("required").prop("disabled", true);
            $("#step4form_module").find("input[name=create_sites_file]").prop("disabled", true);

            removeFormError("#step4form_module");
            $("#siteAddAlert").addClass("hidden");
        }else{
            $("#siteCreateModuleName").attr("required", "required").prop("disabled", false);
            $("#step4form_module").find("input[name=create_sites_file]").prop("disabled", false);
            newSite = true;
        }
    });

    $body.on("input", "#step4form_module #siteCreateModuleName", function() {
        if($(this).val() != "") {
            $("#step4form_module").find("#createSiteFiles").removeClass("hidden");
        }else{
            $("#step4form_module").find("#createSiteFiles").addClass("hidden");
        }
        updateSliderHeight();
    });

    /**
     * Process site lang data(Single language)
     * @param form
     * @param lang
     */
    function processSiteLanguage(form, lang){
        var langData = {};
        var data = {};
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
    function processSiteDomain(form){
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
    function processSiteModule(){
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
            }
        });
        return form;
    }

    /**
     * Apply the selected domain
     * @param domainName
     * @returns {string}
     */
    function applyDomainValue(domainName){
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
    function isFormEmpty(form, currentStepForm){
        var fromInputNames = [];
        $.each(form, function(i, v){
           if(v.value != ""){
               fromInputNames.push(v.name)
           }
        });
        return showFormError(currentStepForm, fromInputNames);
    }

    /**
     * Show error on form
     * @param form
     * @param fieldNames
     * @returns {boolean}
     */
    function showFormError(form, fieldNames){
        var errCtr = 0;
        var curForm = $(form+" input");
        curForm.each(function(){
            if($(this).prop('required')){
                var inputName = $(this).attr("name");
                var errlabel = $(this).closest("form").find("label.err_" + inputName).not(":has(input)");
                if (jQuery.inArray(inputName, fieldNames) === -1) {
                    errlabel.addClass("fieldErrorColor");
                    errCtr++;
                } else {
                    errlabel.removeClass("fieldErrorColor");
                }
            }
        });

        if(errCtr > 0)
            return true;

        return false;
    }

    /**
     * Remove errors from form
     * @param form
     */
    function removeFormError(form){
        if(form != "" && form != "skip") {
            var curForm = $(form + " input");
            curForm.each(function () {
                var inputName = $(this).attr("name");
                var errlabel = $(this).closest("form").find("label.err_" + inputName).not(":has(input)");
                errlabel.removeClass("fieldErrorColor");
            });
        }
    }

    /**
     * Function to update the slider
     * height
     */
    function updateSliderHeight(){
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
     */
    function openSiteEditTab(name, siteId){
        melisHelper.tabOpen(name, 'fa-globe', siteId+'_id_meliscms_tool_sites_edit_site', 'meliscms_tool_sites_edit_site',  { siteId : siteId }, null, function(){

        });
    }

    function updateActiveStep(step){
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

    function initVariables()
    {
        formData = {};
        selectedLanguages = '';
        domainType = '';
        createFile = true;
        newSite = true;
        owlStep = null;
        currentStepForm = '';
        siteName = '';
        selectedDomainValue = [];
    }

    /**
     *
     * @param form
     * @returns {*|jQuery}
     */
    function getSerializedForm(form){
        return $(form).serializeArray();
    }

    /**
     *
     * @param elem
     */
    function showElement(elem){
        $(elem).show();
    }

    /**
     *
     * @param elem
     */
    function hideElement(elem){
        $(elem).hide();
    }

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
        melisLinkTree.createInputTreeModal('#s404_page_id');
    });

    $body.on("click", "#site_main_page_id_button span", function() {
        melisLinkTree.createInputTreeModal('#site_main_page_id');
    });

    $body.on("click", ".pageSelect span", function() {
        var id = $(this).find('input').attr('id');
        melisLinkTree.createInputTreeModal('#' + id);
    });
    /**
     * ================================================================================
     * ============================== END PROPERTIES TAB ===============================
     * ================================================================================
     */

    /**
     * ================================================================================
     * ============================== START LANGUAGES TAB =============================
     * ================================================================================
     */
    $body.on('change', '.sites-tool-lang-tab-checkbox', function () {
        var input = $(this).closest('label').siblings('.to-delete-languages-data');

        if ($(this).data('active') === 'active' && !this.checked) {
            input.val('false');

            melisCoreTool.confirm(
                translations.tr_meliscore_common_yes,
                translations.tr_meliscore_common_no,
                translations.tr_melis_cms_sites_tool_languages_title,
                translations.tr_melis_cms_sites_tool_languages_prompt_delete_data,
                function() {
                    input.val('true');
                }
            );
        }
    });
    /**
     * ================================================================================
     * ============================== END LANGUAGES TAB ===============================
     * ================================================================================
     */
});