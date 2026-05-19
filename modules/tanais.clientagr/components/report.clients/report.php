<?php

namespace Tanais\Alter;

use Bitrix\Crm\DealTable;
use \Bitrix\Crm;
use \Bitrix\Crm\Service;

\Bitrix\Main\Loader::requireModule('crm');
\Bitrix\Main\Loader::requireModule('currency');

class Report
{
    protected $reportType;
    protected $moduleId;
    protected $currentUser;
    protected $cacheFileName;
    protected $cellFormat = [];

    public function __construct($type = 'all')
    {
        $this->reportType = 'import';
        $this->currentUser = \Bitrix\Main\Engine\CurrentUser::get();
        $this->moduleId = 'tanais.alter';
        $this->cacheFileName = sys_get_temp_dir() . "/" . str_replace('\\', '_', static::class) . '.cache';
    }

    public function getTitle()
    {
        return 'Отчёт клиенты группы';
    }

    public function getExportFileName()
    {
        return 'Отчёт клиенты группы ' . date('dmyHis') . '.xls';
    }

    public function getColumns(): array
    {
        $reference = new \Tanais\ClientAGR\Controller\Reference();

        $myCompanies = [];
        $referenceValues = $reference->getAction('Mycompany');
        foreach ($referenceValues as $referenceKey => $referenceValue) {
            $myCompanies[$referenceValue["CODE"]] = $referenceValue;
        }
        unset($referenceValues);
        
        $columns = [];

        foreach ($myCompanies as $company) {
            $columns[] = ['id' => 'COMPANY_ID_' . $company['CODE'], 'name' => 'ID_' . $company['CODE'], 'default' => true, 'sticked' => false, 'sortType' => 'numeric'];
        }

        $columns[] = ['id' => 'COMPANY_TITLE', 'name' => 'Компания', 'default' => true, 'sticked' => false, 'sortType' => 'string'];

        $columns[] = ['id' => 'RQ_INN_KPP', 'name' => 'ИНН/КПП', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'UF_CRM_COMPANY_AGR_AGROHOLDING', 'name' => 'Агрохолдинг', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'UF_CRM_COMPANY_AGR_GROUP_COMPANY', 'name' => 'Группа компаний', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'UF_CRM_COMPANY_AGR_REGION', 'name' => 'Регион', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'UF_CRM_COMPANY_AGR_ACTIVITY_TYPE', 'name' => 'Вид деятельности', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        // $columns[] = ['id' => 'ABC_CLIENT', 'name' => 'Категория клиента A/B/C', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        
        $columns[] = ['id' => 'UF_CRM_COMPANY_AGR_A_CLIENT_AR', 'name' => 'A-Клиент', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'UF_CRM_COMPANY_AGR_B_CLIENT_AR', 'name' => 'B-Клиент', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'UF_CRM_COMPANY_AGR_C_CLIENT_AR', 'name' => 'C-Клиент', 'default' => true, 'sticked' => false, 'sortType' => 'string'];

        foreach ($myCompanies as $company) {
            $columns[] = ['id' => 'DEAL_SUCCESS_SUM_' . $company['CODE'], 'name' => 'Общая выручка ' . $company['CODE'], 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        }

        foreach ($myCompanies as $company) {
            $columns[] = ['id' => 'DEAL_QC_SUM_' . $company['CODE'], 'name' => 'Оборот текущего квартала ' . $company['CODE'], 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        }

        foreach ($myCompanies as $company) {
            $columns[] = ['id' => 'DEAL_Q1_SUM_' . $company['CODE'], 'name' => 'Оборот Q1 ' . $company['CODE'], 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        }

        foreach ($myCompanies as $company) {
            $columns[] = ['id' => 'DEAL_Q2_SUM_' . $company['CODE'], 'name' => 'Оборот Q2 ' . $company['CODE'], 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        }

        foreach ($myCompanies as $company) {
            $columns[] = ['id' => 'DEAL_Q3_SUM_' . $company['CODE'], 'name' => 'Оборот Q3 ' . $company['CODE'], 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        }

        foreach ($myCompanies as $company) {
            $columns[] = ['id' => 'DEAL_Q4_SUM_' . $company['CODE'], 'name' => 'Оборот Q4 ' . $company['CODE'], 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        }

        foreach ($myCompanies as $company) {
            $columns[] = ['id' => 'DEAL_BEGINDATE_FIRST_' . $company['CODE'], 'name' => 'Дата первой сделки ' . $company['CODE'], 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        }

        foreach ($myCompanies as $company) {
            $columns[] = ['id' => 'DEAL_BEGINDATE_LAST_' . $company['CODE'], 'name' => 'Дата последней сделки ' . $company['CODE'], 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        }

        $columns[] = ['id' => 'TOTAL_HEADS_ALL_KINDS_DAIRY_COWS_HEIFER', 'name' => 'Всего голов животных всех видов/ КРС молочных коров/ КРС тёлок', 'default' => true, 'sticked' => false, 'sortType' => 'string'];
        $columns[] = ['id' => 'UF_CRM_COMPANY_AGR_COMMENT', 'name' => 'Комментарий AGR', 'default' => true, 'sticked' => false, 'sortType' => 'string'];

        return $columns;
    }


    public function getFilterParams(): array
    {

        $filterParams = [];

        // $filterParams[] = [
        //     'id' => 'PERIOD',
        //     'name' => 'Дата',
        //     'type' => 'date',
        //     'default' => true,
        //     'required' => true,
        //     'valueRequired' => true,
        // ];

        return $filterParams;
    }

    public function getFilter($filterData = []): array
    {
        $filter = [];
        // if ($filterData['PERIOD_from']) {
        //     $periodStart = $filterData['PERIOD_from'];
        //     $periodEnd = $filterData['PERIOD_to'];
        //     $filter['PERIOD_from'] = $periodStart;
        //     $filter['PERIOD_to'] = $periodEnd;

        //     $filter[] = [
        //         'LOGIC' => 'OR',
        //         [
        //             '<=UF_CRM_13_BEGIN_TIME' => $periodEnd,
        //             '>=UF_CRM_13_END_TIME'   => $periodStart,
        //         ],
        //         [
        //             '>=UF_CRM_13_BEGIN_TIME' => $periodStart,
        //             '<=UF_CRM_13_BEGIN_TIME' => $periodEnd,
        //         ],
        //         [
        //             '>=UF_CRM_13_END_TIME'   => $periodStart,
        //             '<=UF_CRM_13_END_TIME'   => $periodEnd,
        //         ],
        //         [
        //             '>=BEGINDATE' => $periodStart,
        //             '<=BEGINDATE' => $periodEnd,
        //         ],
        //     ];
        // }

        return $filter;
    }

    public function getClientData($filter): array
    {
        $controller = new \Tanais\ClientAGR\Controller\Company();
        $localData = $controller->dataReportAction();
        // d($localData);

        $currentCompany = \Tanais\ClientAGR\Reference::getThisServerRef();

        // $keyFromData = "";

        $reference = new \Tanais\ClientAGR\Controller\Reference();

        $myCompanies = [];
        $referenceValues = $reference->getAction('Mycompany');
        foreach ($referenceValues as $referenceKey => $referenceValue) {
            $myCompanies[$referenceValue["CODE"]] = $referenceValue;
        }
        unset($referenceValues);

        $tmpReportData = [];
        foreach ($localData as $company) {
            $tmpReportData[] = [
                'COMPANY_TITLE' => $company['TITLE'],
                'COMPANY_ID_' . $currentCompany['CODE'] => $company['ID'],
                'RQ_INN' => $company['RQ_INN'],
                'RQ_KPP' => $company['RQ_KPP'],
                'UF_CRM_COMPANY_AGR_AGROHOLDING' => implode(', ', $company['UF_CRM_COMPANY_AGR_AGROHOLDING']),
                'UF_CRM_COMPANY_AGR_GROUP_COMPANY' => $company['UF_CRM_COMPANY_AGR_GROUP_COMPANY'],
                'UF_CRM_COMPANY_AGR_REGION' => implode(', ', $company['UF_CRM_COMPANY_AGR_REGION']),
                'UF_CRM_COMPANY_AGR_ACTIVITY_TYPE' => implode(', ', $company['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE']),
                'UF_CRM_COMPANY_AGR_A_CLIENT_AR' => implode(', ', (array)$company['UF_CRM_COMPANY_AGR_A_CLIENT_AR']),
                'UF_CRM_COMPANY_AGR_B_CLIENT_AR' => implode(', ', (array)$company['UF_CRM_COMPANY_AGR_B_CLIENT_AR']),
                'UF_CRM_COMPANY_AGR_C_CLIENT_AR' => implode(', ', (array)$company['UF_CRM_COMPANY_AGR_C_CLIENT_AR']),
                'UF_CRM_COMPANY_AGR_TOTAL_HEADS_ALL_KINDS' => $company['UF_CRM_COMPANY_AGR_TOTAL_HEADS_ALL_KINDS'],
                'UF_CRM_COMPANY_AGR_DAIRY_COWS' => $company['UF_CRM_COMPANY_AGR_DAIRY_COWS'],
                'UF_CRM_COMPANY_AGR_HEIFER' => $company['UF_CRM_COMPANY_AGR_HEIFER'],
                $currentCompany['CODE'] => $company,
            ];
        }

        $webhooks = \Tanais\ClientAGR\Helper::getAllPartnerWebhook();
        foreach ($webhooks as $webhook) {
            $partnerCompanys = \Tanais\ClientAGR\Helper::callRestApi($webhook, 'tanais.clientagr.company.datareport.json', []);

            if (!is_array($partnerCompanys) || empty($partnerCompanys))
                continue;

            $keyCodeCompany = "";
            foreach ($myCompanies as $company) {
                if (strpos($webhook, $company['URL']) !== false) {
                    $keyCodeCompany = $company['CODE'];
                    break;
                }
            }

            if (empty($keyCodeCompany))
                continue;

            foreach ($partnerCompanys as $partnerCompany) {
                if (is_array($partnerCompany['UF_CRM_COMPANY_AGR_LINK']) && !empty($partnerCompany['UF_CRM_COMPANY_AGR_LINK'])) {
                    foreach ($partnerCompany['UF_CRM_COMPANY_AGR_LINK'] as $link) {
                        $matchCompanyId = basename(rtrim($link, '/'));
                        if (empty($matchCompanyId))
                            continue;

                        $matchCompanyKey = "";
                        foreach ($myCompanies as $company) {
                            if (strpos($link, $company['URL']) !== false) {
                                $matchCompanyKey = "COMPANY_ID_" .  $company['CODE'];
                                break;
                            }
                        }

                        if (empty($matchCompanyKey))
                            continue;

                        // findKey - $tmpReportData, $matchCompanyKey, $matchCompanyId
                        $findKey = ($tmp = array_filter($tmpReportData, fn($item) => isset($item[$matchCompanyKey]) && $item[$matchCompanyKey] == $matchCompanyId)) ? key($tmp) : false;

                        if (empty($findKey))
                            continue;

                        $tmpReportData[$findKey]['COMPANY_ID_' . $keyCodeCompany] = $partnerCompany['ID'];
                        $tmpReportData[$findKey][$keyCodeCompany] = $partnerCompany;

                        break; // если нашли куда добавить, то выходим из цикла ссылок
                    }
                } else {
                    $tmpReportData[] = [
                        'COMPANY_TITLE' => $partnerCompany['TITLE'],
                        'COMPANY_ID_' . $keyCodeCompany => $partnerCompany['ID'],
                        'RQ_INN' => $partnerCompany['RQ_INN'],
                        'RQ_KPP' => $partnerCompany['RQ_KPP'],
                        'UF_CRM_COMPANY_AGR_AGROHOLDING' => implode(', ', (array)$partnerCompany['UF_CRM_COMPANY_AGR_AGROHOLDING']),
                        'UF_CRM_COMPANY_AGR_GROUP_COMPANY' => $partnerCompany['UF_CRM_COMPANY_AGR_GROUP_COMPANY'],
                        'UF_CRM_COMPANY_AGR_REGION' => implode(', ', (array)$partnerCompany['UF_CRM_COMPANY_AGR_REGION']),
                        'UF_CRM_COMPANY_AGR_ACTIVITY_TYPE' => implode(', ', (array)$partnerCompany['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE']),
                        'UF_CRM_COMPANY_AGR_A_CLIENT_AR' => implode(', ', (array)$partnerCompany['UF_CRM_COMPANY_AGR_A_CLIENT_AR']),
                        'UF_CRM_COMPANY_AGR_B_CLIENT_AR' => implode(', ', (array)$partnerCompany['UF_CRM_COMPANY_AGR_B_CLIENT_AR']),
                        'UF_CRM_COMPANY_AGR_C_CLIENT_AR' => implode(', ', (array)$partnerCompany['UF_CRM_COMPANY_AGR_C_CLIENT_AR']),
                        'UF_CRM_COMPANY_AGR_TOTAL_HEADS_ALL_KINDS' => $partnerCompany['UF_CRM_COMPANY_AGR_TOTAL_HEADS_ALL_KINDS'],
                        'UF_CRM_COMPANY_AGR_DAIRY_COWS' => $partnerCompany['UF_CRM_COMPANY_AGR_DAIRY_COWS'],
                        'UF_CRM_COMPANY_AGR_HEIFER' => $partnerCompany['UF_CRM_COMPANY_AGR_HEIFER'],
                        'UF_CRM_COMPANY_AGR_COMMENT' => $partnerCompany['UF_CRM_COMPANY_AGR_COMMENT'],
                        $keyCodeCompany => $partnerCompany,
                    ];
                }
            }
        }

        $reportData = [];
        foreach ($tmpReportData as $report) {
            $tmp = [];
            $tmp['COMPANY_TITLE'] = $report['COMPANY_TITLE'];

            foreach ($myCompanies as $company) {
                $tmp['COMPANY_ID_' . $company['CODE']] = $report['COMPANY_ID_' . $company['CODE']];
            }

            $tmp['RQ_INN_KPP'] = $report['RQ_INN'] . '/' . $report['RQ_KPP'];
            $tmp['UF_CRM_COMPANY_AGR_AGROHOLDING'] = $report['UF_CRM_COMPANY_AGR_AGROHOLDING'];
            $tmp['UF_CRM_COMPANY_AGR_GROUP_COMPANY'] = $report['UF_CRM_COMPANY_AGR_GROUP_COMPANY'];
            $tmp['UF_CRM_COMPANY_AGR_REGION'] = $report['UF_CRM_COMPANY_AGR_REGION'];
            $tmp['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE'] = $report['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE'];

            $tmp['UF_CRM_COMPANY_AGR_A_CLIENT_AR'] = $report['UF_CRM_COMPANY_AGR_A_CLIENT_AR'];
            $tmp['UF_CRM_COMPANY_AGR_B_CLIENT_AR'] = $report['UF_CRM_COMPANY_AGR_B_CLIENT_AR'];
            $tmp['UF_CRM_COMPANY_AGR_C_CLIENT_AR'] = $report['UF_CRM_COMPANY_AGR_C_CLIENT_AR'];

            if (!empty($report['UF_CRM_COMPANY_AGR_TOTAL_HEADS_ALL_KINDS']) || !empty($report['UF_CRM_COMPANY_AGR_DAIRY_COWS']) || !empty($report['UF_CRM_COMPANY_AGR_HEIFER'])) {
                $tmp['TOTAL_HEADS_ALL_KINDS_DAIRY_COWS_HEIFER'] = $report['UF_CRM_COMPANY_AGR_TOTAL_HEADS_ALL_KINDS'] . '/ ' . $report['UF_CRM_COMPANY_AGR_DAIRY_COWS'] . '/ ' . $report['UF_CRM_COMPANY_AGR_HEIFER'];
            } else {
                $tmp['TOTAL_HEADS_ALL_KINDS_DAIRY_COWS_HEIFER'] = "";
            }

            foreach ($myCompanies as $company) {
                $tmp['DEAL_SUCCESS_SUM_' . $company['CODE']] = ($report[$company['CODE']]['DEAL_SUCCESS_SUM'] ?? 0) == 0 ? '' : number_format($report[$company['CODE']]['DEAL_SUCCESS_SUM'], 2, ',', ' ');
                $tmp['DEAL_QC_SUM_' . $company['CODE']] = ($report[$company['CODE']]['DEAL_QC_SUM'] ?? 0) == 0 ? '' : number_format($report[$company['CODE']]['DEAL_QC_SUM'], 2, ',', ' ');
                $tmp['DEAL_Q1_SUM_' . $company['CODE']] = ($report[$company['CODE']]['DEAL_Q1_SUM'] ?? 0) == 0 ? '' : number_format($report[$company['CODE']]['DEAL_Q1_SUM'], 2, ',', ' ');
                $tmp['DEAL_Q2_SUM_' . $company['CODE']] = ($report[$company['CODE']]['DEAL_Q2_SUM'] ?? 0) == 0 ? '' : number_format($report[$company['CODE']]['DEAL_Q2_SUM'], 2, ',', ' ');
                $tmp['DEAL_Q3_SUM_' . $company['CODE']] = ($report[$company['CODE']]['DEAL_Q3_SUM'] ?? 0) == 0 ? '' : number_format($report[$company['CODE']]['DEAL_Q3_SUM'], 2, ',', ' ');
                $tmp['DEAL_Q4_SUM_' . $company['CODE']] = ($report[$company['CODE']]['DEAL_Q4_SUM'] ?? 0) == 0 ? '' : number_format($report[$company['CODE']]['DEAL_Q4_SUM'], 2, ',', ' ');

                $tmp['DEAL_BEGINDATE_FIRST_' . $company['CODE']] = isset($report[$company['CODE']]['DEAL_BEGINDATE_FIRST']) && $report[$company['CODE']]['DEAL_BEGINDATE_FIRST'] instanceof \Bitrix\Main\Type\DateTime ? $report[$company['CODE']]['DEAL_BEGINDATE_FIRST']->format('d.m.Y') : '';
                $tmp['DEAL_BEGINDATE_LAST_' . $company['CODE']] = isset($report[$company['CODE']]['DEAL_BEGINDATE_LAST']) && $report[$company['CODE']]['DEAL_BEGINDATE_FIRST'] instanceof \Bitrix\Main\Type\DateTime ? $report[$company['CODE']]['DEAL_BEGINDATE_LAST']->format('d.m.Y') : '';
            }

            $reportData[] = $tmp;
        }

        return $reportData;
    }

    
    public function getFilterPresets(): array
    {
        $filterPresets = [];

        // $currentDate = new \DateTime();
        // $prevMonthDate = (clone $currentDate)
        //     ->modify('first day of last month 00:00:00');

        // $year = (int)$prevMonthDate->format('Y');
        // $month = (int)$prevMonthDate->format('n');
        // $currentQuarter = ceil($month / 3);

        // $startDate = clone $prevMonthDate;
        // $endDate = (clone $prevMonthDate)
        //     ->modify('last day of this month')
        //     ->setTime(23, 59, 59);


        // $filterPresets = [
        //     'default_preset' => [
        //         'name' => 'Предыдущий месяц ',
        //         'default' => true,
        //         'fields' => [
        //             'PERIOD_datesel' => "MONTH",
        //             'PERIOD_quarter' => strval($currentQuarter),
        //             'PERIOD_year' => strval($year),
        //             'PERIOD_month' => strval($month),
        //             'PERIOD_from' => $startDate->format('d.m.Y H:i:s'),
        //             'PERIOD_to' => $endDate->format('d.m.Y H:i:s'),
        //         ],
        //     ],
        //     'filter' => [
        //         '>=UF_CRM_13_BEGIN_TIME' => $startDate->format('d.m.Y H:i:s'),
        //         '<=UF_CRM_13_END_TIME' => $endDate->format('d.m.Y H:i:s'),
        //     ]
        // ];


        return $filterPresets;
    }


    public function getData($filterData = [], $offset, $limit, $sort): array
    {
        $clientData = self::getClientData($filterData);

        $filterPresets = self::getFilterPresets();
        unset($filterPresets['filter']);

        foreach ($clientData as $data) {
            $gridRow = $data;
            $gridExport = $data;

            $list[] = [
                'data' => $gridRow,
            ];

            $listExport[] = [
                'data' => $gridExport,
            ];
        }

        $return = [
            "DATA" => $list,
            'DATA_EXPORT' => $listExport,
            "COUNT" => is_countable($list) ? count($list) : 0,
            'SORT_FRIENDLY' => false,
            'FILTER_PRESETS' => $filterPresets,
        ];

        foreach ($return['DATA'] as $key => $row) {
            if (($key < $offset) or ($key >= ($offset + $limit)))
                unset($return['DATA'][$key]);
        }

        return $return;
    }
}
