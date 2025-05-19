var melisDynamicDragnDrop = (function($, window) {
    let $body           = $("body"),
        $iconButtons    = $(".dnd-layout-buttons .column-icon"),
        $dndWrapper     = $(".melis-dragdropzone-container .dnd-layout-wrapper"),
        $dndButtons     = $('.dnd-layout-buttons, .dnd-bottom-buttons'),
        $dndzoneContainer = $(".body_wrapper .melis-dragdropzone-container");

        // .dnd-layout-wrapper
        /* $dndWrapper
            .on("mouseenter", function(e) {
                e.stopPropagation();

                $dndButtons.removeClass("show-buttons");

                $(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").addClass("show-buttons");
            })
            .on("mouseleave", function() {
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
})(jQuery, window);