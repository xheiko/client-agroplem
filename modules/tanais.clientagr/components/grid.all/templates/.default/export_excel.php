<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

use Bitrix\Main\Localization\Loc;


$APPLICATION->RestartBuffer();

header('Content-Description: File Transfer');
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
header('Content-Disposition: attachment; filename="' . $arResult["FILE_EXPORT_NAME"] . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

$userCache = [];
$groupCache = [];
$columnsToIgnore = ['FLAG_COMPLETE', 'RESPONSIBLE_ID', 'CREATED_BY'];
if (!empty($arResult['ROWS_EXPORT'])) {
    $rows = $arResult['ROWS_EXPORT'];
} else {
    $rows = $arResult['ROWS'];
}

//Модификация для Экспорта
// file_put_contents('/home/bitrix/rows.log',var_export($rows,true));
// file_put_contents('/home/bitrix/columns.log',var_export($arResult['COLUMNS'],true));
foreach ($rows as $rowKey=>&$row) {
	foreach ($row['data'] as $columnKey=>&$cell){
		// if ( preg_match('/^[0-9]*[.][0-9]+$/', $cell) ) { //Регулярка на двоичные
		if (is_numeric($cell) ) { //Регулярка на двоичные
			$cell=str_replace(".",",",$cell);
		}
	}
}
?>
<meta http-equiv="Content-type" content="text/html;charset=<? echo LANG_CHARSET ?>"/>
<table border="1">
    <thead>
    <tr>
        <?php foreach ($arResult['COLUMNS'] as $field): ?>
            <th><?= $field["name"] ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>

    <tbody>
    <?php foreach ($rows as $goal): ?>
        <tr>
            <?php foreach ($arResult['COLUMNS'] as $goaltd): ?>
                <?
                echo '<td>' . ($goal["data"][$goaltd["id"]]) . '</td>';
                ?>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
