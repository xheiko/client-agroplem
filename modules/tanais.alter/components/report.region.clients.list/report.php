<?php

namespace Tanais\Alter;

use \Bitrix\Crm\Service;


\Bitrix\Main\Loader::requireModule('crm');

class Report
{

    public static function getInfoDeal($dealFilter): array
    {
        $arInfo = [];
        $arInfo = \Bitrix\Crm\DealTable::getList([
            'select' => ['ID', 'COMPANY_ID', 'UF_CRM_LABORATORY', 'UF_CRM_1636553061', 'UF_CRM_SUM_DEAL_FOR_REPORT', 'CONTACT_ID'],
            'filter' => $dealFilter,
        ])->fetchAll();
        return $arInfo;
    }

    public static function getLaboratory(): array
    {
        $labs = [];
        $rs = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => '17', '!ID' => 810],
            false, false,
            ['ID', 'NAME']
        );
        while ($ar = $rs->Fetch()) {
            $labs[$ar['ID']] = $ar['NAME'];
        }

        return $labs;
    }


    public static function getRegion(): array
    {
        $arRegions = [];
        $rs = \CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => '16',],
            false, false,
            ['ID', 'NAME']
        );
        while ($ar = $rs->Fetch()) {
            $arRegions[$ar['ID']] = $ar['NAME'];
        }
        return $arRegions;
    }

    public static function getUsers(): array
    {
        $arUsersInfo = [];
        $users = \Bitrix\Main\UserTable::getList([
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'],
            'filter' => []
        ])->fetchAll();
        foreach ($users as $user) {
            $arUsersInfo[$user['ID']] = trim("{$user['LAST_NAME']} {$user['NAME']} {$user['SECOND_NAME']}");
        }
        return $arUsersInfo;
    }

    public static function getReportList(): array
    {
        $list_id = 'custom_grid_labs';
        $filterOption = new \Bitrix\Main\UI\Filter\Options($list_id);;
        $filterData = $filterOption->getFilter([]);

        $arFilterDeals = [];
        if (!empty($filterData['DATE_UPD_from'])) {
            $arFilterDeals['>=UF_CRM_1636553061'] = $filterData['DATE_UPD_from'];
            $arFilterDeals['<=UF_CRM_1636553061'] = $filterData['DATE_UPD_to'];
        }
        if (!empty($filterData['CRM_LABORATORY'])) {
            $arFilterDeals['UF_CRM_LABORATORY'] = $filterData['CRM_LABORATORY'];
        }

        $arLabs = self::getLaboratory();
        $arRegions = self::getRegion();
        $arUsersInfo = self::getUsers();

        $ui_filter = [
            ['id' => 'DATE_UPD', 'name' => 'Дата фактического выставления УПД', 'type' => 'date', 'default' => true],
            ['id' => 'CRM_LABORATORY', 'name' => 'Лаборатория', 'type' => 'list', 'items' => $arLabs],
        ];

        \Bitrix\UI\Toolbar\Facade\Toolbar::addFilter([
            'FILTER_ID' => $list_id,
            'GRID_ID' => $list_id,
            'FILTER' => $ui_filter,
            'ENABLE_LIVE_SEARCH' => true,
            'ENABLE_LABEL' => true
        ]);

        $lists = [
            [
                'Category' => ['type' => 'level', 'hierarchy' => 'Регионы'],
                'Company' => ['type' => 'level', 'hierarchy' => 'Регионы', 'level' => 'Компания', 'parent' => 'Category'],
                'Компания' => ['type' => 'string'],
                'Количество сделок' => ['type' => 'number'],
                'Сумма сделок' => ['type' => 'number'],
                'Менеджер по продажам' => ['type' => 'string'],
                'Регион' => ['type' => 'string'],
                'Уникальные регионы' => ['type' => 'string'],
                'Лаборатория' => ['type' => 'string'],
            ]
        ];

        // Загружаем все компании
        $companies = \Bitrix\Crm\CompanyTable::getList([
            'select' => ['ID', 'TITLE', 'UF_CRM_REGION', 'UF_CRM_SALESMANAGER'],
            'filter' => [],
        ])->fetchAll();

        $companyIds = array_column($companies, 'ID');

        // Получаем сделки по всем компаниям за один запрос
        $dealFilterCompany = array_merge($arFilterDeals, [
            '!UF_CRM_1636553061' => null,
            '!COMPANY_ID' => false,
            'COMPANY_ID' => $companyIds,
        ]);

        $deals = self::getInfoDeal($dealFilterCompany);

        // Группируем сделки по COMPANY_ID
        $dealMap = [];
        foreach ($deals as $deal) {
            $companyId = $deal['COMPANY_ID'];
            $dealMap[$companyId]['COUNT_DEALS']++;
            $dealMap[$companyId]['AMMOUNT_DEALS'] += (int)$deal['UF_CRM_SUM_DEAL_FOR_REPORT'];
            $dealMap[$companyId]['DATE_UPD'] = $deal['UF_CRM_1636553061'];
            $dealMap[$companyId]['LAB'] = $deal['UF_CRM_LABORATORY'];
        }

        foreach ($companies as $company) {
            $companyId = $company['ID'];
            if (empty($dealMap[$companyId])) continue;

            $lists[] = [
                'Category' => $arRegions[$company['UF_CRM_REGION']] ?? '',
                'Company' => $company['TITLE'],
                'Компания' => $company['TITLE'],
                'Менеджер по продажам' => $arUsersInfo[$company['UF_CRM_SALESMANAGER']] ?? '',
                'Количество сделок' => $dealMap[$companyId]['COUNT_DEALS'],
                'Сумма сделок' => $dealMap[$companyId]['AMMOUNT_DEALS'],
                'Регион' => $arRegions[$company['UF_CRM_REGION']] ?? '',
                'Уникальные регионы' => $arRegions[$contact['UF_CRM_REGION']] ?? '',
                'Лаборатория' => $arLabs[$dealMap[$companyId]['LAB']] ?? '',
                'Дата выставления УПД' => $dealMap[$companyId]['DATE_UPD'],
            ];
        }

        $contacts = \Bitrix\Crm\ContactTable::getList([
            'select' => ['ID', 'FULL_NAME', 'UF_CRM_REGION'],
            'filter' => ['!UF_CRM_REGION' => null],
        ])->fetchAll();

        $contactsIds = array_column($contacts, 'ID');

        $dealFilterContact = array_merge($arFilterDeals, [
            '!UF_CRM_1636553061' => null,
            'COMPANY_ID' => null,
            'CONTACT_ID' => $contactsIds,
        ]);

        $deals = self::getInfoDeal($dealFilterContact);

        // Группируем сделки по CONTACT_ID
        $dealMap = [];
        foreach ($deals as $deal) {

            $contactId = $deal['CONTACT_ID'];
            $dealMap[$contactId]['COUNT_DEALS']++;
            $dealMap[$contactId]['AMMOUNT_DEALS'] += (int)$deal['UF_CRM_SUM_DEAL_FOR_REPORT'];
            $dealMap[$contactId]['DATE_UPD'] = $deal['UF_CRM_1636553061'];
            $dealMap[$contactId]['LAB'] = $deal['UF_CRM_LABORATORY'];
        }

        foreach ($contacts as $contact) {
            $contactId = $contact['ID'];
            if (empty($dealMap[$contactId])) continue;

            $lists[] = [
                'Category' => $arRegions[$contact['UF_CRM_REGION']] ?? '',
                'Company' => $contact['FULL_NAME'],
                'Компания' => $contact['FULL_NAME'],
                'Менеджер по продажам' => $arUsersInfo[$contact['UF_CRM_SALESMANAGER']] ?? '',
                'Количество сделок' => $dealMap[$contactId]['COUNT_DEALS'],
                'Сумма сделок' => $dealMap[$contactId]['AMMOUNT_DEALS'],
                'Регион' => $arRegions[$contact['UF_CRM_REGION']] ?? '',
                'Уникальные регионы' => $arRegions[$contact['UF_CRM_REGION']] ?? '',
                'Лаборатория' => $arLabs[$dealMap[$contactId]['LAB']] ?? '',
                'Дата выставления УПД' => $dealMap[$contactId]['DATE_UPD'],
            ];
        }

        $jsonData = json_encode($lists);

        return [
            'DATA' => $jsonData,
        ];
    }
}