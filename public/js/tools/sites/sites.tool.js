$(document).ready(function() {
    $body = $("body");
    var modalUrl = "/melis/MelisCms/Sites/renderToolSitesModalContainer";

    $body.on('click', "#btn-new-meliscms-tool-sites", function () {
        melisCoreTool.pending("#btn-new-meliscms-tool-sites");
        melisHelper.createModal('id_meliscms_tool_sites_modal_container','meliscms_tool_sites_modal_add_content',true,[],modalUrl,function () {
            melisCoreTool.done("#btn-new-meliscms-tool-sites");
        });
    });

    /**
     * This will open a new tab when editing a site
     */
    $body.on("click", ".btnEditSites", function(){
        var tableId = $(this).closest('tr').attr('id');
        var name = $(this).closest('tr').find("td:nth-child(2)").text();
        melisHelper.tabOpen(name, 'fa-globe', tableId+'_id_meliscms_tool_sites_edit_site', 'meliscms_tool_sites_edit_site',  { siteId : tableId }, null, function(){

        });
    });


    var owlStep = null;
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
                    $("#btn-prev-step").hide();
                }else{
                    $("#btn-prev-step").show();
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
        if(owlStep != null) {
            owlStep.trigger('owl.next');
        }
    });

    $body.on("click", "#btn-prev-step", function(e){
        if(owlStep !== null)
            owlStep.trigger('owl.prev');
    });

    /**
     * check step
     * @param step
     */
    function checkStep(step)
    {
        //check if multi language
        var isMultiLang = $('#is_multi_language').bootstrapSwitch('status');
        //process step
        switch(step){
            case "step_1":
                break;
            case "step_2":
                if(isMultiLang){
                    $(".step2-forms .sites_step2-multi-language").show();
                    $(".step2-forms .sites_step2-single-language").hide();
                }else{
                    $(".step2-forms .sites_step2-single-language").show();
                    $(".step2-forms .sites_step2-multi-language").hide();
                }
                break;
            case "step_3":
                if(isMultiLang){
                    var multiLangForm = $("#step2form-multi_language");
                    var multiLangFormData = multiLangForm.serializeArray();
                    var multiDomainsContainer = $(".sites_step3-multi-domain #multi-domains_container");
                    var div = $("<div/>",{
                        class: "form-group"
                    });
                    multiDomainsContainer.empty();
                    $.each(multiLangFormData, function(){
                        if(this.name == "site_selected_languages"){
                            var langData = this.value.split("_");
                            var label = $("<label/>").text(langData[1]);
                            div.append(label);
                            var input = $("<input/>",{
                                class: "form-control"
                            }).attr("data-langId", langData[0]);
                            div.append(input);
                            multiDomainsContainer.append(div);
                        }else if(this.name == "sites_url_setting"){

                        }
                    });
                }else{

                }
                break;
            case "step_4":
                $("#btn-next-step").show();
                $("#btn-finish-step").hide();
                break;
            case "step_5":
                $("#btn-finish-step").show();
                $("#btn-next-step").hide();
                break;
            default:
                break;
        }
    }
});