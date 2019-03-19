$(document).ready(function() {
    $body = $("body");
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
                    //show errors
                } else {
                    owlStep.trigger('owl.next');
                }
            }else{
                owlStep.trigger('owl.next');
            }
        }
    });

    $body.on("click", "#btn-prev-step", function(e){
        if(owlStep !== null)
            owlStep.trigger('owl.prev');
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
                            var label = $("<label/>").text(langData[2]);
                            div.append(label);
                            var input = $("<input/>",{
                                type: "text",
                                class: "form-control",
                                name: "site-domain-"+this.value,
                                value: ""
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

                selectedLanguages = '- Languages: ' + lang;
                break;
            case "step_4":
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

                domainType = '- Domains: ' + domain;
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
                $(".site_creation_info").empty().append(selectedLanguages, "<br />",domainType,
                                                        "<br/><p class='step5-message'>"+text.replace(/%s/g, 'TEST')+"</p>");
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

            },
            success: function(data){
                if(data.success){
                    $('#id_meliscms_tool_sites_modal_container_container').modal('hide');
                    openSiteEditTab(data.siteName, data.siteId);
                }
                melisCore.flashMessenger();
            }
        });
        e.preventDefault();
    });

    $body.on("change", "#siteSelectModuleName", function(){
        if($(this).val() != ""){
            newSite = false;
            createFile = false;
        }else{
            newSite = true;
        }
    });


    function processSiteLanguage(form, lang){
        var langData = {};
        var data = {};
        $.each(form, function(i, v){
            if(this.name == "site_selected_lang") {
                var langInfo = this.value.split("-");
                /**
                 * prepare lang info
                 */
                lang = langInfo[1];

                langData[langInfo[1]] = langInfo[0];
            }else{
                langData[v.name] = v.value;
            }

            data.data = langData;
            data.langDetails = lang;
        });
        return data;
    }

    function processSiteDomain(form){
        var domainData = {};
        $.each(form, function(i, v){
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
                }
            }else if(v.name == "siteCreateModuleName"){
                delete form[i];
                if(newSite) {
                    form.push({'site_name': v.value});
                }
            }
        });
        return form;
    }

    function isFormEmpty(form, currentStepForm){
        var fromInputNames = [];
        $.each(form, function(i, v){
           if(v.value != ""){
               fromInputNames.push(v.name)
           }
        });
        return showFormError(currentStepForm, fromInputNames);
    }

    function showFormError(form, fieldNames){
        var errCtr = 0;
        var curForm = $(form+" input");
        curForm.each(function(){
            if(jQuery.inArray($(this).attr("name"), fieldNames) === -1) {
                // var errSpan = $(this).closest("form").find("span.err_" + $(this).attr("name"));
                // errSpan.html("Error <br/>").removeClass("hidden");
                errCtr++;
            }
        });

        if(errCtr > 0)
            return true;

        return false;
    }

    function openSiteEditTab(name, siteId){
        melisHelper.tabOpen(name, 'fa-globe', siteId+'_id_meliscms_tool_sites_edit_site', 'meliscms_tool_sites_edit_site',  { siteId : siteId }, null, function(){

        });
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
});