var melisDynamicDragnDrop = (function($, window) {
    function init() {
        let $iconButtons    = $(".dnd-layout-buttons .column-icon"),
            $dndButtons     = $('.dnd-layout-buttons, .dnd-bottom-buttons'),
            $dndWrapper     = $(".melis-dragdropzone-container .dnd-layout-wrapper");
            
            // .dnd-layout-wrapper, mouseenter & mouseleave, mouseover & mouseout
            $dndWrapper
                .on("mouseenter", function(e) {
                    //e.stopPropagation();

                    $dndButtons.removeClass("show-buttons");

                    $(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").addClass("show-buttons");
                })
                .on("mouseleave", function() {
                    $(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").removeClass("show-buttons");
                });

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
        $body.on("click", ".dnd-plus-button", function() {
            console.log(`.dnd-plus-button clicked!!!`);
            let _this = $(this);
            let pluginId = _this.data("pluginId");
            let parentDNDId = _this.closest("melis-dragdropzone-container").last();
            console.log(pluginId);
            console.log(parentDNDId);
        });
});