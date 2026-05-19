<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class AlterReportStatsComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        try {
            $this->checkModules();
            $this->getStats();
            $this->includeComponentTemplate();
        } catch (Exception $e) {
            ShowError($e->getMessage());
        }
    }

    protected function checkModules()
    {
        if (!\Bitrix\Main\Loader::includeModule('highloadblock')) {
            throw new Exception('Модуль Highload-блоков не установлен');
        }
    }

    protected function getStats()
    {
        if (!$this->arParams['FILTER_URI']) {
            throw new Exception('Необходимо задать FILTER_URI');
        }

        // Получаем HL-блок
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList([
            'filter' => ['=NAME' => 'AlterReportLog']
        ])->fetch();

        if (!$hlblock) {
            throw new Exception('Highload-блок alter_report_log не найден');
        }

        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entityClass = $entity->getDataClass();

        // Получаем статистику
        $result = $entityClass::getList([
            'select' => [
                'COUNT' => new \Bitrix\Main\Entity\ExpressionField('COUNT', 'COUNT(%s)', 'ID'),
                'SUM_TIME' => new \Bitrix\Main\Entity\ExpressionField('SUM_TIME', 'SUM(%s)', 'UF_GENERATED_TIME'),
            ],
            'filter' => [
                '=UF_URI' => $this->arParams['FILTER_URI'],
                '>=UF_TIME' => (new \Bitrix\Main\Type\DateTime())->add('-7 days'),
                '!UF_USER_ID' => 1,
            ],
        ])->fetch();

        $count = $result['COUNT'] ?? 0;

        $sum = $result['SUM_TIME'] ?? 0;
        $sum = number_format($sum, 1, '.', '');

        $avg = ($count > 0 ? $sum / $count : 0);
        $avg = number_format($avg, 1, '.', '');

        $this->arResult = [
            'COUNT' => $count,
            'SUM_TIME' => $sum,
            'AVERAGE_TIME' => $avg,
            'FILTER_URI' => $this->arParams['FILTER_URI']
        ];
    }
}
