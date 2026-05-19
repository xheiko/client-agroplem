(function () {
    if (window.__wdrInstanceCreated) return;
    window.__wdrInstanceCreated = true;

    window.addEventListener('load', function () {
        const pivot = new WebDataRocks({
            container: "#wdr-component",
            beforetoolbarcreated: customizeToolbar,
            toolbar: true,
            report: {
                "dataSource": {
                    "data": window.CLIENTS_REPORT_DATA || []
                },
                "slice": {
                    "reportFilters": [
                        {
                            "uniqueName": "Регион"
                        },
                        {
                            "uniqueName": "Менеджер по продажам"
                        },
                    ],
                    "rows": [
                        {
                            "uniqueName": "Регионы"
                        }
                    ],
                    "columns": [
                        {
                            "uniqueName": "Measures"
                        }
                    ],
                    "measures": [
                        {
                            "uniqueName": "Количество сделок",
                            "aggregation": "sum"
                        },
                        {
                            "uniqueName": "Сумма сделок",
                            "aggregation": "sum"
                        },
                        {
                            "uniqueName": "Уникальные регионы",
                            "aggregation": "distinctcount",
                            "caption": "Уникальные регионы"
                        },
                        {
                            "uniqueName": "Компания",
                            "aggregation": "distinctcount",
                            "caption": "Уникальные компании"
                        }
                    ]
                }
            },
            global: {
                // replace this path with the path to your own translated file
                localization: "/local/modules/tanais.alter/js/webdata/ru.json"
            }
        });

        pivot.on('reportcomplete', function () {
            pivot.expandAllData();
        });
    });

    function customizeToolbar(toolbar) {
        let tabs = toolbar.getTabs(); // get all tabs from the toolbar
        toolbar.getTabs = function () {
            delete tabs[0]; // delete the first tab
            delete tabs[1]; // delete the first tab
            delete tabs[2]; // delete the first tab
            delete tabs[7]; // delete the first tab
            delete tabs[4]; // delete the first tab
            delete tabs[6]; // delete the first tab
            delete tabs[3]['menu'][1];
            delete tabs[3]['menu'][3]
            return tabs;
        }
    }

    BX.ready(function () {
        BX.bindDelegate(
            document.body, 'click', {className: 'main-ui-filter-find'},
            function (e) {
                if (!e) {
                    e = window.event;
                }

                function reload() {
                    location.reload();
                }

                setTimeout(reload, 1000);
                return BX.PreventDefault(e);
            }
        );
    });
    

})();