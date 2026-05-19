<?php
/**
 * Класс для работы с календарём проб
 *
 * @Timur Kolomeets 22/03/2023
 */

namespace Tanais\Alter;

use \Bitrix\Main;

Main\Loader::requireModule('crm');

class ProbeCalendar
{
    public static function generateInfoForSoilTable(): array
    {
        $arrEvents = [];
        $events = self::getParamsForTable();

        $arFilter = [
            ["LOGIC" => "OR",
                ["!UF_CRM_641AE8C4902FB" => null], //Дата отбора проб
                ["!UF_CRM_632046BBB65E4" => null],//дата поступления в лабораторию
            ],
            'UF_CRM_LABORATORY' => 802,
        ];

        $arSelect = [
            'ID',
            'TITLE',
            'UF_CRM_641AE8C4902FB', //Дата отбора проб
            'UF_CRM_632046BBB65E4', //дата поступления в лабораторию
        ];

        $resultDealsList = \CCrmDeal::GetListEx([], $arFilter, false, false, $arSelect);
        while ($deal = $resultDealsList->Fetch()) {

            foreach ($events as $code => $event) {
                $i = -1;

                // foreach ($deal[$code] as $date) {
                $i++;
                $arrEvents[] = [
                    'title' => $deal['TITLE'],
                    'start' => date('Y-m-d', strtotime($deal[$code])),
                    'color' => $event['color'],
                    'display' => "list-item",
                    'editable' => true,
                    'classNames' => "lead",
                    'url' => "/crm/deal/details/{$deal['ID']}/",
                    'leadId' => $deal['ID'],
                    'propCode' => $code,
                    'propCodeIndex' => $i
                ];
                //}
            }
        }
        
        return $arrEvents;
    }

    public static function getParamsForTable(): array
    {
        return [
            'UF_CRM_641AE8C4902FB' => [
                'name' => 'Дата отбора проб',
                'color' => '#007dff',
                'prefix' => 'Пробы ',
            ],
            'UF_CRM_632046BBB65E4' => [
                'name' => 'Дата поступления проб в лабораторию',
                'color' => '#3caa3c',
                'prefix' => 'КД ',
            ],
        ];
    }
}