<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Loader::includeModule("tanais.alter");
\Bitrix\Main\UI\Extension::load('tanais.alter.report.report_list');
\Bitrix\Main\UI\Extension::load('tanais.alter.fontawesome');
const MODULE_PATH = '/home/bitrix/www/local/modules/tanais.alter/';

class reportList extends CBitrixComponent
{
    public function executeComponent()
    {

        $reports['Лаборатории'][] = [
            'NAME' => 'Новые клиенты ',
            'URL' => '/alter/report/laboratory/newclients/',
            'FILE' => MODULE_PATH . '/public/report/laboratory/newclients/index.php',
        ];

        $reports['Лаборатории'][] = [
            'NAME' => 'Компании. Просроченная задолженность',
            'URL' => '/alter/report/company/debtreceivable/',
            'FILE' => MODULE_PATH . '/alter/report/company/debtreceivable/index.php',
        ];

        $reports['Лаборатории'][] = [
            'NAME' => 'Сделки. Просроченная задолженность',
            'URL' => '/alter/report/deals/debtreceivable/',
            'FILE' => MODULE_PATH . '/alter/report/deals/debtreceivable/index.php',
        ];

        $reports['Регионы'][] = [
            'NAME' => 'Регион компаний',
            'URL' => '/alter/report/regions/regionclientslist/',
            'FILE' => MODULE_PATH . '/public/report/regions/regionclientslist/index.php',
        ];

        $getStats7d = $this->getStats7d();
        $stats = $getStats7d['stats'];
        $users = $getStats7d['users'];


        foreach ($reports as $key1 => $sections) {
            foreach ($sections as $key2 => $section) {

                $reports[$key1][$key2]['DATE_MODIFIED'] = file_exists($section['FILE']) ? date("d.m.Y", $this->getMaxComponentMtime($section['FILE'])) : '-';
                $reports[$key1][$key2]['COUNT'] = $stats[$section['URL']]['COUNT'] ? $stats[$section['URL']]['COUNT'] : '-';
                $reports[$key1][$key2]['AVERAGE_TIME'] = $stats[$section['URL']]['AVERAGE_TIME'] ? $stats[$section['URL']]['AVERAGE_TIME'] : '-';

                foreach ($users as $userItem) {
                    if ($userItem['UF_URI'] == $section['URL'])
                        $reports[$key1][$key2]['USERS'] .= '<nobr>' . $this->getUserFormatted($userItem['UF_USER_ID']) . ' (' . $userItem['COUNT'] . ')</nobr><br>';
                }
            }
        }

        $this->arResult['REPORTS'] = $reports;

        $this->includeComponentTemplate();
    }

    public function getMaxComponentMtime($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $indexMtime = filemtime($filePath);
        $content = file_get_contents($filePath);

        // Удаляем комментарии
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);
        $content = preg_replace('/\/\/.*?(\r?\n|$)/', "\n", $content);

        // Ищем компоненты в коде
        $pattern = '/\$APPLICATION->IncludeComponent\s*\(\s*["\']([^"\']+)["\']/s';
        preg_match_all($pattern, $content, $matches);

        if (!empty($matches[1])) {
            if (in_array('tanais.alter:grid.all', $matches[1])) {
                return $indexMtime;
            }

            $maxMtime = 0;

            // Проверяем время изменения файлов компонент
            foreach ($matches[1] as $component) {
                if ($component == "bitrix:crm.control_panel")
                    continue;

                $componentPath = str_replace(':', '/', $component);
                $componentPath = $_SERVER['DOCUMENT_ROOT'] . '/local/components/' . $componentPath;

                if (is_dir($componentPath)) {
                    $dirIterator = new RecursiveDirectoryIterator($componentPath);
                    $iterator = new RecursiveIteratorIterator($dirIterator);

                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            $fileMtime = $file->getMTime();
                            if ($fileMtime > $maxMtime) {
                                $maxMtime = $fileMtime;
                            }
                        }
                    }
                }
            }
        }

        if (!$maxMtime)
            $maxMtime = $indexMtime;

        return $maxMtime;
    }


    public function getStats7d()
    {
        // Получаем HL-блок
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList([
            'filter' => ['=NAME' => 'AlterReportLog']
        ])->fetch();

        if (!$hlblock) {
            throw new Exception('Highload-блок alter_report_log не найден');
        }

        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entityClass = $entity->getDataClass();

        $dbResult = $entityClass::getList([
            'select' => [
                'UF_URI',
                'COUNT' => new \Bitrix\Main\Entity\ExpressionField('COUNT', 'COUNT(%s)', 'ID'),
                'SUM_TIME' => new \Bitrix\Main\Entity\ExpressionField('SUM_TIME', 'SUM(%s)', 'UF_GENERATED_TIME'),
            ],
            'filter' => [
                '>=UF_TIME' => (new \Bitrix\Main\Type\DateTime())->add('-7 days'),
                '!UF_USER_ID' => 1,
            ],
            'group' => ['UF_URI'],
            'order' => ['UF_URI' => 'ASC', 'COUNT' => 'DESC'],
        ]);

        $stats = [];
        while ($result = $dbResult->fetch()) {
            $count = $result['COUNT'] ?? 0;

            $sum = $result['SUM_TIME'] ?? 0;
            $sum = number_format($sum, 1, '.', '');

            $avg = ($count > 0 ? $sum / $count : 0);
            $avg = number_format($avg, 1, '.', '');

            $stats[$result['UF_URI']] = [
                'COUNT' => $count,
                'SUM_TIME' => $sum,
                'AVERAGE_TIME' => $avg,
                'UF_URI' => $result['UF_URI'],
            ];
        }

        // d($stats);

        $dbResult = $entityClass::getList([
            'select' => [
                'UF_URI',
                'UF_USER_ID',
                'COUNT' => new \Bitrix\Main\Entity\ExpressionField('COUNT', 'COUNT(%s)', 'ID'),
            ],
            'filter' => [
                '>=UF_TIME' => (new \Bitrix\Main\Type\DateTime())->add('-7 days'),
                '!UF_USER_ID' => 1,
            ],
            'group' => ['UF_URI', 'UF_USER_ID'],
            'order' => ['UF_URI' => 'ASC', 'COUNT' => 'DESC'],
        ]);

        $users = $dbResult->fetchAll();

        $return['stats'] = $stats;
        $return['users'] = $users;

        return $return;
    }

    public function getUserFormatted($userId)
    {
        $user = \Bitrix\Main\UserTable::getById($userId)->fetch();

        if ($user) {
            $formattedName = trim(implode(' ', [
                $user['LAST_NAME'],
                $user['NAME'],
                // $user['SECOND_NAME']
            ]));

            return $formattedName;
        }
    }
}
