console.log('JS extension tanais.alter/js/crm/productcatalog/priceListButton/  loaded')
// const urlParams = new URLSearchParams(window.location.search);
BX.ready(
    function () {
        const domain = window.location.hostname;

        const url = new URL(window.location.href);
        const sectionId = url.searchParams.get("SECTION_ID");

        const dynamic_menu = document.querySelector('[class*="ui-toolbar-filter-box"]')
        const path = window.location.pathname;
        if (dynamic_menu) {
            var button = new BX.UI.Button({
                text: "Прайс лист",
                color: BX.UI.Button.Color.SUCCESS,
                onclick: function (button, event) {
                    window.open("https://" + domain + "/local/modules/tanais.alter/public/export/price_list.php?SECTION_ID=" + sectionId);
                }
            });
            button.setActive(true);
            button.renderTo(dynamic_menu);
            const buttonElement = dynamic_menu.querySelector('.ui-btn');
            buttonElement.style.margin = '12px';
        }
    }
);


