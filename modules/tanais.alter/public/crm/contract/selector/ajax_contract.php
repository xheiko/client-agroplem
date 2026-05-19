<?
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_CHECK", true);
define('PUBLIC_AJAX_MODE', true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$_SESSION["SESS_SHOW_INCLUDE_TIME_EXEC"]="N";
$APPLICATION->ShowIncludeStat = false;
//--------------------------------------------------------------------------------------
$deal_id=null;
$contract_id=null;
if (is_numeric($_REQUEST['deal_id'])) 	  $deal_id=$_REQUEST['deal_id'];
if (is_numeric($_REQUEST['contract_id'])) $contract_id=$_REQUEST['contract_id'];
//--------------------------------------------------------------------------------------
\Bitrix\Main\Loader::includeModule('crm');
// $list = [];

//Получаем Лаборатории
// $arSort   = ['ID'  => 'DESC'];
// $arFilter = ['!ID' => 0];
// $arSelect = ['ID', 'TITLE'];

// $res = \CCrmDeal::GetList($arSort, $arFilter, $arSelect);
// while ($row  = $res->fetch()) {
// echo ""
// $list[] = $row;
// }

global $USER;
if ( ($USER->IsAuthorized()) && $deal_id && $contract_id ) {
    $arFields=['UF_CRM_CLIENT_CONTRACT'=>$contract_id];
    $CCrmDeal = new \CCrmDeal();
    $res=$CCrmDeal->update($deal_id,$arFields);
    echo json_encode($res);
} else
    echo json_encode(false);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>