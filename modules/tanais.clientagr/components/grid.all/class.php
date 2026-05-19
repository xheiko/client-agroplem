<?php
defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UserTable;
use Bitrix\Main\Grid;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use \Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Page\Asset;
use Tanais\Dashboard\Items;
use \Bitrix\Crm\Service;

\Bitrix\Main\Loader::requireModule('crm');
\Bitrix\Main\Loader::requireModule('sale');
\Bitrix\Main\Loader::requireModule('ui');
\Bitrix\Main\Loader::requireModule('highloadblock');

\Bitrix\Main\UI\Extension::load("ui.alerts");
\Bitrix\Main\UI\Extension::load("ui.dialogs.messagebox");
\Bitrix\Main\UI\Extension::load("ui.buttons");

//Вытягиваем из Работ на объектах. Отталкиваемся от Клиента - сделки. #1
//Добавить поля в смарт процесс Работы - Техника и смена.
class GridExportComponent extends CBitrixComponent
{
    const VERSION = 'v10032025';
    // const LOG_HIGHLOADBLOCK_ID = 3; Переделано на параметр

    protected $currentUser;

    public function __construct(CBitrixComponent $component = null)
    {
        $this->currentUser = \Bitrix\Main\Engine\CurrentUser::get();

        $this->exportAs = (array_key_exists('EXPORT_AS', $_REQUEST) ? $_REQUEST['EXPORT_AS'] : false);
        $this->serverName = COption::GetOptionString("main", "server_name", $_SERVER["SERVER_NAME"]);

        //Если сервер Alta то есть логгирование
        $this->reportLogHLBloclkId = null;
        if ($this->serverName == 'altab24.agrochemist.ru')
            $this->reportLogHLBloclkId = 3;

        parent::__construct($component);
    }


    public function executeComponent()
    {
        global $APPLICATION;
        global $USER_FIELD_MANAGER;

        // $userId = $this->currentUser->getId();
        $startTime = microtime(true); //Замер времени выполнения

        $gridOptions = new GridOptions($this->arParams['GRID_ID']);
        // $currentOptions = $gridOptions->getCurrentOptions();
        $reportObject = $this->arParams['REPORT_OBJECT'];

        $sort = $gridOptions->GetSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
        $arFilter = $this->getFilter();

        $filter = $arFilter['filter'] ? $arFilter['filter'] : [];
        if ($this->arParams['FILTER']) {
            $filter = array_merge($filter, $this->arParams['FILTER']);
        }

        $navParams = $gridOptions->GetNavParams();
        $nav = new PageNavigation($this->arParams['GRID_ID']);

        $nav->allowAllRecords(true)
            ->setPageSize($navParams['nPageSize'])
            ->initFromUri();

        $limit = $nav->getLimit();

        if ($this->exportAs) {
            $nav->setPageSize(100000); // Все элементы, если это экспорт
            $limit = 100000;
        }

        $info = $reportObject->getData($filter, $nav->getOffset(), $limit, $sort['sort']);
        $getDataTime = microtime(true); //Замер времени выполнения

        $this->arResult['COLUMNS'] = $reportObject->getColumns();
        if ($info['SORT_FRIENDLY']) { //Если Данные можно сортировать
            foreach ($this->arResult['COLUMNS'] as &$column) {
                if (empty($column['sort']))
                    $column['sort'] = $column['id'];
            }
            $info = $this->setNavigationAndSort($info, $nav->getOffset(), $limit, $sort['sort'], $this->arResult['COLUMNS']);
        }

        //Форматируем и акцентируем данные для вывода
        $info['DATA'] = $this->setFormat($info['DATA'], $this->arResult['COLUMNS']);
        $APPLICATION->SetTitle($reportObject->getTitle());

        $this->arResult['ROWS_EXPORT'] = $info['DATA_EXPORT'];
        $this->arResult['ROWS'] = $info['DATA'];
        $this->arResult['FILTER_UI'] = $reportObject->getFilterParams();
        $this->arResult['FILTER_PRESETS'] = $info['FILTER_PRESETS'];
        $this->arResult["NAV_OBJECT"] = $nav;
        $this->arResult["COUNT"] = $info['COUNT'];
        $this->arResult["GENERATED_TIME"] = $info['GENERATED_TIME'];
        $this->arResult["DATA_ACTUAL_TIME"] = $info['DATA_ACTUAL_TIME'];
        $this->arResult["DATA_CACHED"] = $info['DATA_CACHED'];
        $this->arResult["FILE_EXPORT_NAME"] = $reportObject->getExportFileName();
        $nav->setRecordCount($info['COUNT']);

        $setNavigationAndSortTime = microtime(true); //Замер времени выполнения

        //Сохраняем статистику
        if ($this->reportLogHLBloclkId) {
            $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($this->reportLogHLBloclkId)->fetch();
            $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
            $entityDataClass = $entity->getDataClass();
            [$url] = explode('?', $_SERVER['REQUEST_URI']);
            $data = [
                'UF_EXCEL_DOWNLOAD' => ($this->exportAs),
                'UF_GENERATED_TIME' => $setNavigationAndSortTime - $startTime,
                'UF_URI' => $url,
                'UF_REPORT_TITLE' => $APPLICATION->GetTitle(),
                'UF_USER_ID' => $this->currentUser->getId(),
                'UF_ROW_COUNT' => $info['COUNT'],
                'UF_FILTER' => serialize($arFilter),
            ];
            $entityDataClass::add($data);
        }

        //Статистика для Администратова $isAdmin = \Bitrix\Main\Engine\CurrentUser::get()->isAdmin();
        if (\Bitrix\Main\Engine\CurrentUser::get()->isAdmin()) {
            $setNavigationAndSortTime = sprintf('%.6f sec.', $setNavigationAndSortTime - $getDataTime);
            $getDataTime = sprintf('%.6f sec.', $getDataTime - $startTime);
            $resourceUsages = getrusage();
?>
            <div class="ui-alert ui-alert-xs ui-alert-success ui-alert-icon-danger">
                <span class="ui-alert-message"><strong>Статистика для Системный Бот (версия компонента <?= self::VERSION ?>). Подготовка данных для отчёта классом : <?= get_class($reportObject) ?></strong>
                    <?
                    echo "<br>Время получения и обработки данных из БД: $getDataTime &nbsp;&nbsp;&nbsp;&nbsp; Время сортировки: $setNavigationAndSortTime<br>";
                    echo "Использование памяти: " . round((memory_get_usage() / 1024 / 1024), 2) . " Мб.&nbsp;&nbsp; Пиковое использование памяти: " . round((memory_get_peak_usage() / 1024 / 1024), 2) . " Мб.<br>";
                    echo "Системная статистика getrusage: user time used " . round(($resourceUsages["ru_utime.tv_sec"]), 2) . " сек., system time used " . round(($resourceUsages["ru_stime.tv_sec"]), 2) . " сек.<br>";
                    ?>
                    <?
                    $APPLICATION->IncludeComponent(
                        "tanais.alta:report.stats",
                        "",
                        [
                            "FILTER_URI" => $url
                        ],
                        $component
                    );
                    ?>
                </span>
            </div>
<?
        }


        if ($this->exportAs) {
            $APPLICATION->RestartBuffer();
            $this->IncludeComponentTemplate('export_' . mb_strtolower($this->exportAs));
            die();
        } else {
            $this->includeComponentTemplate();
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

    private function setNavigationAndSort($data, $offset, $limit, $sort, $columns)
    {
        $data = $this->setSort($data, $sort, $columns); //Сортируем данные
        if ($limit > 0) { //Очищаем от лишних элементов массив значей GRID
            foreach ($data['DATA'] as $key => $row) {
                if (($key < $offset) or ($key >= ($offset + $limit))) {
                    unset($data['DATA'][$key]);
                }
            }
        }
        return $data;
    }

    private function setSort($data = [], $sort = [], $columns)
    {
        if ((empty($sort)) or (empty($data)) or (empty($data['DATA'])))
            return $data;

        $sortParams = $sort;
        $sortDirection = reset($sortParams);
        $sortProp = key($sortParams);

        usort($data['DATA'], function ($a, $b) use ($sortDirection, $sortProp, $columns) {
            global $cachedReportColumnSortType; //Чтобы не искать каждую строку делаем тип сортировки GLOBAL
            if ($a['data'][$sortProp] === $b['data'][$sortProp])
                return 0;
            if (trim($a['data'][$sortProp]) == 'ИТОГО:') // Элменты с содержимым итого всегда в конец
                return 1;
            if (trim($b['data'][$sortProp]) == 'ИТОГО:') // Элменты с содержимым итого всегда в конец
                return -1;
            if (empty($cachedReportColumnSortType))
                foreach ($columns as $column) {
                    if (($column['id'] == $sortProp) or ($column['ID'] == $sortProp) or ($column['Id'] == $sortProp) or ($column['iD'] == $sortProp)) {
                        $cachedReportColumnSortType = $column['sortType'];
                    }
                }
            //Если указан тип сортировки, то определяем тип сортировки
            if ($cachedReportColumnSortType == 'string') {
                $stringA = mb_strtoupper(strip_tags($a['data'][$sortProp]), 'UTF-8');
                $stringB = mb_strtoupper(strip_tags($b['data'][$sortProp]), 'UTF-8');
                if ($sortDirection == "asc")
                    return ($stringA < $stringB) ? -1 : 1;
                else
                    return ($stringA > $stringB) ? -1 : 1;
            }
            //Если тип сортировки не указан в определении колонок, ты пытаемся сами понять тип 
            if (empty($cachedReportColumnSortType))
                if (
                    str_contains(strtoupper($sortProp), 'COMPANY') or
                    str_contains(strtoupper($sortProp), 'NAME') or
                    str_contains(strtoupper($sortProp), 'CLIENT') or
                    str_contains(strtoupper($sortProp), 'CONTACT') or
                    str_contains(strtoupper($sortProp), 'UNIT')
                )
                    $cachedReportColumnSortType = 'string';
            if (empty($cachedReportColumnSortType))
                if (
                    str_contains(strtoupper($sortProp), 'DATE') or
                    str_contains(strtoupper($sortProp), 'LAST_DEAL_CREATE') or
                    str_contains(strtoupper($sortProp), 'LAST_LOGIN_DATE_FROM_PC') or
                    str_contains(strtoupper($sortProp), 'LAST_LOGIN_DATE_FROM_MOBILE')
                )
                    $cachedReportColumnSortType = 'date';
            if (empty($cachedReportColumnSortType)) {
                $numA = intval(preg_replace('/[^-0-9]/', '', strip_tags($a['data'][$sortProp])));
                $numB = intval(preg_replace('/[^-0-9]/', '', strip_tags($b['data'][$sortProp])));

                //Если это не числа, а строки, то сортируем как строки
                if ($numB == 0 && $numA == 0)
                    $cachedReportColumnSortType = 'string';
                else
                    $cachedReportColumnSortType = 'number';
            }
            //Конец определения типа сортировки

            // Начинаем сортировать данные
            if ($cachedReportColumnSortType == 'number') {

                $numA = intval(preg_replace('/[^-0-9]/', '', strip_tags($a['data'][$sortProp])));
                $numB = intval(preg_replace('/[^-0-9]/', '', strip_tags($b['data'][$sortProp])));
                if ($sortDirection == "asc")
                    return ($numA < $numB) ? -1 : 1;
                else
                    return ($numA > $numB) ? -1 : 1;
            }
            if ($cachedReportColumnSortType == 'date') {
                $dateA = strip_tags($a['data'][$sortProp]);
                $dateB = strip_tags($b['data'][$sortProp]);
                $dateA = intval(substr($dateA, 6, 4) . substr($dateA, 3, 2) . substr($dateA, 0, 2) . substr($dateA, 11, 2) . substr($dateA, 14, 2) . substr($dateA, 17, 2)); //Делаем строки типа 20241231
                $dateB = intval(substr($dateB, 6, 4) . substr($dateB, 3, 2) . substr($dateB, 0, 2) . substr($dateB, 11, 2) . substr($dateB, 14, 2) . substr($dateB, 17, 2));
                if ($sortDirection == "asc")
                    return $dateA - $dateB;
                else
                    return $dateB - $dateA;
            }
            if ($cachedReportColumnSortType == 'monthStringRussian') {
                // требуется реализация
            }
        });
        return $data;
    }

    // Форматируем содержимое
    // $columns[] = [
    //     'id' => 'ALTAINK_QUANTITY',
    //     'name' => 'Остатки поставщика',
    //     'default' => true,
    //     "align" => "right",
    //     'measureUnit'  => '%  доз',
    //     'decimals'      => 0,
    //     'decimalSeparator'     => '.',
    //     'thousandsSeparator'   => ' ',
    //     'clearZero'     => true,
    //     'prefix'   => '<i class="fa-regular fa-earth-americas"></i> ',
    //     'style' => 'color:#1B5E20;font-weight:800;',
    //     'accent'   => [
    //         'Активный' => 'color:#F57F17;',
    //         'Неактивный' => 'font-weight: 600;color:#FFC107;',
    //         'Активный. Новый' => 'font-weight: 600;'    
    //         'more' => [
    //             'value' => 0,
    //             'style' => 'color:#1B5E20;font-weight:800;',
    //         ],
    //         'equal' => [
    //             'value' => 0,
    //             'style' => 'color:#F57F17;',
    //         ],
    //         'less' => [
    //             'value' => 0,
    //             'style' => 'color:#B71C1C;font-weight:600;',
    //         ],
    //     ],
    // ];
    private function setFormat($data = [], $columns = [])
    {
        if ((empty($columns)) or (empty($data)))
            return $data;

        $format = [];
        foreach ($columns as $column)
            $format[$column['id']] = $column;

        foreach ($data as &$row) {
            // d($row);
            foreach ($row['data'] as $columndId => &$cell) {
                if ($cell === null)
                    $cell = '';
                if (($cell == "0") and ($format[$columndId]['clearZero'] === true))
                    $cell = '';
                if ((!empty($cell)) or ($cell === "0")) {
                    $initialCell = $cell;
                    if (isset($format[$columndId]['decimals']) && isset($format[$columndId]['decimalSeparator']) && isset($format[$columndId]['thousandsSeparator']) && ($cell) && is_numeric($cell))
                        $cell = number_format($cell, $format[$columndId]['decimals'], $format[$columndId]['decimalSeparator'], $format[$columndId]['thousandsSeparator']);
                    if ($format[$columndId]['measureUnit'])
                        $cell = preg_replace('/%/', $cell, $format[$columndId]['measureUnit'], 1);
                    // $cell = str_replace('%', $cell, $format[$columndId]['measureUnit']);
                    if ($format[$columndId]['prefix'])
                        $cell = $format[$columndId]['prefix'] . $cell;
                    if ($format[$columndId]['suffix'])
                        $cell = $cell . $format[$columndId]['suffix'];
                    if ($format[$columndId]['style'])
                        $cell = '<span style="' . $format[$columndId]['style'] . '">' . $cell . "</span>";
                    if ($format[$columndId]['accent']) {
                        foreach ($format[$columndId]['accent'] as $value => $accent) {
                            if ($initialCell === $value)
                                $cell = '<span style="' . $accent . '">' . $cell . "</span>";
                        }
                        if ((isset($format[$columndId]['accent']['more']['value'])) and (isset($format[$columndId]['accent']['more']['style'])))
                            if ($initialCell > $format[$columndId]['accent']['more']['value'])
                                $cell = '<span style="' . $format[$columndId]['accent']['more']['style'] . '">' . $cell . "</span>";
                        if ((isset($format[$columndId]['accent']['equal']['value'])) and (isset($format[$columndId]['accent']['equal']['style'])))
                            if ($initialCell == $format[$columndId]['accent']['equal']['value'])
                                $cell = '<span style="' . $format[$columndId]['accent']['equal']['style'] . '">' . $cell . "</span>";
                        if ((isset($format[$columndId]['accent']['less']['value'])) and (isset($format[$columndId]['accent']['less']['style'])))
                            if ($initialCell < $format[$columndId]['accent']['more']['value'])
                                $cell = '<span style="' . $format[$columndId]['accent']['less']['style'] . '">' . $cell . "</span>";
                    }
                }
            }
        }

        return $data;
    }
}
