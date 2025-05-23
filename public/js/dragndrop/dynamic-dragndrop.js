$(function () {
	let $document = $(document),
		$body = $("body"),
		$dndButtons = $(".dnd-layout-buttons");

        // .dnd-layout-wrapper
        $body
            .on("mouseenter", ".dnd-layout-wrapper", function (e) {
                // e.stopPropagation();

                $dndButtons.hide();

                $(this).children(".dnd-layout-buttons").show();
            })
            .on("mouseleave", ".dnd-layout-wrapper", function () {
                $(this).children(".dnd-layout-buttons").hide();
            });

        // .dnd-layout-buttons
        $body.on("click", ".dnd-layout-buttons div[data-dnd-tpl]", function () {
            let dndId = $(this).data("dndId");
            let dndTpl = $(this).data("dndTpl");
            let pageId = $(this).data("pageId");
            let melisSite = $(this).data("melisSite");
            var tempLoader =
                '<div id="loader" class="overlay-loader"><img class="loader-icon spinning-cog" src="/MelisCore/assets/images/cog12.svg" data-cog="cog12"></div>';

            $(this).closest(".melis-dragdropzone-container").prepend(tempLoader);

            let dndContainer = $(this).closest(".melis-dragdropzone-container");

            let dndLayout = dndContainer.find(".melis-dragdropzone");
            let dndCtr = dndLayout.length;

            console.log({ dndCtr });

            $.get("/dnd-layout", {
                pageId,
                dndId,
                dndTpl,
                melisSite,
            })
                .done((res) => {
                    if (res.success) {
                        dndContainer.find("#loader").remove();

                        let newLayout = $(res.html);
                        let newLayoutDnd = newLayout.find(".melis-dragdropzone");
                        let newLayoutDndCtr = newLayoutDnd.length;

                        console.log({ newLayoutDndCtr });

                        // if (dndCtr != newLayoutDndCtr) {
                        if (dndCtr == 1) {
                            let dndContent = dndContainer.find(".melis-dragdropzone");

                            $.each(dndContent.children(), (i, v) => {
                                // filter dnd with contents
                                if ($(v).text().trim() !== "") {
                                    // move and append to new dnd layout
                                    $(v).appendTo(newLayout.find(".melis-dragdropzone:first"));
                                }
                            });

                            // replacing new layout
                            $(
                                "body .melis-dragdropzone-container[data-plugin-id='" +
                                    dndId +
                                    "']"
                            ).replaceWith(newLayout);
                        } else {
                            // comparing dnds to the new dnd layout
                            $.each(dndLayout, (i, v) => {
                                // checking if the plugin id not exist in the new layout
                                let pluginExist = newLayout.find(
                                    "div.melis-dragdropzone[data-plugin-id='" +
                                        $(v).data("pluginId") +
                                        "']"
                                );
                                // all plugin no exist in the new layout will append to the last dnd in the new layout
                                if (!pluginExist.length) {
                                    let dndContents = $(v).children();

                                    $.each(dndContents, (di, dv) => {
                                        // filter dnd with contents
                                        if ($(dv).text().trim() !== "") {
                                            // move and append to new dnd layout
                                            $(dv).appendTo(newLayout.find(".melis-dragdropzone:last"));
                                        }
                                    });
                                }
                            });

                            // replacing new layout
                            $(
                                "body .melis-dragdropzone-container[data-plugin-id='" +
                                    dndId +
                                    "']"
                            ).replaceWith(newLayout);
                        }
                        // }

                        melisPluginEdition.moveResponsiveClass();
                        melisPluginEdition.pluginDetector();
                        melisPluginEdition.initResizable();
                        melisDragnDrop.setDragDropZone();

                        // save change to session
                        melisPluginEdition.sendDragnDropList(dndId, pageId);

                        // call popoverInit()
                        popoverInit();
                    }
                })
                .always(() => {
                    dndContainer.find("#loader").remove();
                });
        });

        $body.on("click", ".dnd-plus-button", function() {
            let _this = $(this);
            let pluginId = _this.data("plugin-id");
            let siteModule = _this.data("site-module");
            let parentDND = $(_this).parents(".melis-dragdropzone-container").last();
            let pageId = _this.data("page-id");

            let originalLayout = parentDND.data("layout-template");

            let parent = parentDND.attr("data-plugin-id");
            let sourceId = pluginId;

            // Get the source container wrapper
            let sourceContainer = $('.melis-dragdropzone-container[data-dragdropzone-id="' + sourceId + '"]');
            //update template
            $(sourceContainer).data("layout-template", 'MelisFront/dnd-2-cols-down-tpl');

            if (!sourceContainer.length) {
                console.warn("Source container '" + sourceId + "' not found.");
                return;
            }

            // Ensure .row exists inside sourceContainer
            let row = sourceContainer.children('.row');
            if (!row.length) {
                let existingContent = sourceContainer.children().not('.row');

                // Wrap non-row content if necessary
                if (!existingContent.hasClass('melis-dragdropzone-container')) {

                    if(sourceContainer.find(".melis-dragdropzone-container").length <= 1) {
                        originalLayout = 'MelisFront/dnd-default-tpl';
                    }

                    let wrapper = $('<div class="melis-dragdropzone-container"></div>')
                        .attr({
                            'data-dragdropzone-id': sourceId,
                            'data-plugin-id': sourceId,
                            'data-tag-id': sourceId,
                            'id': sourceId,
                            'data-site-module': siteModule,
                            'data-layout-template': originalLayout
                        });
                    wrapper.append(existingContent);
                    existingContent = wrapper;
                }

                row = $('<div class="row"></div>');
                let col = $('<div class="col-md-12"></div>').append(existingContent);
                row.append(col);
                sourceContainer.append(row);
            }

            // Determine next hierarchical index
            let lastIndex = 0;
            sourceContainer.find('[data-dragdropzone-id]').each(function () {
                let dndId = $(this).attr('data-dragdropzone-id');
                let match = dndId.match(new RegExp('^' + sourceId + '_(\\d+)$'));
                if (match) {
                    lastIndex = Math.max(lastIndex, parseInt(match[1], 10));
                }
            });
            let newIndex = lastIndex + 1;
            let newDNDId = sourceId + '_' + newIndex;

            // Get the last .col-md-12 and clone the wrapper inside it
            let lastCol = row.children('.col-md-12').last();
            let wrapperToClone = lastCol.find('.melis-dragdropzone-container[data-dragdropzone-id="' + sourceId + '"]').first();

            if (!wrapperToClone.length) {
                console.warn("No wrapper with data-dragdropzone-id='" + sourceId + "' found.");
                return;
            }

            // Wrap in a new .col-md-12 and append to the row
            requestDND(newDNDId, row, pageId, parent, sourceId);

            // initialize popover after
            popoverInit();
        });

        /**
         *
         * @param id
         * @param row
         * @param pageId
         * @param parent
         * @param sourceId
         */
        function requestDND(id, row, pageId, parent, sourceId)
        {
            $.ajax({
                'url' : '/dnd-layout',
                'data' : {id : id},
                'type' : 'GET'
            }).done(function(data){
                var newCol = $('<div class="col-md-12"></div>').append($(data['html']));
                row.append(newCol);

                let root = $('[data-dragdropzone-id="'+sourceId+'"]').first();
                updateNestedDragdropIds(root, sourceId);

                // Save the session
                melisPluginEdition.sendDragnDropList(id, pageId, parent);
            });
        }

        /* $body.on("click", ".popover-body .close-btn", function () {
            let $popoverTrigger = $(this)
                .closest(".popover")
                .prevAll('[data-bs-toggle="popover"]')
                .first();
            $popoverTrigger.popover("hide");
        }); */

        function updateNestedDragdropIds($element, baseId) {
            let index = 1;

            // Find only the FIRST nested .melis-dragdropzone-container for each branch
            $element.find('.melis-dragdropzone-container').each(function () {
                const $child = $(this);

                // Skip if it's already been renamed in a deeper recursion
                if ($child.data('renamed')) return;

                const newId = `${baseId}_${index}`;

                // Update all attributes
                $child.attr('data-dragdropzone-id', newId);
                if ($child.attr('data-plugin-id')) $child.attr('data-plugin-id', newId);
                if ($child.attr('data-tag-id')) $child.attr('data-tag-id', newId);
                if ($child.attr('id')) $child.attr('id', newId);

                // Mark as renamed to avoid renaming again in deeper recursions
                $child.data('renamed', true);

                // Recurse on this child to rename its descendants
                updateNestedDragdropIds($child, newId);

                index++;
            });
        }

        function getNextPluginIndex(sourceContainer) {
            let max = 0;
            $(sourceContainer).find('[data-plugin-id]').each(function () {
                let el = $(this);
                if($(this).parent().hasClass('melis-ui-outlined')){
                    let pluginId = el.attr('data-plugin-id');
                    let match = pluginId.match(/^(.*?)(\d+)$/);

                    if(match){
                        var tagId = parseInt(match[2], 10);
                        if(max < tagId){
                            max = tagId;
                        }

                    }
                }
            });
            return max;
        }

        /* function popoverInit() {
            $('[data-bs-toggle="popover"]').each(function () {
                let $trigger = $(this),
                    contentId = $trigger.data("bs-content-id"),
                    content = $("#" + contentId).html();

                $trigger.popover({
                    html: true,
                    sanitize: false,
                    content: content,
                    trigger: "click",
                    container: $trigger.closest(".dnd-layout-buttons"),
                    template: '<div class="popover dnd-layout-buttons-popover" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>',
                });
		});

        popoverInit(); */

        function reInitResize() {
            console.log(`reInitResize() executed!!!`);
            // re init resize
            var $uiOutlined = $("body .melis-dragdropzone .melis-ui-outlined");
                //console.log({uiOutlined});

                $uiOutlined.each(function() {
                    let $outlined = $(this);
                        console.log($outlined.data("uiResizable"));
                        if($outlined.data("uiResizable")) {
                            $outlined.resizable("destroy");
                        }
                });

                console.log(parent.pluginResizable);
                console.log(typeof melisPluginEdition !== "undefined");

                if (parent.pluginResizable == 1 && typeof melisPluginEdition !== "undefined") {
                    melisPluginEdition.initResizable();
                }
        }

        reInitResize();
});
