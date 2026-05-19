<?php
defined('B_PROLOG_INCLUDED') || die;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);  // echo Loc::GetMessage('TEST_STRING');

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UI\Filter\Options;

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
            // d($isAllowed);
            $reportObject = new \Tanais\Alter\Report();
            $this->arResult['reportObject'] = $reportObject;

            if (empty($this->arParams['GRID_ID']))
                $this->arParams['GRID_ID'] = $this->__name;
            if (empty($this->arParams['REPORT_OBJECT']))
                $this->arParams['REPORT_OBJECT'] = $reportObject;
            if (empty($this->arParams['HIDE_FILTER']))
                $this->arParams['HIDE_FILTER'] = false;
            if (empty($this->arParams['HIDE_BUTTON_EXPORT']))
                $this->arParams['HIDE_BUTTON_EXPORT'] = false;

            $isExport = ($_REQUEST['EXPORT_REPORT'] === 'docx');
            if ($isExport) {
                $filterOptions = new Options('fdghytrehertyhevc467u657fdsghcchjjytjukdsdj');
                $rawFilter = $filterOptions->getFilter([]);
                $filter = $reportObject->getFilter($rawFilter);

                $gridOptions = new GridOptions($this->arParams['GRID_ID']);
                $navParams = $gridOptions->GetNavParams();
                $nav = new PageNavigation($this->arParams['GRID_ID']);
                $nav->allowAllRecords(true)->setPageSize($navParams['nPageSize'])->initFromUri();

                $nav->setPageSize(100000);


                $offset = $nav->getOffset();
                $limit = $nav->getLimit();
                $sort = $gridOptions->GetSorting()['sort'];

                $data = $reportObject->getData($filter, $offset, $limit, $sort);

                $this->arResult['LIST_EXPORT'] = $data['DATA_EXPORT'];

                $this->IncludeComponentTemplate('export_business_trip');
                return;
            }

            $this->includeComponentTemplate();
        } else {
            $reportObject = new \Tanais\Alter\Report();
            $APPLICATION->SetTitle($reportObject->getTitle());
            $this->arResult['COMPONENT_NAME'] = $this->__name;
            $this->IncludeComponentTemplate('restricted');
        }
    }

    public
    function checkRights(): bool
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
