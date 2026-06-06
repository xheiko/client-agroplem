<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arActivityDescription = array(
	"NAME" => "Получить заместителя по отделу",
	"DESCRIPTION" => "Активити для получения заместителя отдела",
	"TYPE" => "activity",
	"CLASS" => "GetDeputyByDepartment",
	"JSCLASS" => "BizProcActivity",
	'CATEGORY' => [
		'ID'       => 'alter_group',
		'OWN_ID'   => 'alter_group',
		'OWN_NAME' => "Кастомные активити",
	],
	"FILTER" => [
		'INCLUDE' => [
			['crm', 'Bitrix\Crm\Integration\BizProc\Document\Dynamic'],
		]
	],

    "RETURN" => [
        "DeputyUser" => [
            "NAME" => "Заместитель подразделения",
            "TYPE" => "user",
        ],
    ],
);
