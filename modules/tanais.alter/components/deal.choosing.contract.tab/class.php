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
\Bitrix\Main\UI\Extension::load("ui.buttons");


class DealChoosingContractTab extends CBitrixComponent
{

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);
    }

    public function executeComponent()
    {

        $dealId = $_GET['dealId'];
        if ($dealId) {
            $filter = self::getFilter($dealId);
        }

        $gridOptions = new GridOptions('deal_choosing_contract');
        $sort = $gridOptions->GetSorting([
            'sort' => ['UPDATED_TIME' => 'ASC'], // сортировка по умолчанию
        ]);
        $sorting = $sort['sort'];


        $columns = [];

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\Tanais\Alter\Crm\ClientContract::ENTITY_ID);

        $stages = $factory->getStages();
        foreach ($stages as $stage) {
            $arStages[$stage->getStatusId()] = $stage->getName();
        }

        $systemFields = $factory->GetFieldsInfo();
        $userFields = $factory->getUserFieldsInfo();
        $allFields = array_merge($systemFields, $userFields);

        $arDefaultFields = ['TITLE', 'UPDATED_TIME', 'STAGE_ID'];
        $columns[] = ['id' => 'ACTION', 'name' => "Действие", 'default' => true,];

        foreach ($allFields as $code => $columnField) {
            $default = false;
            if (!empty($columnField['TITLE'])) {
                if (in_array($code, $arDefaultFields)) {
                    $default = true;
                }
                $columns[] = ['id' => $code, 'name' => $columnField['TITLE'], 'default' => $default, 'sort' => $code];
            }
        }


        $list = [];
        if (!empty($filter)) {
            $contracts = self::getContracts($filter, $sorting);

            foreach ($contracts as $contract) {
                $dataGrid = $contract;
                $dataGrid['STAGE_ID'] = $arStages[$contract['STAGE_ID']];
                $dataGrid['ACTION'] = '<button ' .
                    'class="ui-btn ui-btn-sm ui-btn-primary" ' .
                    'onclick="bindContractToDeal(' .
                    (int)$dealId . ',' . (int)$contract['ID'] .
                    ')">' .
                    'Выбрать' .
                    '</button>';
                $list[] = [
                    'id' => $contract['ID'],
                    'data' => $dataGrid,
//                'actions' => [[
//                    'text' => 'Выбрать',
//                    'onclick' => "bindContractToDeal("
//                        . (int)$dealId . ", "
//                        . (int)$contract['ID'] .
//                        ");",
//                ]],
                ];
            }
        }

        $count = count($list);

        $this->arResult['columns'] = $columns;
        $this->arResult['rowsData'] = $list;
        $this->arResult['count'] = $count;

        $this->includeComponentTemplate();
    }

    function getFilter($dealId): array
    {
        $arFilterContracts = [];
        $container = Service\Container::getInstance();
        $factory = $container->getFactory(\CCrmOwnerType::Deal);
        $elements = $factory->getItems([
            'select' => ['COMPANY_ID', 'CONTACT_ID'],
            'filter' => [
                'ID' => $dealId,
            ]
        ]);

        foreach ($elements as $element) {
            if ($element->get('COMPANY_ID')) {
                $arFilterContracts['COMPANY_ID'] = $element->get('COMPANY_ID');
            } elseif ($element->get('CONTACT_ID')) {
                $arFilterContracts['CONTACT_ID'] = $element->get('CONTACT_ID');
            }
        }
        if (!empty($arFilterContracts)) {
            $arFilterContracts['!STAGE_ID'] = 'DT1050_19:FAIL';
        }
        return $arFilterContracts;
    }

    function getContracts($filter, $sorting): array
    {
        $contractData = [];
        $container = Service\Container::getInstance();
        $factory = $container->getFactory(\Tanais\Alter\Crm\ClientContract::ENTITY_ID);
        $contracts = $factory->getItems([
            'select' => ['*'],
            'order' => $sorting,
            'filter' => $filter
        ]);

        foreach ($contracts as $contract) {
            $arContract = $contract->getCompatibleData();
            $contractData[] = $arContract;
        }
        return $contractData;
    }
}