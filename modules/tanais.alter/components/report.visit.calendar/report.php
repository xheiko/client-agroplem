<?php

namespace Tanais\Alter;

use Bitrix\Main\Page\Asset;
use Bitrix\Main\Loader;

Loader::includeModule('crm');
Loader::includeModule("iblock");
Loader::includeModule("highloadblock");

\Bitrix\Main\UI\Extension::load('tanais.alter.report.calendar');

class Report
{
    protected $moduleId;
    protected $currentUser;
    protected $filterData;

    public function __construct()
    {
        $this->currentUser = \Bitrix\Main\Engine\CurrentUser::get();
        $this->moduleId = 'tanais.alter';
        $this->cacheFileName = sys_get_temp_dir() . "/" . str_replace('\\', '_', static::class) . '.cache';
    }

    public function getTitle(): string
    {
        return 'Календарь визитов сотрудников';
    }

    public function getGridId(): string
    {
        return 'report_manager_calendar240226';
    }

    public function getRegion(): array
    {
        return \Tanais\Alter\Helper::getCrmRegion();
    }

    public function getLaboratory(): array
    {
        return \Tanais\Alter\Crm\Laboratory::getLaboratoryList();
    }

    protected function getColorPalette(): array
    {
        return [
            '#2A7AE4', '#27AE60', '#F39C12', '#8E44AD', '#E74C3C',
            '#16A085', '#2980B9', '#D35400', '#7F8C8D',
            '#1ABC9C', '#3498DB', '#9B59B6', '#34495E',
            '#2ECC71', '#E67E22', '#E84393', '#6C5CE7',
            '#00B894', '#0984E3', '#FD79A8',
            '#A29BFE', '#55EFC4', '#FAB1A0',
            '#FF7675', '#74B9FF', '#81ECEC',
            '#636E72', '#B2BEC3',
        ];
    }

    public function getEvents(): array
    {
        $arEvents = [];

        $arLaboratory = self::getLaboratory();
        $colors = $this->getColorPalette();
        $i = 0;
        
        foreach ($arLaboratory as $laboratoryId => $laboratory) {
            $color = $colors[$i % count($colors)];
            $arEvents[$laboratoryId] = [
                'name' => $laboratory,
                'style' => 'DateAdmissionToTheLaboratory',
                'color' => $color,
            ];
            $i++;
        }
        return $arEvents;
    }

    public function getFilterParams(): array
    {
        return [
            [
                'id' => 'REGION',
                'name' => 'Регион',
                'type' => 'list',
                'items' => self::getRegion(),
                'default' => true,
            ],
            [
                'id' => 'LABORATORY',
                'name' => 'Лаборатория',
                'type' => 'list',
                'items' => self::getLaboratory(),
                'params' => ['multiple' => 'Y'],
                'default' => true,
            ],
            [
                'id' => 'CATEGORY_ID',
                'name' => 'Воронка',
                'type' => 'list',
                'items' => [
                    47 => 'У клиента',
                    48 => 'Офисный день',
                    49 => 'Отсутствие',
                ],
                'params' => ['multiple' => 'Y'],
                'default' => true,
            ],
            [
                'id' => 'ASSIGNED',
                'name' => 'Ответственный',
                'type' => 'dest_selector',
                'params' => [
                    'departmentSelectDisable' => 'N',
                    'enableDepartments' => 'N',
                ]
            ],
            [
                'id' => 'CLIENT',
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
            ],
            [
                'id' => 'VISIT_DATE',
                'name' => 'Дата визита',
                'type' => 'date',
                'default' => true
            ],
            [
                'id' => 'STAGE_ID',
                'name' => 'Статус',
                'type' => 'list',
                'items' => [
                    'NEW' => 'Новый',
                    'PLANNED' => 'Планируемый визит',
                    'AGREE' => 'Согласование',
                    'SUCCESS' => 'Успешно',
                    'LOSE' => 'Отменено',
                ],
                'params' => ['multiple' => 'Y'],
                'default' => true,
            ],
        ];
    }

    public function getUsers(): array
    {
        $arUser = [];
       // $events = self::getEvents();
        $colors = $this->getColorPalette();
        $colorCounter = 0;
        $arParams["SELECT"] = ["UF_DEPARTMENT"];
        $rsUsers = \CUser::GetList([], [], [], $arParams);
        while ($user = $rsUsers->Fetch()) {
            $arUser['NAMES'][$user['ID']] = $user['LAST_NAME'] . ' ' . $user['NAME'];
            //$arUser['DEPARTMENT_USER'][$user['ID']] = $events[$user['UF_DEPARTMENT'][0]];
            $arUser['USER_DEPARTMENT'][$user['UF_DEPARTMENT'][0]][] = $user['ID'];
            $color = $colors[$colorCounter % count($colors)];
            $arUser['COLOR'][$user['ID']] = $color;
            $colorCounter ++;
        }
        return $arUser;
    }

    public function getFilter($filterData = []): array
    {
        $filter = [];

        $arStageSuccess = ['DT1132_47:SUCCESS', 'DT1132_48:SUCCESS', 'DT1132_49:SUCCESS'];
        $arStagePlanned = ['DT1132_47:PREPARATION', 'DT1132_48:PREPARATION', 'DT1132_49:PREPARATION'];
        $arStageAgreement = ['DT1132_48:CLIENT'];
        $arStageDraft = ['DT1132_47:NEW', 'DT1132_48:NEW', 'DT1132_49:NEW'];
        $arStageFail = ['DT1132_47:FAIL', 'DT1132_48:FAIL', 'DT1132_49:FAIL'];

        if ($filterData['REGION']) {
            $filter['UF_CRM_25_REGION'] = $filterData['REGION'];
        }
        if ($filterData['LABORATORY']) {
            $filter['UF_CRM_25_LABORATORY'] = $filterData['LABORATORY'];
        }
        if ($filterData['CATEGORY_ID']) {
            $filter['CATEGORY_ID'] = $filterData['CATEGORY_ID'];
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
        if ($filterData['CLIENT']) {
            if (str_contains($filterData['CLIENT'], 'CRMCOMPANY')) {
                $companyId = explode('CRMCOMPANY', $filterData['CLIENT']);
                $filter['COMPANY_ID'] = $companyId[1];
            } else {
                $contactId = explode('CRMCONTACT', $filterData['CLIENT']);
                $filter['CONTACT_ID'] = $contactId[1];
            }
        }
        if ($filterData['STAGE_ID']) {
            $filter['STAGE_ID'] = [];
            foreach ($filterData['STAGE_ID'] as $stageId) {
                if ($stageId == 'NEW') {
                    $filter['STAGE_ID'] = array_merge($filter['STAGE_ID'], $arStageDraft);
                }
                if ($stageId == 'PLANNED') {
                    $filter['STAGE_ID'] = array_merge($filter['STAGE_ID'], $arStagePlanned);
                }
                if ($stageId == 'AGREE') {
                    $filter['STAGE_ID'] = array_merge($filter['STAGE_ID'], $arStageAgreement);
                }
                if ($stageId == 'SUCCESS') {
                    $filter['STAGE_ID'] = array_merge($filter['STAGE_ID'], $arStageSuccess);
                }
                if ($stageId == 'LOSE') {
                    $filter['STAGE_ID'] = array_merge($filter['STAGE_ID'], $arStageFail);
                }
            }
        }else{
            $filter['STAGE_ID'] = [];
            $filter['STAGE_ID'] = array_merge($filter['STAGE_ID'], $arStagePlanned);
            $filter['STAGE_ID'] = array_merge($filter['STAGE_ID'], $arStageAgreement);
            $filter['STAGE_ID'] = array_merge($filter['STAGE_ID'], $arStageSuccess);
        }

        return $filter;
    }

    public function getContacts(): array
    {
        $arContact = [];
        $contactResult = \CCrmContact::GetListEx([], [], false, false, ['ID', 'FULL_NAME']);
        while ($contact = $contactResult->fetch()) {
            $arContact[$contact['ID']] = $contact['FULL_NAME'];
        }
        return $arContact;
    }

    public function getCompanies(): array
    {
        $arCompany = [];
        $entityResult = \CCrmCompany::GetListEx([], [], false, false, ['ID', 'TITLE']);
        while ($entity = $entityResult->fetch()) {
            $arCompany[$entity['ID']] = $entity['TITLE'];
        }
        return $arCompany;
    }

    public function getData($filterData = []): array
    {
        $this->filterData = $filterData;

        $userId = $this->currentUser;

        $arUser = self::getUsers();
        $arCompany = self::getCompanies();
        $arContact = self::getContacts();

        $arEvents = [];
        $container = \Bitrix\Crm\Service\Container::getInstance();
        $factory = $container->getFactory(\Tanais\Alter\Config::VISITS_ENTITY_ID);
        $elements = $factory->getItemsFilteredByPermissions(
            [
                'filter' => $filterData,
                'select' => ['ID', 'UF_CRM_25_REGION', 'UF_CRM_25_VISIT_DATE', 'COMPANY_ID',
                    'CONTACT_ID', 'ASSIGNED_BY_ID', 'CATEGORY_ID']
            ],
            $userId->getId()
        );

        foreach ($elements as $element) {

            $assignedId = $element->get('ASSIGNED_BY_ID');

            if (!$arUser['NAMES'][$assignedId]) {
                continue;
            }
            $beginTime = $element->get('UF_CRM_25_VISIT_DATE');

            $companyId = $element->get('COMPANY_ID');
            if (!empty($companyId)) {
                $client = $arCompany[$companyId];
            } else {
                $contactId = $element->get('CONTACT_ID');
                $client = $arContact[$contactId];
            }

            $beginTime = new \DateTime($beginTime);


            $arEvents[] = [
                'title' => $arUser['NAMES'][$assignedId],
                'description' => $client,
                'start' => $beginTime->format('Y-m-d'),
                 'color' => $arUser['COLOR'][$assignedId],
                'display' => "list-item",
                'editable' => true,
                'classNames' => "visit",
                'url' => "/page/vizity/vizit/type/" . \Tanais\Alter\Config::VISITS_ENTITY_ID . "/details/{$element->getId()}/",
                'visitId' => $element->getId(),
                'propCodeBegin' => 'UF_CRM_25_VISIT_DATE',
            ];

        }

        return [
            'AR_EVENTS' => $arEvents,
            //  'EVENTS' => self::getEvents(),
        ];
    }
}
