<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define('BX_NO_ACCELERATOR_RESET', true);
define('CHK_EVENT', true);
define('BX_WITH_ON_AFTER_EPILOG', true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
\Bitrix\Main\Loader::includeModule('crm');

echo '<html><head>';
echo '<link rel="stylesheet" href="contract_selector.css">';
echo '<link rel="stylesheet" href="/local/include/fontawesome/css/fontawesome.min.css">';
echo '<link rel="stylesheet" href="/local/include/fontawesome/css/solid.css">';
echo "<style>@import url('https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,500;0,700;1,400&display=swap');</style>";
echo '</head><body>';

if (CModule::IncludeModule("crm")) {


    $contractSingdate = 'UF_CRM_6_CONTRACT_SIGNDATE';
    $comment = 'UF_CRM_6_COMMENT';

}
?>
<style>


</style>

<script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk="
        crossorigin="anonymous"></script>
<script>
    function ajax_contract(deal_id, contract_id) {
        // console.log(deal_id,contract_id);
        $.ajax({
            url: "ajax_contract.php",
            type: 'POST',
            async: false,
            data: {deal_id: deal_id, contract_id: contract_id},
            success: function (data) {
                console.log(data);
                // console.log($('#contract'+contract_id));
                // $('#contract'+contract_id).text("✔ Cделка обновлена. Чтобы обновилась карточка сделки - нужно обновить страницу CTRL-R");
                // $('#contract'+contract_id).css({'background-color': '#ffd300'});
                // $("[id*=contract]").hide();
                // $('#contract'+contract_id).show();
                $('#tabletdcontarct').html('<section class="pt-10 pb-10"><h5 class="font__family-montserrat font__weight-light text-uppercase font__size-18 text-blue brk-library-rendered" data-brk-library="component__title"> ✔ Договор с клиентом записан в сделку </h5><hr class="divider wow zoomIn brk-library-rendered" data-brk-library="component__title" style="visibility: visible; animation-name: zoomIn;"><p>Чтобы обновилась карточка сделки - нужно обновить страницу CTRL-R.</p></section>');
                top.location.reload();

            },
            error: function (error) {
                console.log(error.status);
                console.log(error.statusText);
                console.log(error.responseText);
            }
        });
    }

    function ajax_price(deal_id, price_id) {
        // console.log(deal_id,contract_id);
        $.ajax({
            url: "ajax_price.php",
            type: 'POST',
            async: false,
            data: {deal_id: deal_id, price_id: price_id},
            success: function (data) {
                // console.log(data);
                // console.log($('#contract'+contract_id));
                // $('#price'+price_id).text("✔ Cделка обновлена. Чтобы обновилась карточка сделки - нужно обновить страницу CTRL-R");
                // $('#price'+price_id).css({'background-color': '#ffd300'});
                // $("[id*=price]").hide();
                // $('#price'+price_id).show();
                $('#tabletdprice').html('<section class="pt-10 pb-10"><h5 class="font__family-montserrat font__weight-light text-uppercase font__size-18 text-blue brk-library-rendered" data-brk-library="component__title"> ✔ Цены выбраны и записаны в сделку </h5><hr class="divider wow zoomIn brk-library-rendered" data-brk-library="component__title" style="visibility: visible; animation-name: zoomIn;"><p>Чтобы обновилась карточка сделки - нужно обновить страницу CTRL-R.</p></section>');
                top.location.reload();
            },
            error: function (error) {
                console.log(error.status);
                console.log(error.statusText);
                console.log(error.responseText);
            }
        });
    }
</script>
<?
// echo $_GET['APP_SID']."<br>";
// echo 'dcef24e7704b62b967d37f5908e06ada';

if ($_REQUEST['APP_SID'] == '') {
    echo "<h1>Неверный APP_SID</h1>";
    die;
}
if ($_REQUEST['PLACEMENT'] == 'CRM_DEAL_DETAIL_TAB') {
    $deal_id = json_decode($_REQUEST['PLACEMENT_OPTIONS'], true)['ID'];
    // $deal_id=5802; // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
    $arSort = ['ID' => 'DESC'];
    $arFilter = ['ID' => $deal_id];
    $res = \CCrmDeal::GetList($arSort, $arFilter);
    if (($deal_id) && ($deal = $res->fetch())) {
        if ($deal['COMPANY_ID']) {
            $arFilterContracts['COMPANY_ID'] = $deal['COMPANY_ID'];
        } elseif ($deal['CONTACT_ID']) {
            $arFilterContracts['CONTACT_ID'] = $deal['CONTACT_ID'];
        }

        // echo $company_id."<br>";
        global $AGROPLEM_UF_CODES, $AGROPLEM_SERVER, $AGROPLEM_SMART_ID;
        $entityTypeId = 1050;     //Смарт-процесс Договоры с клиентами
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
        $arFilterContracts['!STAGE_ID'] = 'DT1050_19:FAIL';
        $sp_contracts = $factory->getItems(['select' => ['*'], 'filter' => $arFilterContracts, 'order' => [$contractSingdate => 'DESC']]);

        $contracts = [];
        foreach ($sp_contracts as $sp_contract) {
            $contracts[] = $sp_contract;
        }


//--------------------------------------------------------------------------------------------------------------

        // echo '<table ><tr><td class="tabletd" id="tabletdcontarct">';

        echo '<section class="pt-10 pb-10">';
        // echo '<h5 class="font__family-montserrat font__weight-light text-uppercase font__size-18 text-blue brk-library-rendered" data-brk-library="component__title">Договор с клиентом</h5>';
        // echo '<hr class="divider wow zoomIn brk-library-rendered" data-brk-library="component__title" style="visibility: visible; animation-name: zoomIn;">';

        echo '<div class="container">';
        echo '<div class="panel__wrapper-icon mb-100 brk-library-rendered" data-brk-library="component__panel">';
        echo '<ul class="panel__list">';
        foreach ($contracts as $contract) {
            $stage_color = $factory->getStage($contract->get('STAGE_ID'))->get('COLOR');
            ?>
            <li>
            <table>
                <tr>
                    <td class="table2ico">
                        <i class="fa-sharp fa-solid fa-file-contract" style="color:<?= $stage_color ?>;"></i>
                    </td>
                    <td>
                        <div class="line_text"
                             onclick="ajax_contract('<?= $deal_id ?>','<?= $contract->get('ID') ?>');">
                            <b><?= $contract->get('TITLE') ?></b><br>
                            <i><?= "" . $factory->getStage($contract->get('STAGE_ID'))->get('NAME') . "" ?>&nbsp;&nbsp;&nbsp;

                                <?= " (изменен " . FormatDate($contract->get('UPDATED_TIME'), 'X') ?>)</i><br>
                            <i><?= "" . $contract->get($comment) . "" ?>&nbsp;&nbsp;&nbsp;
                        </div>
                    </td>
                </tr>
            </table>
            </li><?
        }
        echo '</ul">';
        echo '</div></div>';
        echo '</section>';

        // echo '</td><td class="tabletd" id="tabletdprice">';
        // echo '<h1>Выберите цены <a href="/crm/company/details/'.$company_id.'/" target="_parent">клиента</a></h1>';


        echo "</td></tr></table>";

    } else {

        echo "<h1>У клиента нет ни одного договора</h1>";
    }


}

echo "</body></html>";


?>

