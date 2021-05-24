var melisCmsPageTreeviewCheckboxes = (function() {
    // variable declaration
    var $body = $("body");

        // methods / functions

		// events
        $body.on("click", "#page-tree-view-checkbox", function() {
            var $this = $(this),
                $tree = $.ui.fancytree.getTree("#id-mod-menu-dynatree"),
                $fancytree_checkboxes = $("#id-mod-menu-dynatree").find(".fancytree-checkbox");

                $this.toggleClass("selected");

                // hidden by default in fancyTreeInit.js
                if ( $this.hasClass("selected") ) {
                    $fancytree_checkboxes.fadeIn();
                }
                else {
                    $fancytree_checkboxes.fadeOut();
                }
        });
})();