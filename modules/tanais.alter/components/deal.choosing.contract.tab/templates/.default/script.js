console.log('Файл подключен');

window.bindContractToDeal = function(dealId, contractId)
{
    BX.ajax.runComponentAction(
        'tanais.alter:deal.choosing.contract.tab', 'bind',
        {
            mode: 'ajax',
            data: { dealId: dealId, contractId: contractId }
        }
    ).then(function(response){
        if (response.data.success)
        {
            window.location.reload();
        }
        else
        {
            alert('Ошибка: ' + (response.data.error || 'неизвестная'));
        }
    }).catch(function(error){
        console.error(error);
        alert('AJAX-ошибка, см. консоль');
    });
};

