<?php
defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Grid\Options as GridOptions;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);  // echo Loc::GetMessage('TEST_STRING');


\Bitrix\Main\Loader::requireModule('tanais.alter');
\Bitrix\Main\Loader::requireModule('crm');
\Bitrix\Main\Loader::requireModule('ui');
\Bitrix\Main\Loader::requireModule('socialnetwork');
\Bitrix\Main\Loader::requireModule('intranet');


class reportComponent extends CBitrixComponent
{
    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);
    }

    public function executeComponent()
    {
        global $APPLICATION;
        \Bitrix\Main\Loader::registerAutoLoadClasses(null, ['\Tanais\Alter\Report' => $this->__path . '/report.php',]);

        $isAllowed = $this->checkRights();
        if ($isAllowed) {
            global $APPLICATION;

            $reportObject = new \Tanais\Alter\Report();
            $this->arParams['REPORT_OBJECT'] = $reportObject;
            $this->arParams['GRID_ID'] = $reportObject->getGridId();

            $gridOptions = new GridOptions($this->arParams['GRID_ID']);

            $arFilter = $this->getFilter();

            $filter = $arFilter['filter'] ? $arFilter['filter'] : [];
            if ($this->arParams['FILTER']) {
                $filter = array_merge($filter, $this->arParams['FILTER']);
            }

            $info = $reportObject->getData($filter);

            $APPLICATION->SetTitle($reportObject->getTitle());

            $this->arResult['EVENTS'] = $info['EVENTS'];
            $this->arResult['AR_EVENTS'] = $info['AR_EVENTS'];
            $this->arResult['FILTER_UI'] = $reportObject->getFilterParams();

            $this->includeComponentTemplate();
        } else {
            $reportObject = new \Tanais\Alter\Report();
            $APPLICATION->SetTitle($reportObject->getTitle());
            $this->arResult['COMPONENT_NAME'] = $this->__name;
            $this->IncludeComponentTemplate('restricted');
        }
    }

    public function getFilter(): array
    {
        $reportObject = $this->arParams['REPORT_OBJECT'];
        $filterOption = new Bitrix\Main\UI\Filter\Options($this->arParams['GRID_ID']);
        $filterData = $filterOption->getFilter([]);
        $filter = [];
        $filter = $reportObject->getFilter($filterData);
        return [
            'filter' => $filter,
        ];
    }


    public function checkRights(): bool
    {
        //Админам всё разрешено
        if (\Bitrix\Main\Engine\CurrentUser::get()->isAdmin())
            return true;
        //Читаем файл rights.php
        $user = \Bitrix\Main\Engine\CurrentUser::get();
        $userId = $user->getId();
        if ($rights = include(__DIR__ . '/rights.php')) {

            //Безограничений на уровне файла rights.php
            if ($rights === true)
                return true;

            //Напрямую пользватели с правами
            if ((is_array($rights['users'])) and (!empty($rights['users'])))
                if (in_array($userId, $rights['users']))
                    return true;

            //Проверка принадлежности  к группам BitrixFrameWork
            $userGroups = $user->getUserGroups();
            if ((is_array($rights['groups'])) and (!empty($rights['groups'])) and (is_Array($userGroups))) {
                $intersect = array_intersect($userGroups, $rights['groups']);
                if (!empty($intersect))
                    return true;
            }

            //Проверка принадлежности к группам Социальной сети
            if ((is_array($rights['socnet_groups'])) and (!empty($rights['socnet_groups']))) {
                $dbGroups = \CSocNetUserToGroup::GetList([], ['USER_ID' => $userId], false, false, ['GROUP_ID']);
                $socnetGroupsMember = [];
                while ($arGroup = $dbGroups->Fetch())
                    $socnetGroupsMember[] = $arGroup['GROUP_ID'];
                $intersect = array_intersect($socnetGroupsMember, $rights['socnet_groups']);
                if (!empty($intersect))
                    return true;
            }

            //Проверка принадлежности к Отделам
            if ((is_array($rights['departments'])) and (!empty($rights['departments']))) {
                if ($userData = \CUser::GetByID($userId)->Fetch()) {
                    foreach ($userData['UF_DEPARTMENT'] as $depatmentId) {
                        if (in_array($depatmentId, $rights['departments']))
                            return true;
                    }
                }
            }

            if ((is_array($rights['departments_head'])) and (!empty($rights['departments_head']))) {
                $structureData = \CIntranetUtils::GetStructure();
                $heads = [];
                foreach ($rights['departments_head'] as $departmentId)
                    $heads[] = $structureData['DATA'][$departmentId]['UF_HEAD'];
                if (in_array($userId, $heads))
                    return true;
            }

            if ((is_array($rights['departments_deputy'])) and (!empty($rights['departments_deputy']))) {
                //Не реализовано
            }
            return false;
        } else
            return true; //Если нет файла rights.php
    }
}
