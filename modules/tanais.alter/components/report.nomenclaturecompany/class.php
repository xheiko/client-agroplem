<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class reportComponent extends CBitrixComponent
{

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);
    }

    public function executeComponent()
    {
        \Bitrix\Main\Loader::registerAutoLoadClasses(null, ['\Tanais\Alter\Report' => $this->__path . '/report.php',]);
        $reportObject = new \Tanais\Alter\Report();
        $this->arResult['LIST'] = $reportObject->getData([], $offset, $limit, $sort);

        $this->includeComponentTemplate();
    }
}