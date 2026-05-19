<?php
defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Application;
use Bitrix\Main;
use \Bitrix\Crm\Service;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\PageNavigation;
use \Bitrix\UI\Buttons\Button;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;

Loader::requireModule('tanais.alter');
Loader::requireModule('crm');
Loader::requireModule('ui');

class CompanyLeadsTab extends CBitrixComponent
{

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);
    }

    public function executeComponent()
    {
        global $USER_FIELD_MANAGER;

        $companyId = $_GET['companyId'];

        $users = \Tanais\Alter\User::getAllUsers();

        $gridOptions = new GridOptions('company_leads');
        $sort = $gridOptions->GetSorting([
            'sort' => ['DATE_CREATE' => 'DESC'], // сортировка по умолчанию
        ]);
        $sorting = $sort['sort'];

        $statuses = CCrmStatus::GetStatusList('STATUS');

        $columns = [];

        $fieldsInfo = \CCrmLead::GetFieldsInfo();

        foreach ($fieldsInfo as $code => &$field) {
            $field['CAPTION'] = \CCrmLead::GetFieldCaption($code);
        }

        $userType = new \CCrmUserType(
            $USER_FIELD_MANAGER,
            \CCrmLead::$sUFEntityID
        );
        $userType->PrepareFieldsInfo($fieldsInfo);

        $arDefaultFields = ['TITLE', 'UF_CRM_RESULT', 'ID', 'DATE_CREATE'];

        foreach ($fieldsInfo as $code => $columnField) {
            $default = false;
            if (!empty($columnField['CAPTION'])) {
                if (in_array($code, $arDefaultFields)) {
                    $default = true;
                }
                $columns[] = ['id' => $code, 'name' => $columnField['CAPTION'], 'default' => $default, 'sort' => $code];
            }
            if (!empty($columnField['TITLE'])) {
                if (in_array($code, $arDefaultFields)) {
                    $default = true;
                }
                $columns[] = ['id' => $code, 'name' => $columnField['TITLE'], 'default' => $default, 'sort' => $code];
            }
        }


        $list = [];
        $leads = self::getLeads($companyId, $sorting);

        foreach ($leads as $lead) {
            $dataGrid = $lead;
            $dataGrid['TITLE'] = '<a href="/crm/lead/details/' . $lead['ID'] . '/">' . $lead['TITLE'] . '</a>';
            $dataGrid['ASSIGNED_BY_ID'] = '<a href="/company/personal/user/' . $lead['ASSIGNED_BY_ID'] . '/">' . $users[$lead['ASSIGNED_BY_ID']] . '</a>';
            $dataGrid['STAGE_ID'] = $statuses[$lead['STAGE_ID']];
            foreach ($lead['PRODUCT_ROWS'] as $product) {
                $dataGrid['PRODUCTS'] .= '<a href = "/crm/catalog/14/product/' . $product['PRODUCT_ID'] . '/">' . $product['PRODUCT_NAME'] . '</a> ' . $product['QUANTITY'] . ' доз <br>';
            }

            $list[] = [
                'data' => $dataGrid,
            ];
        }

        $count = count($list);

        $this->arResult['columns'] = $columns;
        $this->arResult['rowsData'] = $list;
        $this->arResult['count'] = $count;

        $this->includeComponentTemplate();
    }

    function getLeads($companyId, $sorting): array
    {
        $arLeads = [];
        $container = Service\Container::getInstance();
        $factory = $container->getFactory(\CCrmOwnerType::Lead);
        $elements = $factory->getItems([
            'select' => ['*'],
            'order' => $sorting,
            'filter' => [
                'COMPANY_ID' => $companyId,
            ]
        ]);

        foreach ($elements as $element) {
            $arElements = $element->getCompatibleData();
            $arLeads[] = $arElements;
        }
        return $arLeads;
    }
}