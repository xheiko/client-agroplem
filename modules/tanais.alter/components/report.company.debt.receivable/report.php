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
    protected $filterData;

    protected $dateFrom;
    protected $dateTo;

    protected $cellFormat = [];

    public function __construct()
    {
        $this->currentUser = \Bitrix\Main\Engine\CurrentUser::get();
        $this->moduleId = 'tanais.alter';
        $this->cacheFileName = sys_get_temp_dir() . "/" . str_replace('\\', '_', static::class) . '.cache';
    }

    public function getTitle()
    {
        return 'Компании. Просроченная задолженность.';
    }

    public function getExportFileName()
    {
        return 'Просроченная задолженность на ' . date('d.m.Y') . '.xls';
    }

    public function getProductsList(): array
    {
        return [];
    }

    public function getColumns(): array
    {
        $columns = [];

        $columns[] = ['id' => 'DEALS_ID', 'name' => 'Сделки', 'default' => false, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'LABORATORY', 'name' => 'Лаборатория', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'ASSIGNED_BY_NAME', 'name' => 'Ответственный', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'FACT', 'name' => 'Факт', 'default' => true, 'measureUnit' => '% ₽', 'decimals' => 0, 'decimalSeparator' => '.', 'thousandsSeparator' => ' ', 'clearZero' => false, 'sortType' => 'number'];
        $columns[] = ['id' => 'GOOD_DEALS', 'name' => 'Не просроченных сделок', 'default' => true, 'measureUnit' => '% шт.', 'decimals' => 0, 'decimalSeparator' => '.', 'thousandsSeparator' => ' ', 'clearZero' => false, 'sortType' => 'number'];
        $columns[] = ['id' => 'LESS_MONTH', 'name' => 'Менее месяца', 'default' => true, 'measureUnit' => '% шт.', 'decimals' => 0, 'decimalSeparator' => '.', 'thousandsSeparator' => ' ', 'clearZero' => false, 'sortType' => 'number'];
        $columns[] = ['id' => 'ONE_TO_TWO_MONTH', 'name' => 'От месяца до 2х', 'default' => true, 'measureUnit' => '% шт.', 'decimals' => 0, 'decimalSeparator' => '.', 'thousandsSeparator' => ' ', 'clearZero' => false, 'sortType' => 'number'];
        $columns[] = ['id' => 'MORE_TWO_MONTH', 'name' => 'Более 2х месяцев', 'default' => true, 'measureUnit' => '% шт.', 'decimals' => 0, 'decimalSeparator' => '.', 'thousandsSeparator' => ' ', 'clearZero' => false, 'sortType' => 'number'];

        return $columns;
    }

    public function getFilterParams(): array
    {
        $filterParams[] = [
            'id' => 'ASSIGNED_BY_ID',
            'default' => true,
            'name' => 'Ответственный',
            'type' => 'entity_selector',
            'params' => [
                'multiple' => 'Y',
                'dialogOptions' => [
                    'height' => 240,
                    'context' => 'filter',
                    'entities' => [
                        [
                            'id' => 'user',
                            'options' => [
                                'inviteEmployeeLink' => false
                            ],
                        ],
                        [
                            'id' => 'department',
                        ]
                    ]
                ],
            ],
        ];

        $filterParams[] = [
            'id' => 'CATEGORY_ID',
            'default' => true,
            'name' => 'Воронка',
            'type' => 'list',
            'items' => self::getLabs(),
            'params' => ['multiple' => 'Y']
        ];

        return $filterParams;
    }

    public function getFilter($filterData = []): array
    {
        $filter = [];
        foreach ($filterData as $key => $filterRow) {
            if (!empty($filterData[$key])) {
                $filter[$key] = $filterRow;
            }
        }
        return $filter;
    }

    private function getLabs(): array
    {
        $arLabs = [];
        $res = \Bitrix\Crm\Category\DealCategory::getList(
            ["select" => ["*"], "filter" => ['!ID' => 8]]
        );
        while ($entity = $res->fetch()) {
            $labName = preg_replace('/^.*?[-—]\s*/u', '', $entity['NAME']);
            $arLabs[$entity['ID']] = $labName;

        }
        return $arLabs;
    }

    public function getReportElements()
    {
        $reportData = [];

        // МЫ РЕШАЕМ, ЧТО ОТДАВАТЬ в отчёте! Все данные, данные по отделу сотрудника или только самого пользователя
//        $sections = \CIntranetUtils::GetUserDepartments($this->currentUser->getId());
//        if ((!in_array(1, $sections)) and (!in_array(2, $sections)) and (!in_array(3, $sections)) and (!in_array(86, $sections)) and (!in_array(87, $sections)) and ($this->currentUser->getId() != 1)) {
//            $subUsers = \Tanais\Alter\User::getSubordinateEmployees($this->currentUser->getId());
//            $subUsers[] = $this->currentUser->getId();
//            $subUsers = array_unique($subUsers);
//
//            $arFilter['ASSIGNED_BY_ID'] = $subUsers;
//
//            // Если установлен фильтр, то вычищаем не его сотрудников
//            if (!empty($this->filterData['ASSIGNED_BY_ID'])) {
//                $arFilter['ASSIGNED_BY_ID'] = $this->filterData['ASSIGNED_BY_ID'];
//                foreach ($arFilter['ASSIGNED_BY_ID'] as $key => $userId) {
//                    if (!in_array($userId, $subUsers)) {
//                        unset($arFilter['ASSIGNED_BY_ID'][$key]);
//                    }
//                }
//            }
//        } else {
            if (!empty($this->filterData['ASSIGNED_BY_ID'])) {
                $arFilter['ASSIGNED_BY_ID'] = $this->filterData['ASSIGNED_BY_ID'];
            }
       // }

        if (!empty($this->filterData['CATEGORY_ID'])) {
            $arFilter['CATEGORY_ID'] = $this->filterData['CATEGORY_ID'];
        }
        $arFilter['!CATEGORY_ID'] = 8;
        
        $userNames = \Tanais\Alter\User::getAllUsers();
        $labs = self::getLabs();

        $arDeals = \Bitrix\Crm\DealTable::getList([
            'select' => [
                'ID',
                'TITLE',
                'ASSIGNED_BY_ID',
                'UF_CRM_OVERDUE_DEBT',
                'UF_CRM_DEAL_PAYMENT_LASTDATE',
                'CATEGORY_ID',
            ],
            'order' => ["ID" => "asc"],
            'filter' => $arFilter,
        ]);

        while ($deal = $arDeals->fetch()) {

            $userId = (int)$deal['ASSIGNED_BY_ID'];
            $userName = $userNames[$userId] ?? 'Неизвестный сотрудник';

            $key = (int)$deal['CATEGORY_ID'] . '_' . (int)$deal['ASSIGNED_BY_ID'];

            $reportData[$key]['ASSIGNED_BY_ID'] = (int)$deal['ASSIGNED_BY_ID'];
            $reportData[$key]['ASSIGNED_BY_NAME'] = $userName;
            $reportData[$key]['LABORATORY'] = $labs[$deal['CATEGORY_ID']];

            $reportData[$key]['FACT'] += $deal['UF_CRM_OVERDUE_DEBT'];

            $reportData[$key]['GOOD_DEALS'] = (int)($reportData[$key]['GOOD_DEALS'] ?? 0);
            $reportData[$key]['LESS_MONTH'] = (int)($reportData[$key]['LESS_MONTH'] ?? 0);
            $reportData[$key]['ONE_TO_TWO_MONTH'] = (int)($reportData[$key]['ONE_TO_TWO_MONTH'] ?? 0);
            $reportData[$key]['MORE_TWO_MONTH'] = (int)($reportData[$key]['MORE_TWO_MONTH'] ?? 0);

            if ((float)$deal['UF_CRM_OVERDUE_DEBT'] <= 0) {
                $reportData[$key]['GOOD_DEALS'] += 1;
            } else {
                $reportData[$key]['DEALS_ID'] .= '<a href="/crm/deal/details/' . $deal['ID'] . '/">' . $deal['TITLE'] . '</a><br>';
                $dateRaw = $deal['UF_CRM_DEAL_PAYMENT_LASTDATE'] ?? null;
                $now = new \DateTime();

                if ($dateRaw instanceof \Date || $dateRaw instanceof \DateTime) {
                    $overdueFrom = ($dateRaw instanceof \DateTime)
                        ? $dateRaw
                        : \DateTime::createFromTimestamp($dateRaw->getTimestamp());
                } else {
                    try {
                        $overdueFrom = new \DateTime((string)$dateRaw);
                    } catch (\Throwable $e) {
                        continue;
                    }
                }

                $diff = $overdueFrom->diff($now);
                $months = $diff->y * 12 + $diff->m;

                if ($months == 0) {
                    $reportData[$key]['LESS_MONTH'] += 1;
                } elseif ($months == 1) {
                    $reportData[$key]['ONE_TO_TWO_MONTH'] += 1;
                } else {
                    $reportData[$key]['MORE_TWO_MONTH'] += 1;
                }
            }
        }

        return $reportData;
    }

    public function getData($filterData = [], $offset, $limit, $sort)
    {
        $this->filterData = $filterData;

        $list = [];
        $rowCount = 0;

        $reportElements = $this->getReportElements();

        foreach ($reportElements as $reportElement) {
            if ((empty($this->filterData['FIND']))
                or (mb_stripos($reportElement['ASSIGNED_BY_NAME'], trim($this->filterData['FIND'])) !== false)
                or (mb_stripos($reportElement['LABORATORY'], trim($this->filterData['FIND'])) !== false)
            ) {
                $rowCount++;

                $dataGrid = $reportElement;

                $list[] = [
                    'data' => $dataGrid,
                ];
            }
        }

        $return = [
            "DATA" => $list,
            "COUNT" => $rowCount,
            "DATA_EXPORT" => [],
            "GENERATED_TIME" => '',
            'DATA_ACTUAL_TIME' => time(),
            'DATA_CACHED' => false,
            'SORT_FRIENDLY' => true,
        ];

        return $return;
    }
}
