<?php

namespace Tanais\Alter;

use Bitrix\Crm\DealTable;
use \Bitrix\Crm;
use CBitrixComponent;

\Bitrix\Main\Loader::requireModule('crm');

class Report
{
    protected $moduleId;
    protected $currentUser;
    protected $filterData;
    const STAGE_NEW = ['DT1132_47:NEW'];
    const STAGE_PLAN = ['DT1132_47:PREPARATION'];
    const STAGE_SUCCESS = ['DT1132_47:SUCCESS'];
    const STAGE_FAIL = ['DT1132_47:FAIL', 'DT1132_48:NEW', 'DT1132_48:PREPARATION', 'DT1132_48:FAIL', 'DT1132_49:NEW', 'DT1132_49:PREPARATION', 'DT1132_49:FAIL'];
    const STAGE_OFFICE_SUCCESS = ['DT1132_48:SUCCESS'];
    const STAGE_DAYOFF_SUCCESS = ['DT1040_49:SUCCESS'];


    public function __construct()
    {
        $this->currentUser = \Bitrix\Main\Engine\CurrentUser::get();
        $this->moduleId = 'tanais.alter';
        $this->cacheFileName = sys_get_temp_dir() . "/" . str_replace('\\', '_', static::class) . '.cache';
    }

    public function getTitle()
    {
        return 'Отчёт по сотрудникам';
    }

    public function getExportFileName()
    {
        return 'Отчёт по сотрудникам.xls';
    }

    public function getRegion(): array
    {
        return \Tanais\Alter\Helper::getCrmRegion();
    }

    public function getLaboratory(): array
    {
        return \Tanais\Alter\Crm\Laboratory::getLaboratoryList();
    }

    public function formatNumber($number): string
    {
        return number_format($number, strpos($number, '.') === false ? 1 : 1);
    }

    public function getDepartmentUser(): array
    {
        $arUser = [];
        $arParams["SELECT"] = ["UF_DEPARTMENT"];
        $arRegion = self::getRegion();
        $rsUsers = \CUser::GetList([], [], [], $arParams);
        while ($user = $rsUsers->Fetch()) {
            $arUser['DEPARTMENT_USER'][$user['ID']] = $arRegion[$user['UF_DEPARTMENT'][0]];
            $arUser['USER'][$user['ID']] = $user['LAST_NAME'] . ' ' . $user['NAME'];
            $arUser['USER_DEPARTMENT'][$user['UF_DEPARTMENT'][0]][] = $user['ID'];
        }
        return $arUser;
    }

    public function getColumns(): array
    {
        $columns = [];
        $columns[] = ['id' => 'REGION', 'name' => 'Сотрудник', 'default' => true, 'sticked' => true];
        $columns[] = ['id' => 'DRAFTS', 'name' => 'Черновик (клиент) ', 'default' => true, 'align' => 'right'];
        $columns[] = ['id' => 'PLANNED', 'name' => 'План (клиент)', 'default' => true, 'align' => 'right'];
        $columns[] = ['id' => 'COMPLETED', 'name' => 'Выполнено (клиент)', 'default' => true, 'align' => 'right'];
        $columns[] = ['id' => 'CONTRAGENT', 'name' => 'Клиентов', 'default' => true, 'align' => 'right'];
        //  $columns[] = ['id' => 'VISITS_TIME', 'name' => 'Время у клиента', 'default' => true, 'align' => 'right'];
        $columns[] = ['id' => 'OFFICE', 'name' => 'Офисных дней', 'default' => true, 'align' => 'right'];
        $columns[] = ['id' => 'DAYOFF', 'name' => 'Отсутствий', 'default' => true, 'align' => 'right'];

        return $columns;
    }

    public function fillWithMesuareUnit(&$list)
    {
        if (empty($this->cellFormat))
            self::getColumns();

        foreach ($list as &$row)
            foreach ($row['data'] as $key => &$cell) {
                if (($this->cellFormat[$key]) && (!empty($cell))) {
                    if (isset($this->cellFormat[$key]['DECIMALS']) && isset($this->cellFormat[$key]['DECIMAL_SEPARATOR']) && isset($this->cellFormat[$key]['THOUSANDS_SEPARATOR']) && ($cell) && is_numeric($cell))
                        $cell = number_format($cell, $this->cellFormat[$key]['DECIMALS'], $this->cellFormat[$key]['DECIMAL_SEPARATOR'], $this->cellFormat[$key]['THOUSANDS_SEPARATOR']);
                    if ($this->cellFormat[$key]['UNITS'])
                        $cell = $cell . $this->cellFormat[$key]['UNITS'];
                }
            }
    }

    public function getFilterParams(): array
    {
        $filterParams[] = [
            'id' => 'REGION',
            'name' => 'Регион',
            'type' => 'list',
            'items' => self::getRegion(),
            'default' => true,
        ];
        $filterParams[] = [
            'id' => 'LABORATORY',
            'name' => 'Лаборатория',
            'type' => 'list',
            'items' => self::getLaboratory(),
            'params' => ['multiple' => 'Y'],
            'default' => true,
        ];

        $filterParams[] = [
            'id' => 'ASSIGNED',
            'name' => 'Ответственный',
            'type' => 'dest_selector',
            'params' => [
                'departmentSelectDisable' => 'N',
                'enableDepartments' => 'N',
            ]
        ];
        $filterParams[] = [
            'id' => 'CONTRAGENT',
            'name' => 'Клиент',
            'type' => 'dest_selector',
            'params' => [
                'enableUsers' => 'N',
                'enableDepartments' => 'N',
                'departmentSelectDisable' => 'N',
                'allowUserSearch' => 'N',
                'enableCrm' => 'Y',
                'addTabCrmContacts' => 'Y',
                'enableCrmContacts' => 'Y',
                'addTabCrmCompanies' => 'Y',
                'enableCrmCompanies' => 'Y'
            ]
        ];
        $filterParams[] = [
            'id' => 'VISIT_DATE',
            'name' => 'Дата визита',
            'type' => 'date',
            'default' => true
        ];
        $filterParams[] = [
            'id' => 'STAGE_ID',
            'name' => 'Статус',
            'type' => 'list',
            'items' => [
                'NEW' => 'Новый',
                'PLANNED' => 'Планируемый визит',
                'SUCCESS' => 'Успешно',
                'LOSE' => 'Отменено',
            ],
            'params' => ['multiple' => 'Y'],
            'default' => true,
        ];

        return $filterParams;
    }

    public function getFilter($filterData = []): array
    {

        $filter = [];

        if ($filterData['LABORATORY']) {
            $filter['UF_CRM_25_LABORATORY'] = $filterData['LABORATORY'];
        }
        if ($filterData['REGION']) {
            $filter['UF_CRM_25_REGION'] = $filterData['REGION'];
        }
        if ($filterData['VISIT_DATE_from']) {
            $filter['>=UF_CRM_25_VISIT_DATE'] = $filterData['VISIT_DATE_from'];
        }
        if ($filterData['VISIT_DATE_to']) {
            $filter['<=UF_CRM_25_VISIT_DATE'] = $filterData['VISIT_DATE_to'];
        }

        if ($filterData['ASSIGNED']) {
            $filter['ASSIGNED_BY_ID'] = str_replace('U', '', $filterData['ASSIGNED']);
        }
        if ($filterData['CONTRAGENT']) {
            if (str_contains($filterData['CONTRAGENT'], 'CRMCOMPANY')) {
                $companyId = explode('CRMCOMPANY', $filterData['CONTRAGENT']);
                $filter['COMPANY_ID'] = $companyId[1];
            } else {
                $contactId = explode('CRMCONTACT', $filterData['CONTRAGENT']);
                $filter['CONTACT_ID'] = $contactId[1];
            }
        }
        if ($filterData['STAGE_ID']) {
            $filter['STAGE_ID'] = [];
            foreach ($filterData['STAGE_ID'] as $stageId) {
                if ($stageId == 'NEW') {
                    $filter['STAGE_ID'] = array_merge($filter['STAGE_ID'], self::STAGE_NEW);
                }
                if ($stageId == 'PLANNED') {
                    $filter['STAGE_ID'] = array_merge($filter['STAGE_ID'], self::STAGE_PLAN);
                }
                if ($stageId == 'SUCCESS') {
                    $filter['STAGE_ID'] = array_merge($filter['STAGE_ID'], self::STAGE_SUCCESS);
                }
                if ($stageId == 'LOSE') {
                    $filter['STAGE_ID'] = array_merge($filter['STAGE_ID'], self::STAGE_FAIL);
                }
            }
        }

        if (is_array($filter['!STAGE_ID']))
            $filter['!STAGE_ID'] = array_merge($filter['!STAGE_ID'], self::STAGE_FAIL);
        else if ($filter['!STAGE_ID'])
            $filter['!STAGE_ID'] = array_merge([$filter['!STAGE_ID']], self::STAGE_FAIL);
        else
            $filter['!STAGE_ID'] = self::STAGE_FAIL;


        return $filter;
    }

    public function getVisitInfo($filter): array
    {

        $arUser = self::getDepartmentUser();
        $arUser = $arUser['USER'];

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\Tanais\Alter\Config::VISITS_ENTITY_ID);
        $filter['!STAGE_ID'][] = self::STAGE_FAIL;

        $items = $factory->getItems([
            'select' => [
                'ID',
                'COMPANY_ID',
                'STAGE_ID',
                'CATEGORY_ID',
                'UF_CRM_25_VISIT_DATE',
                'CONTACT_ID',
                'ASSIGNED_BY_ID'
            ],
            'filter' => $filter,
            'order' => ['ID' => 'ASC'],

        ]);

        $arData = [];
        foreach ($items as $item) {

            $assignedId = $item->get('ASSIGNED_BY_ID');
            if (!$arUser[$assignedId]) {
                continue;
            }

            $arData['INFO'][$arUser[$assignedId]]['USER_ID'] = $assignedId;

            $stage_id = $item->get('STAGE_ID');
            if (in_array($stage_id, self::STAGE_NEW)) {
                $arData['INFO'][$arUser[$assignedId]]['DRAFTS'] += 1;
                $arData['TOTAL']['DRAFTS'] += 1;
            }
            if (in_array($stage_id, self::STAGE_PLAN)) {
                $arData['INFO'][$arUser[$assignedId]]['PLANNED'] += 1;
                $arData['TOTAL']['PLANNED'] += 1;
            }
            if (in_array($stage_id, self::STAGE_SUCCESS)) {
                $arData['INFO'][$arUser[$assignedId]]['COMPLETED'] += 1;
                $arData['TOTAL']['COMPLETED'] += 1;
            }
            if (in_array($stage_id, self::STAGE_OFFICE_SUCCESS)) {
                $arData['INFO'][$arUser[$assignedId]]['OFFICE'] += 1;
                $arData['TOTAL']['OFFICE'] += 1;
            }
            if (in_array($stage_id, self::STAGE_DAYOFF_SUCCESS)) {
                $arData['INFO'][$arUser[$assignedId]]['DAYOFF'] += 1;
                $arData['TOTAL']['DAYOFF'] += 1;
            }

            $clientId = $item->get('COMPANY_ID') | $item->get('CONTACT_ID');
            if (($clientId) && (!in_array($stage_id, self::STAGE_NEW))) {
                $arData['INFO'][$arUser[$assignedId]]['CONTRAGENT'][] = $clientId;
            }

            $arData['INFO'][$arUser[$assignedId]]['ID'][] = $item->get('ID');
        }

        return $arData;
    }

    public function getData($filterData = [], $offset, $limit, $sort)
    {
        $this->filterData = $filterData;

        $count = 0;
        $arData = self::getVisitInfo($filterData);

        foreach ($arData['INFO'] as $key => $data) {
            $count++;
            if (empty($key)) {
                continue;
            }

            if (!empty($data['CONTRAGENT'])) {
                $clients += count(array_unique($data['CONTRAGENT']));
            }
            $list[] = [
                'data' => [
                    'REGION' => '<a href="/company/personal/user/' . $data['USER_ID'] . '/">' . $key . '</a>',
                    'DRAFTS' => (empty($data['DRAFTS'])) ? "" : $data['DRAFTS'] . ' виз.',
                    'PLANNED' => (empty($data['PLANNED'])) ? "" : $data['PLANNED'] . ' виз.',

                    'COMPLETED' => (empty($data['COMPLETED'])) ? "" : $data['COMPLETED'] . ' виз.',
                    'OFFICE' => (empty($data['OFFICE'])) ? "" : $data['OFFICE'] . ' дн.',
                    'DAYOFF' => (empty($data['DAYOFF'])) ? "" : $data['DAYOFF'] . ' дн.',

                    'CONTRAGENT' => (empty($data['CONTRAGENT'])) ? "" : count(array_unique($data['CONTRAGENT'])) . ' комп.',
                ],
            ];

            $listForExport[] = [
                'data' => [
                    'REGION' => $key,
                    'DRAFTS' => (empty($data['DRAFTS'])) ? "" : $data['DRAFTS'],
                    'PLANNED' => (empty($data['PLANNED'])) ? "" : $data['PLANNED'],
                    'COMPLETED' => (empty($data['COMPLETED'])) ? "" : $data['COMPLETED'],
                    'CONTRAGENT' => (empty($data['CONTRAGENT'])) ? "" : count(array_unique($data['CONTRAGENT'])),
                ],
            ];
        }

        $list[] = [
            'data' => [
                'REGION' => 'ИТОГО: ',
                'DRAFTS' => (empty($arData['TOTAL']['DRAFTS'])) ? "" : $arData['TOTAL']['DRAFTS'] . ' виз.',
                'PLANNED' => (empty($arData['TOTAL']['PLANNED'])) ? "" : $arData['TOTAL']['PLANNED'] . ' виз.',
                'COMPLETED' => (empty($arData['TOTAL']['COMPLETED'])) ? "" : $arData['TOTAL']['COMPLETED'] . ' виз.',
                'CONTRAGENT' => (empty($clients)) ? "" : $clients . ' комп.',
            ],
        ];

        $listForExport[] = [
            'data' => [
                'REGION' => 'ИТОГО: ',
                'DRAFTS' => (empty($arData['TOTAL']['DRAFTS'])) ? "" : $arData['TOTAL']['DRAFTS'],
                'PLANNED' => (empty($arData['TOTAL']['PLANNED'])) ? "" : $arData['TOTAL']['PLANNED'],
                'COMPLETED' => (empty($arData['TOTAL']['COMPLETED'])) ? "" : $arData['TOTAL']['COMPLETED'],
                'CONTRAGENT' => (empty($clients)) ? "" : $clients,
            ],
        ];


        $return = [
            "DATA" => $list,
            "COUNT" => $count,
            "DATA_EXPORT" => $listForExport,
            'DATA_ACTUAL_TIME' => time(),
            'DATA_CACHED' => false,
            'SORT_FRIENDLY' => true
        ];
        file_put_contents($this->cacheFileName, json_encode($return));

        return $return;
    }
}
