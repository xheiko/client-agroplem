<?php

namespace Tanais\Alter;

use \Bitrix\Crm\Service;


\Bitrix\Main\Loader::requireModule('crm');

class Report
{
    protected $cellFormat = [];

    public function getTitle(): string
    {
        return 'Новые клиенты';
    }

    public function getExportFileName(): string
    {
        return 'Новые клиенты.xls';
    }

    public function getColumns(): array
    {
        $columns = [];
        $columns[] = ['id' => 'CLIENT', 'name' => 'Клиент', 'default' => true, 'sticked' => true];

        $deals = $this->getDeals();
        $laboratory = $this->getLaboratory();

        $labs = array_unique($deals["LABS"]);

        foreach ($labs as $lab) {
            $columns[] = ['id' => $lab . '_FIRST_DEAL', 'name' => $laboratory[$lab] . ' первая сделка', 'default' => true, 'sticked' => false];
            $columns[] = ['id' => $lab . '_LAST_DEAL', 'name' => $laboratory[$lab] . ' последняя сделка', 'default' => true, 'sticked' => false];
//            $columns[] = ['id' => $lab . '_OPPORTUNITY', 'name' => $laboratory[$lab] . ' оборот', 'default' => true, 'sticked' => false];
//
//            $this->cellFormat[$lab . '_OPPORTUNITY'] = ['UNITS' => '₽', 'DECIMALS' => 2, 'DECIMAL_SEPARATOR' => '.', 'THOUSANDS_SEPARATOR' => ' '];
            $columns[] = [
                'id' => $lab . '_OPPORTUNITY',
                'name' => $laboratory[$lab] . ' оборот',
                'default' => true,
                'measureUnit' => '% ₽',
                'decimals' => 2,
                'decimalSeparator' => '.',
                'thousandsSeparator' => ' ',
                'clearZero' => false,
                'sortType' => 'number',
            ];
        }

        return $columns;
    }


    public function getFilterParams(): array
    {
        $filterParams[] = [
            'id' => 'UF_CRM_LABORATORY',
            'name' => 'Лаборатории',
            'type' => 'list',
            'items' => $this->getLaboratory(),
            'params' => ['multiple' => 'Y']
        ];

        $filterParams[] = [
            'id' => 'OPPORTUNITY',
            'name' => 'Оборот',
            'type' => 'number',
        ];

        $filterParams[] = [
            'id' => 'FIRST_DEAL',
            'name' => 'Первая сделка',
            'type' => 'date',
        ];

        $filterParams[] = [
            'id' => 'LAST_DEAL',
            'name' => 'Последняя сделка',
            'type' => 'date',
        ];

        return $filterParams;
    }

    public function getFilter($filterData = []): array
    {
        $filter = [];
        foreach ($filterData as $key => $filterRow)
            if (!empty($filterData[$key])) {
                $filter[$key] = $filterRow;
            }
        return $filter;
    }

    public function getLaboratory(): array
    {
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


    public function getDeals($filter = [])
    {
        $arDeals = [];
        $entityResult = \CCrmDeal::GetListEx(
            [
                'UF_CRM_1636553061' => 'ASC'
            ],
            [
                '!UF_CRM_LABORATORY' => 810,
                '!STAGE_SEMANTIC_ID' => 'F',
                $filter
            ],
            false,
            false,
            [
                'ID',
                'TITLE',
                'UF_CRM_LABORATORY',
                'UF_CRM_1636553061',
                'OPPORTUNITY',
                'COMPANY_ID'
            ]
        );

        while ($entity = $entityResult->fetch()) {
            $arDeals[$entity['COMPANY_ID']][$entity['UF_CRM_LABORATORY'] . '_OPPORTUNITY'] += $entity['OPPORTUNITY'];
            if (!$arDeals[$entity["COMPANY_ID"]][$entity['UF_CRM_LABORATORY'] . '_FIRST_DEAL']) {
                $arDeals[$entity["COMPANY_ID"]][$entity['UF_CRM_LABORATORY'] . '_FIRST_DEAL'] = $entity['UF_CRM_1636553061'];
            }
            $arDeals[$entity["COMPANY_ID"]][$entity['UF_CRM_LABORATORY'] . '_LAST_DEAL'] = $entity['UF_CRM_1636553061'];
            $arDeals['LABS'][] = $entity["UF_CRM_LABORATORY"];
        }
        return $arDeals;
    }

    public function getCompany()
    {
        $arCompany = [];
        $entityResult = \CCrmCompany::GetListEx([], [], false, false, ['ID', 'TITLE',]);

        while ($entity = $entityResult->fetch()) {
            $arCompany[$entity["ID"]] = $entity["TITLE"];
        }
        return $arCompany;
    }

    public function getData($filterData = [], $offset, $limit, $sort): array
    {

        if ($filterData['UF_CRM_LABORATORY']) {
            $deals = $this->getDeals($filterData);
        } else {
            $deals = $this->getDeals();
        }

        $list = [];
        $company = $this->getCompany();

        $count = 0;

        $labs = $this->getLaboratory();

        foreach ($deals as $companyId => $data) {
            if (!$companyId or $companyId == 'LABS') {
                continue;
            }

            $gridRow = [];

            foreach ($labs as $labId => $labName) {

                if (isset($filterData['OPPORTUNITY_to']) && isset($filterData['OPPORTUNITY_from']) && $data[$labId . '_OPPORTUNITY'] >= $filterData['OPPORTUNITY_from'] && $data[$labId . '_OPPORTUNITY'] <= $filterData['OPPORTUNITY_to']
                    or isset($filterData['OPPORTUNITY_to']) && !isset($filterData['OPPORTUNITY_from']) && $data[$labId . '_OPPORTUNITY'] <= $filterData['OPPORTUNITY_to']
                    or isset($filterData['OPPORTUNITY_from']) && !isset($filterData['OPPORTUNITY_to']) && $data[$labId . '_OPPORTUNITY'] >= $filterData['OPPORTUNITY_from']
                    or isset($filterData['FIRST_DEAL_from']) && strtotime($data[$labId . '_FIRST_DEAL']) >= strtotime($filterData['FIRST_DEAL_from']) && strtotime($data[$labId . '_FIRST_DEAL']) <= strtotime($filterData['FIRST_DEAL_to'])
                    or isset($filterData['LAST_DEAL_from']) && strtotime($data[$labId . '_LAST_DEAL']) >= strtotime($filterData['LAST_DEAL_from']) && strtotime($data[$labId . '_LAST_DEAL']) <= strtotime($filterData['LAST_DEAL_to'])) {
                    $gridRow["CLIENT"] = "<a href=/crm/company/details/" . $companyId . "/>" . $company[$companyId] . "</a>";
                    $gridRow[$labId . '_OPPORTUNITY'] = $data[$labId . '_OPPORTUNITY'];
                    $gridRow[$labId . '_FIRST_DEAL'] = $data[$labId . '_FIRST_DEAL'];
                    $gridRow[$labId . '_LAST_DEAL'] = $data[$labId . '_LAST_DEAL'];
                } elseif (!isset($filterData['OPPORTUNITY_to']) and !isset($filterData['OPPORTUNITY_from']) and !isset($filterData['FIRST_DEAL_from']) and !isset($filterData['LAST_DEAL_from'])) {
                    $gridRow["CLIENT"] = "<a href=/crm/company/details/" . $companyId . "/>" . $company[$companyId] . "</a>";
                    $gridRow[$labId . '_OPPORTUNITY'] = $data[$labId . '_OPPORTUNITY'];
                    $gridRow[$labId . '_FIRST_DEAL'] = $data[$labId . '_FIRST_DEAL'];
                    $gridRow[$labId . '_LAST_DEAL'] = $data[$labId . '_LAST_DEAL'];
                }

            }

            if (!empty($gridRow)) {
                $count++;
                $list[] = [
                    'data' => $gridRow,
                ];
            }
        }

        $formattedList = $list;

        $return = [
            "DATA" => $formattedList,
            "COUNT" => $count,
            "DATA_EXPORT" => $list,
            "GENERATED_TIME" => '',
            'DATA_ACTUAL_TIME' => time(),
            'DATA_CACHED' => false,
        ];

        foreach ($return['DATA'] as $key => $row) {
            if (($key < $offset) or ($key >= ($offset + $limit)))
                unset($return['DATA'][$key]);
        }

        return $return;

    }
}
