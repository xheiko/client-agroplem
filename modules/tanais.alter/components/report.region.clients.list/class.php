<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class RegionClientsReportComponent extends CBitrixComponent
{

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);
    }

    public function executeComponent()
    {
        global $APPLICATION;
        $APPLICATION->SetTitle("Отчет список клиентов в привязке к региону");

        $APPLICATION->IncludeComponent(
            'bitrix:crm.control_panel',
            '',
            [
                'ID' => 'CUSTOM_REPORTS_AGR',
                'ACTIVE_ITEM_ID' => 'CUSTOM_REPORTS_AGR',
            ]
        );
        \Bitrix\Main\Loader::registerAutoLoadClasses(null, ['\Tanais\Alter\Report' => $this->__path . '/report.php',]);
        $this->arResult['LIST'] =  Tanais\Alter\Report::getReportList();

        $this->includeComponentTemplate();
    }
}