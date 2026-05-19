<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
define('C_REST_WEB_HOOK_URL', 'https://agroplemaobitrix.agrochemist.ru/rest/1/9jhy8832r25ty54m/');
// define('C_REST_WEB_HOOK_URL', 'https://bitrix.agroplem.ru/rest/1/y28ul9dnvqsh57mt/');
require($_SERVER["DOCUMENT_ROOT"] . "/local/modules/tanais.alter/lib/crest/crest.php");
// $dealData
// $result['next']=0;
$dealData=[];
do {
    $result = \CRest::call(
        'crm.deal.list',
        [
            'select' => ["ID"],
            'order' => ["ID" => "ASC"],
            'filter' => [],
            'start' => intval($result['next'])
        ]
    );
    $dealData=array_merge($dealData,$result['result']);
} while ($result['next'] < $result['total']);
echo "<pre>" . var_export($dealData, true) . "</pre><br>";


require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
