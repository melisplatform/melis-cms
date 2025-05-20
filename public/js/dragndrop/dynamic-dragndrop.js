var melisDynamicDragnDrop = (function($, window) {
    function init() {
        let $iconButtons    = $(".dnd-layout-buttons .column-icon"),
            $dndButtons     = $('.dnd-layout-buttons, .dnd-bottom-buttons'),
            $dndWrapper     = $(".dnd-layout-wrapper");
            
            // .dnd-layout-wrapper, mouseenter & mouseleave, mouseover & mouseout
            $dndWrapper
                .on("mouseenter", function(e) {
                    //e.stopPropagation();

                    $dndButtons.removeClass("show-buttons");

                    $(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").addClass("show-buttons");
                    /* $(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").fadeIn("400", "slow", function() {
                        $(this).css("opacity", 1);
                    }); */
                })
                .on("mouseleave", function() {
                    $(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").removeClass("show-buttons");
                    /* $(this).children(".dnd-layout-buttons, .dnd-bottom-buttons").fadeOut("400", "slow", function() {
                        $(this).css("opacity", 0);
                    }); */
                });

            // .dnd-layout-buttons
            $.each($iconButtons, function(i, v) {
                let $iconButton = $(v);
                    $iconButton
                        .on("mouseover", function() {
                            $(this).find(".icon-col-bg").removeClass("bg-white").addClass("bg-red");
                        })
                        .on("mouseout", function() {
                            $(this).find(".icon-col-bg").addClass("bg-white").removeClass("bg-red");
                        });
            });
    }

    return {
        init : init
    };
})(jQuery, window);

$(function() {
    melisDynamicDragnDrop.init();

    let $body = $("body");
        $body.on("click", ".dnd-plus-button", function() {          
            let _this = $(this);
            let pluginId = _this.data("pluginId");
            let parentDNDId = _this.closest("melis-dragdropzone-container").last();
        });

        $body.on("click", ".dnd-layout-buttons div[data-dnd-tpl]", function() {
            // initialize dynamic dragndrop
            melisDynamicDragnDrop.init();

            let dndId = $(this).data("dndId");
            let dndTpl = $(this).data("dndTpl");
            let pageId = $(this).data("pageId");
            let melisSite = $(this).data("melisSite");
            /* console.log({dndId});
            console.log({dndTpl});
            console.log({pageId});
            console.log({melisSite}); */
            var tempLoader = '<div id="loader" class="overlay-loader"><img class="loader-icon spinning-cog" src="/MelisCore/assets/images/cog12.svg" data-cog="cog12"></div>';
            
            $(this).closest(".melis-dragdropzone-container").prepend(tempLoader);

            $.get("/dnd-layout", {
                pageId,
                dndId,
                dndTpl,
                melisSite
            })
            .done((res) => {
                //console.log({res});
                if (res.success) {
                    $(this).find("#loader").remove();

                    $(".melis-dragdropzone-container[data-plugin-id='" + dndId + "']").replaceWith(res.html);

                    if (res.pluginsInitFiles) {

                        setTimeout(() => {
                            $.each(res.pluginsInitFiles, (i, v) => {
                                // reinitialize plugins
                                // melisPluginEdition.processPluginResources(v, i);
                            });

                            melisPluginEdition.moveResponsiveClass();
                            melisPluginEdition.pluginDetector();
                            melisPluginEdition.initResizable();

                            melisPluginEdition.sendDragnDropList(dndId, pageId);

                            // TODO 
                            melistagHTML_init();

                        }, 1000)
                    }

                }
            });
        });
});