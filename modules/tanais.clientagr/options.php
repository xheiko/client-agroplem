<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();


$module_id = 'tanais.clientagr';
$moduleId = 'tanais.clientagr';
$siteId = 's1';

\Bitrix\Main\Loader::includeModule($moduleId);

// Рекомендуемая инструкция https://www.1c-bitrix.ru/download/files/ppt/web121115map/klihachev.pdf
/**
 * CControllerClient::GetInstalledOptions($moduleId);
 * формат массива, элементы:
 * 1) ID опции (id инпута)(Берется с помощью COption::GetOptionString($moduleId, $Option[0], $Option[2]) если есть)
 * 2) Отображаемое имя опции
 * 3) Значение по умолчанию (так же берется если первый элемент равен пустой строке), зависит от типа:
 *      checkbox - Y если выбран
 *      text/password - htmlspecialcharsbx($val)
 *      selectbox - одно из значений, указанных в массиве опций
 *      multiselectbox - значения через запятую, указанные в массиве опций
 * 4) Тип поля (массив)
 *      1) Тип (multiselectbox, textarea, statictext, statichtml, checkbox, text, password, selectbox)
 *      2) Зависит от типа:
 *         text/password - атрибут size
 *         textarea - атрибут rows
 *         selectbox/multiselectbox - массив опций формата ["Значение"=>"Название"]
 *      3) Зависит от типа:
 *         checkbox - доп атрибут для input (просто вставляется строкой в атрибуты input)
 *         textarea - атрибут cols
 *
 *      noautocomplete) для text/password, если true то атрибут autocomplete="new-password"
 *
 * 5) Disabled = 'Y' || 'N';
 * 6) $sup_text - ??? текст маленького красного примечания над названием опции
 * 7) $isChoiceSites - Нужно ли выбрать сайт??? флаг Y или N
 */
// ['isYouYes','checkbox','Y',["checkbox",0,'title="ага" data="somedata"'],'N','Красный текст','N'],
// ['isYouText','text','Текст',["text",20],'N','','Y'],
// ['isYouPass','password','Пароль',["password",10,'noautocomplete'=>'Y'],'N','пароль','N'],
// ['isYouTextarea','textarea','Текстареа',["textarea",5,10],'N','чтокак','N'],
// ['isYouSelectbox','selectbox','ko',["selectbox",['lo'=>'po','zo'=>'do','ko'=>'ho','vo'=>'no']]],
// ['isYouMultiselectbox','multiselectbox','ko,lo',["multiselectbox",['lo'=>'po','zo'=>'do','ko'=>'ho','vo'=>'no']]],
// ['isYouStatictext','statictext','Статичный текст',["statictext"]],
// ['isYouStatichtml','statichtml','Статичный <i><b>HTML<b><i>',["statichtml"]],



// Получаем все типы смарт-процессов
$types = \Bitrix\Crm\Model\Dynamic\TypeTable::getList([
    'select' => ['ID', 'TITLE', 'ENTITY_TYPE_ID'],
    'order'  => ['ID' => 'asc'],
]);
$smartProcessList = [];
while ($row = $types->fetch()) {
    $smartProcessList[$row['ENTITY_TYPE_ID']] = "[{$row['ENTITY_TYPE_ID']}] {$row['TITLE']}";
    // print_r($row); echo '<br>';
}

$aTabs = array(
    array(
        'DIV' => 'my_options',
        'TAB' => 'Настройки модуля',

        'OPTIONS' => [
            ['servers', 'Адреса других серверов  (каждый адрес с новой строки и без https)', null, Array("textarea", 5, 52),],
            // ['note' => 'Каждый сервер указывается в новой строке<br>Без https и слеша в конце'],

            'Справочники CRM', //Заголовок
            ['listIdRegion','Регионы', null,['selectbox', $smartProcessList],],
            ['listIdHolding','Холдинги', null,['selectbox', $smartProcessList],],
            ['listIdSoftware','Системы управления стадом', null,['selectbox', $smartProcessList],],
            ['listIdBusiness','Вид деятельности', null,['selectbox', $smartProcessList],],
            ['listIdMycompany','Компании группы AGR', null,['selectbox', $smartProcessList],],
            'Интеграция с другими системами', //Заголовок
            ['mywebhook','Собственный вебхук', null, Array("text", 52),],
            ['webhook1','Входящий вебхук (сервер 1)', null, Array("text", 52),],
            ['webhook2','Входящий вебхук (сервер 2)', null, Array("text", 52),],
            ['webhook3','Входящий вебхук (сервер 3)', null, Array("text", 52),],
            ['webhook4','Входящий вебхук (сервер 4)', null, Array("text", 52),],
            ['webhook5','Входящий вебхук (сервер 5)', null, Array("text", 52),],
            'Доступ', //Заголовок
            ['accessUsers', 'Доступ, ID пользователей через запятую', null, array("text",  52),],
             'Отладка', //Заголовок
             ['logfilename', 'Лог файл', $_SERVER['DOCUMENT_ROOT'].'/local/log/clientAgr.log', Array("text",  52),],

        ],
    )
);

//Если сохраняем
if ($_SERVER['REQUEST_METHOD'] == 'POST' && strlen($_REQUEST['save']) > 0 ) {
    foreach ($aTabs as $aTab) {
        $options = array_filter($aTab['OPTIONS'], 'is_array');        
        __AdmSettingsSaveOptions($moduleId, $options);

    }

    LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID . '&mid_menu=1&mid=' . urlencode($moduleId) .
        '&tabControl_active_tab=' . urlencode($_REQUEST['tabControl_active_tab']) . '&sid=' . urlencode($siteId));
}

//Отрисовка формы в админке
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<form method='post' action='<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($request['mid']) ?>&amp;lang=<?= $request["lang"] ?>' name='tanais_clientagr_settings'>
    <?
    $tabControl->Begin();
    foreach ($aTabs as $aTab) {
        if ($aTab["OPTIONS"]){
            $tabControl->BeginNextTab();
            foreach ($aTab["OPTIONS"] as $key => $option) {
                __AdmSettingsDrawRow($moduleId, $option);
            }
        };
    };
    $tabControl->Buttons(array('btnApply' => false, 'btnCancel' => true, 'btnSaveAndAdd' => false)); 
    $tabControl->End();
    ?>
</form><?
