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
        melisHelper.tabOpen(name, 'fa-user', tableId+'_id_meliscms_tool_sites_edit_site', 'meliscms_tool_sites_edit_site',  { siteId : tableId }, null, function(){

        });
    });

    window.createSitesModalCallback = function () {
        var   $slideGroup = $('.slide-group');
        var   current = 0;
        var   $slide = $('.slide');
        var   slidesTotal = $slide.length;

        var updateIndex = function(currentSlide) {
            current = currentSlide;

            transition(current);
        };

        var transition = function(slidePosition) {
            var slidePositionNew = (slidePosition ) * 500;
            $slideGroup.animate({
                'left': '-' + slidePositionNew + 'px'
            });
        };

        $("#btn-prev-create-meliscms-tool-sites").on("click", function () {
            var index = current - 1;
            current > 0 ? updateIndex(index) : updateIndex(current);
        });
        $("#btn-next-create-meliscms-tool-sites").on("click", function () {
            var index = current + 1;
            current < (slidesTotal - 1) ? updateIndex(index) : updateIndex(current);
        });

    }
});