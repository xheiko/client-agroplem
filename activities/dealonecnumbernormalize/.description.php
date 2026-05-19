<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arActivityDescription = array(
	"NAME" => "Формат значения поля Номер 1С",
	"DESCRIPTION" => "Изменяет формат поля Номер сделки 1С, чтобы было удобно искать сделку в 1С",
	"TYPE" => "activity",
	"CLASS" => "DealOneCNumberNormalize",
	"JSCLASS" => "BizProcActivity",
	'CATEGORY' => [
		'ID'       => 'alter_group',
		'OWN_ID'   => 'alter_group',
		'OWN_NAME' => "Кастомные активити",
	],
	//Данные доступны для использования далее в шаблоне
	// "RETURN" => [
	// 	"ResultStr" => [
	// 		"NAME" => "Вывод результатов активити AltaTest",
	// 		"TYPE" => "string",
	// 	],
	// ],
	//Ограничения использования
	"FILTER" => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
		]
	],
);
