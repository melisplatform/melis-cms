$(function () {
    var $body = $('body');
    var siteSelect = '#menuManagerSite';
    var form = '#id_menu_manager_tool_site';
    var tree = $('#mini-template-category-tree');
    var isInitialized = false;

    $body.on('change', siteSelect, function () {
        console.log(typeof(tree.jstree(true).settings));
        if (isInitialized) {
            $('#mini-template-category-tree').jstree(true).settings.core.data.data = [{name : "langlocale", value: 'fr_FR'}, {name:"siteId", value : 2}];
            $('#mini-template-category-tree').jstree(true).refresh();
        } else {
            initCmsMiniTemplateCategoryTree();
            isInitialized = true;
        }
    });

    $body.on('submit', form, function (e) {
        var formData = new FormData(this);

        console.log('submit');

        e.preventDefault();
    });

    function initCmsMiniTemplateCategoryTree () {
        var current_level;

        $body.on("click", "#mini-template-category-tree", function(evt) {
            $("#mini-template-category-tree ul li div").removeClass("jstree-wholerow-clicked");
            evt.stopPropagation();
            evt.preventDefault();
        });

        $('#mini-template-category-tree')
            .on('#mini-template-category-tree changed.jstree', function (e, data) {})
            .on('#mini-template-category-tree refresh.jstree', function (e, data) {})
            .on('#mini-template-category-tree loading.jstree', function (e, data) {})
            .on('#mini-template-category-tree loaded.jstree', function (e, data) {})
            .on('#mini-template-category-tree refresh.jstree', function (e, data) {})
            .on('ready.jstree', function (e, data) {})
            .on('load_node.jstree', function (e, data) {})
            .on('#mini-template-category-tree open_node.jstree', function (e, data) {
                console.log('ki double click');
            })
            .on('#mini-template-category-tree after_open.jstree', function (e, data) {})
            .on("#mini-template-category-tree move_node.jstree", function (e, data) {})
            .jstree({
                "contextmenu" : {

                },
                "core" : {
                    "multiple": false,
                    "check_callback": true,
                    "animation" : 500,
                    "themes": {
                        "name": "proton",
                        "responsive": false
                    },
                    "dblclick_toggle" : false,
                    "data" : {
                        "cache" : false,
                        "url" : "/melis/MelisCmsCategory2/MelisCmsCategoryList/getCategoryTreeView?langlocale="+$("#mini-template-category-tree").data('langlocale'),
                    },
                },
                "types" : {
                    "#" : {
                        "valid_children" : ["category"]
                    },
                    "catalog" : {
                        "valid_children" : ["category"]
                    },
                    "category" : {
                        "valid_children" : ["category"]
                    },
                },
                "plugins": [
                    "contextmenu", // plugin makes it possible to right click nodes and shows a list of configurable actions in a menu.
                    "changed", // Plugins for Change and Click Event
                    "dnd", // Plugins for Drag and Drop
                    "search", // Plugins for Search of the Node(s) of the Tree View
                    "types", // Plugins for Customizing the Nodes
                ]
            });
    }
});