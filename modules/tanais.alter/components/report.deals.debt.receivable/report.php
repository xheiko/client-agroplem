<?php

namespace Tanais\Alter;

use Bitrix\Crm\DealTable;
use \Bitrix\Crm;
use \Bitrix\Crm\Service;

\Bitrix\Main\Loader::includeModule('crm');

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
        return 'Сделки. Просроченная задолженность';
    }

    public function getExportFileName()
    {
        return 'Сделки. Просроченная задолженность на ' . date('d.m.Y') . '.xls';
    }

    public function getColumns(): array
    {
        $columns = [];

        // $columns[] = ['id' => 'REGION_ALTA', 'name' => 'Регион Альта', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'ASSIGNED', 'name' => 'Ответственный', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'CATEGORY', 'name' => 'Воронка', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'COMPANY', 'name' => 'Компания', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'DEAL_TITLE', 'name' => 'Номер сделки', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'SUM', 'name' => 'Сумма долга на сегодня', 'default' => true, 'measureUnit' => '% ₽', 'decimals' => 2, 'decimalSeparator' => '.', 'thousandsSeparator' => ' ', 'clearZero' => false, 'sortType' => 'number'];
        $columns[] = ['id' => 'OVERDUE', 'name' => 'Просрочено дней', 'default' => true, 'measureUnit' => '% дней', 'decimals' => 0, 'decimalSeparator' => '.', 'thousandsSeparator' => ' ', 'clearZero' => false, 'sortType' => 'number'];

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
            'items' => $this->getCategoryList(),
            'params' => ['multiple' => 'Y']
        ];

        return $filterParams;
    }

    protected function getCategoryList(): array
    {
        \Bitrix\Main\Loader::includeModule('crm');

        $list = [];

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Deal);

        if ($factory) {
            $categories = $factory->getCategories();
            foreach ($categories as $category) {
                $list[(int)$category->getId()] = (string)$category->getName();
            }
        }

        return $list;
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

    public function getReportElements()
    {
        $reportData = [];

        // МЫ РЕШАЕМ, ЧТО ОТДАВАТЬ в отчёте! Все данные, данные по отделу сотрудника или только самого пользователя
//        $sections = \CIntranetUtils::GetUserDepartments($this->currentUser->getId());
//        if ((!in_array(1, $sections)) and (!in_array(2, $sections)) and (!in_array(3, $sections)) and (!in_array(86, $sections)) and (!in_array(87, $sections)) and ($this->currentUser->getId() != 1)) {
//            $subUsers = \Tanais\Alta\User::getSubordinateEmployees($this->currentUser->getId());
//            $subUsers[] = $this->currentUser->getId();
//            array_unique($subUsers);
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
        //    }
        if (!empty($this->filterData['CATEGORY_ID'])) {
            $arFilter['CATEGORY_ID'] = $this->filterData['CATEGORY_ID'];
        }

//        $arFilter[] = [
//            'LOGIC' => 'OR',
//            '>UF_CRM_CREDIT_SUM' => 1,
//            '>UF_CRM_OVERDUE_DEBT' => 1,
//        ];
        $arFilter['!CATEGORY_ID'] = [8];
        $arFilter['>UF_CRM_OVERDUE_DEBT'] = 1;


        $userNames = \Tanais\Alter\User::getAllUsers();

        $arDeals = \Bitrix\Crm\DealTable::getList([
            'select' => [
                'ID',
                'TITLE',
                'ASSIGNED_BY_ID',
                'CATEGORY_ID',
                'UF_CRM_OVERDUE_DEBT',
                'COMPANY_ID',
                'UF_CRM_DEAL_PAYMENT_LASTDATE',
                'COMPANY_TITLE' => 'COMPANY.TITLE',
            ],
            'runtime' => [
                'COMPANY' => [
                    'data_type' => 'Bitrix\Crm\CompanyTable',
                    'reference' => ['=this.COMPANY_ID' => 'ref.ID'],
                ],
            ],
            'order' => ["ID" => "asc"],
            'filter' => $arFilter,
        ]);

        $now = new \DateTime();
        $categoryList = $this->getCategoryList();

        while ($deal = $arDeals->fetch()) {

            $userId = (int)$deal['ASSIGNED_BY_ID'];

            $assignedName = $userNames[$userId] ?? 'Неизвестный сотрудник';
            $companyTitle = (string)($deal['COMPANY_TITLE'] ?? '');

            // Просрочено дней
            $overdueDays = 0;
            $dateRaw = $deal['UF_CRM_DEAL_PAYMENT_LASTDATE'] ?? null;

            if ($dateRaw) {
                try {
                    if ($dateRaw instanceof \Bitrix\Main\Type\Date || $dateRaw instanceof \Bitrix\Main\Type\DateTime) {
                        $overdueFrom = \DateTime::createFromTimestamp($dateRaw->getTimestamp());
                    } elseif ($dateRaw instanceof \DateTime) {
                        $overdueFrom = $dateRaw;
                    } else {
                        $overdueFrom = new \DateTime((string)$dateRaw);
                    }

                    $diff = $overdueFrom->diff($now);
                    if ($diff->invert === 0) {
                        $overdueDays = (int)$diff->days;
                    }
                } catch (\Throwable $e) {
                    $overdueDays = 0;
                }
            }

            $category = (string)$deal['CATEGORY_ID'];

            $reportData[] = [
                'ASSIGNED' => $assignedName,
                'CATEGORY' => $categoryList[$category],
                'COMPANY_ID' => $deal['COMPANY_ID'],
                'COMPANY' => $companyTitle ?: '—',
                'DEAL_ID' => $deal['ID'],
                'DEAL_TITLE' => $deal['TITLE'] ?: ('#' . (int)$deal['ID']),
                'SUM' => (float)$deal['UF_CRM_OVERDUE_DEBT'],
                'OVERDUE' => $overdueDays,
            ];
        }

        return $reportData;
    }

    public function getData($filterData = [], $offset, $limit, $sort)
    {
        $this->filterData = $filterData;
        $listExport = [];
        $list = [];
        $rowCount = 0;
        $totalSum = 0;

        $reportElements = $this->getReportElements();

        foreach ($reportElements as $reportElement) {
            if ((empty($this->filterData['FIND']))
                or (mb_stripos($reportElement['ASSIGNED'], trim($this->filterData['FIND'])) !== false)
                or (mb_stripos($reportElement['DEAL_TITLE'], trim($this->filterData['FIND'])) !== false)
                or (mb_stripos($reportElement['COMPANY'], trim($this->filterData['FIND'])) !== false)
                or (mb_stripos($reportElement['CATEGORY'], trim($this->filterData['FIND'])) !== false)
            ) {
                $rowCount++;
                $totalSum += $reportElement['SUM'];
                $listExport[] = ['data' => $reportElement];

                $reportElement['DEAL_TITLE'] = '<a href="/crm/deal/details/' . $reportElement['DEAL_ID'] . '/">' . $reportElement['DEAL_TITLE'] . '</a><br>';
                $reportElement['COMPANY'] = '<a href="/crm/company/details/' . $reportElement['COMPANY_ID'] . '/">' . $reportElement['COMPANY'] . '</a><br>';

                $list[] = ['data' => $reportElement];
            }
        }

        $list[] = [
            'data' => [
                'COMPANY' => 'ИТОГО: ',
                'DEAL_TITLE' => $rowCount . ' шт.',
                'SUM' => $totalSum,
            ],
        ];

        $listExport[] = [
            'data' => [
                'COMPANY' => 'ИТОГО: ',
                'DEAL_TITLE' => $rowCount . ' шт.',
                'SUM' => $totalSum,
            ],
        ];

        $return = [
            "DATA" => $list,
            "COUNT" => $rowCount,
            "DATA_EXPORT" => $listExport,
            "GENERATED_TIME" => '',
            'DATA_ACTUAL_TIME' => time(),
            'DATA_CACHED' => false,
            'SORT_FRIENDLY' => true,
        ];

        return $return;
    }
}
