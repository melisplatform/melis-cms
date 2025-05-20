var melisDynamicDragnDrop = (function($, window) {
    function init() {
        let $iconButtons    = $(".dnd-layout-buttons .column-icon"),
            $dndButtons     = $('.dnd-layout-buttons, .dnd-bottom-buttons'),
            $dndWrapper     = $(".melis-dragdropzone-container .dnd-layout-wrapper");
            
            // .dnd-layout-wrapper
            $dndWrapper
                // mouseenter
                .on("mouseover", function(e) {
                    //e.stopPropagation();

                    $dndButtons.removeClass("show-buttons");

                    $(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").addClass("show-buttons");
                });
                /* .on("mouseout", function() {
                    $(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").removeClass("show-buttons");
                }); */

            // .dnd-layout-buttons
            $.each($iconButtons, function(i, v) {
                let $iconButton = $(v);
                    $iconButton
                        .on("mouseenter", function() {
                            $(this).find(".icon-col-bg").removeClass("bg-white").addClass("bg-red");
                        })
                        .on("mouseleave", function() {
                            $(this).find(".icon-col-bg").addClass("bg-white").removeClass("bg-red");
                        });
            });
    }

    // .dnd-plus-button
    /* $body.on("click", ".dnd-plus-button", function(e) {

    }); */

    return {
        init : init
    };
})(jQuery, window);

$(function() {
    melisDynamicDragnDrop.init();

    let $body = $("body");
    $body.on("click", ".dnd-plus-button", function(){
        let _this = $(this);
        let pluginId = _this.data("pluginid");
        let parentDNDId = _this.closest("melis-dragdropzone-container").last();
        console.log(pluginId);
        console.log(parentDNDId);
    });
});