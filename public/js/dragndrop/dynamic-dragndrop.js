var melisDynamicDragnDrop = (function($, window) {
    let $body = $("body"),
        $buttons = $(".dnd-layout-buttons .column-icon");

        // .dnd-layout-buttons
        $.each($buttons, function(i, v) {
            let $this = $(v);
            
                $this
                    .on("mouseenter", function() {
                        $(this).find(".icon-col-bg").removeClass("bg-white").addClass("bg-red");
                    })
                    .on("mouseleave", function() {
                        $(this).find(".icon-col-bg").addClass("bg-white").removeClass("bg-red");
                    });
        });

        // .dnd-plus-buttons
        
})(jQuery, window);