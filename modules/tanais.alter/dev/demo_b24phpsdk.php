<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
// https://nikaverro.ru/blog/bitrix/b24phpsdk-vmesto-crest/
// https://github.com/bitrix24/b24phpsdk
echo "Hello!";


$agroplemAO = \Bitrix24\SDK\Services\ServiceBuilderFactory::createServiceBuilderFromWebhook('https://agroplemaobitrix.agrochemist.ru/rest/1/9jhy8832r25ty54m/');
// $dealData = $agroplemAO->core->call('crm.deal.list', [
//     'order' => ["ID" => "ASC"],
//     'filter' => [">ID" => 0],
//     'select' => ['ID', 'ORIGIN_ID'],
// ])->getResponseData()->getResult();
// echo "<pre>" . var_export($dealData, true) . "</pre><br>";

// $batch=\Bitrix24\SDK\Services\CRM\Deal\Service\Batch::add($agroplemAO->getCRMScope()->deal()->list([],[],["ID"]));
// d($batch);
// $list = $agroplemAO->getCRMScope()->deal()->list([],[],["ID"]);
// $deals = $list ->getDeals();

// foreach ($deals as $deal)
// {
    // echo $deal->ID."<br>";
// }

// $debug = $agroplemAO->getCRMScope()->deal()->batch->list([],[],["ID"])->getReturn();
$debug = get_class_methods($agroplemAO->getCRMScope()->deal()->batch->list([],[],["ID"]));
// $debug2 = get_object_vars($agroplemAO->getCRMScope()->deal()->batch);
echo "<pre>" . var_export($debug, true) . "</pre><br>";
// echo "<pre>" . var_export($debug2, true) . "</pre><br>";
// $batch = $n
// $debug2 = Bitrix24\SDK\Services\CRM\Deal\Service\Batch::list([
//         'order' => ["ID" => "ASC"],
//         'filter' => [">ID" => 0],
//         'select' => ['ID', 'ORIGIN_ID'],
//     ]);

// echo "<pre>" . var_export($agroplemAO->getCRMScope()->deal(), true) . "</pre><br>";
// echo "<pre>" . var_export($debug2, true) . "</pre><br>";

// var_dump($agroplemAO->getCRMScope()->deal()->list([], [], ['ID']));
// Bitrix24\SDK\Services\CRM\Deal\Service\Batch::list

// echo "<pre>" . var_export($agroplemAO->getCRMScope()->deal()->list([], [], ['ID']), true) . "</pre><br>";

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
