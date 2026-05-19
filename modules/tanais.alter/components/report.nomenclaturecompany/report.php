<?php

namespace Tanais\Alter;

use Bitrix\Crm\DealTable;
use \Bitrix\Crm;
use \Bitrix\Crm\Service;

\Bitrix\Main\Loader::requireModule('crm');

class Report
{
    protected $moduleId;
    protected $currentUser;
    protected $cacheFileName;
    protected $cellFormat = [];

    public function __construct()
    {
        $this->currentUser = \Bitrix\Main\Engine\CurrentUser::get();
        $this->moduleId = 'tanais.alter';
        $this->cacheFileName = sys_get_temp_dir() . "/" . str_replace('\\', '_', static::class) . '.cache';
    }

    public function getTitle()
    {
        return 'Отчёт Номенклатура и Компании';
    }

    public function getExportFileName()
    {
        return 'Отчёт Номенклатура и Компании ' . date('d.m.Y') . '.xls';
    }

    public function getColumns(): array
    {
        $columns = [];

        $columns[] = ['id' => 'NOMENCLATURE', 'name' => 'Номенклатура'];
        $columns[] = ['id' => 'COMPANY', 'name' => 'Компания'];
        $columns[] = ['id' => 'COUNT_DEALS', 'name' => 'Кол-во сделок'];
        $columns[] = ['id' => 'COUNT_PROBES', 'name' => 'Кол-во проб'];
        $columns[] = ['id' => 'AMOUNT_DEALS', 'name' => 'Сумма сделок, руб.'];

        return $columns;
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

    public static function getContacts(): array
    {
        $arContacts = [];
        $elementContacts = \Bitrix\Crm\ContactTable::getList([
            'select' => ['ID', 'FULL_NAME'],
        ])->fetchAll();
        foreach ($elementContacts as $element) {
            $arContacts[$element['ID']] = $element['FULL_NAME'];
        }

        return $arContacts;
    }

    public function getFilterParams(): array
    {

        $filterParams[] = [
            'id' => 'DATE_UPD',
            'name' => 'Дата',
            'type' => 'date',
            'default' => true,
            'required' => true,
            'valueRequired' => true,
        ];


        $filterParams[] = [
            'id' => 'CRM_LABORATORY',
            'name' => 'Лаборатория',
            'type' => 'list',
            'default' => true,
            'items' => self::getLaboratory(),
        ];


        $filterParams[] = [
            'id' => 'REGION',
            'name' => 'Регион',
            'type' => 'list',
            'default' => true,
            'items' => self::getRegion(),
        ];

        return $filterParams;
    }

    public function getFilter($filterData = []): array
    {
        $filter = [];

        if ($filterData['DATE_UPD_from']) {
            $filter['>=UF_CRM_1636553061'] = $filterData['DATE_UPD_from'];
            $filter['<=UF_CRM_1636553061'] = $filterData['DATE_UPD_to'];
        }
        if ($filterData['CRM_LABORATORY']) {
            $filter['UF_CRM_LABORATORY'] = $filterData['CRM_LABORATORY'];
        }
        if ($filterData['REGION']) {
            $filter['UF_CRM_REGION'] = $filterData['REGION'];
        }

        return $filter;
    }

    public function getInfo($filter)
    {
        $result = \Bitrix\Crm\ProductRowTable::getList([
            'select' => [
                'PRODUCT_ID',
                'OWNER_ID',
                'PRODUCT_NAME',
                'PRICE',
                'QUANTITY',
                'COMPANY_ID' => 'DEAL.COMPANY_ID',
                'CONTACT_ID' => 'DEAL.CONTACT_ID',
            ],
            'runtime' => [
                'DEAL' => [
                    'data_type' => 'Bitrix\Crm\DealTable',
                    'reference' => ['=this.OWNER_ID' => 'ref.ID'],
                ],
                'COMPANY' => [
                    'data_type' => '\Bitrix\Crm\CompanyTable',
                    'reference' => ['=this.COMPANY_ID' => 'ref.ID']
                ],
                'CONTACT' => [
                    'data_type' => '\Bitrix\Crm\ContactTable',
                    'reference' => [
                        '=this.CONTACT_ID' => 'ref.ID',
                    ]
                ],
            ],
            'order' => ["OWNER_ID" => "asc"],
            'filter' => [
                'OWNER_TYPE' => 'D',
            ],
            'limit' => 30,
        ])->fetchAll();

        return $result;
    }


    public function getData($filterData = [], $offset, $limit, $sort): array
    {
        d(self::getInfo($filterData));
        $list = self::getInfo($filterData);
        return $list;
    }
}
