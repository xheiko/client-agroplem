console.log('tanais.alter/js/crm/copyprice loaded');
BX.ready(function () {
});

function copyprice() {
    BX.ajax.runAction('tanais:alter.crm.copyprice.copyprice', {}).then(
        function (response) {
            console.log(response);
        },
    );
}