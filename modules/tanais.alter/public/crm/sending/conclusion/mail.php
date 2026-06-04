<?php
/*define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define('BX_NO_ACCELERATOR_RESET', true);
define('CHK_EVENT', true);
define('BX_WITH_ON_AFTER_EPILOG', true);*/

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CJSCore::Init(array('ajax', 'json', 'ls', 'session', 'jquery', 'popup', 'pull'));
global $USER, $APPLICATION;
$APPLICATION->ShowHeadStrings();
$APPLICATION->ShowHead();
\Bitrix\Main\UI\Extension::load('ui.entity-selector');
\Bitrix\Main\Loader::includeModule('disk');
\Bitrix\Main\Loader::includeModule('crm');
\Bitrix\Main\Loader::includeModule('iblock');
const MODULE_PATH = '/home/bitrix/www/local/modules/tanais.alter';
const MAX_FILESIZE_IN_BODY = 9800000;

use Bitrix\Crm\Integration\Main\UISelector;
use \Bitrix\Crm\Service;

$APPLICATION->SetAdditionalCss("/alter/crm/sending/conclusion/style.css");
?>
<script src="https://code.jquery.com/jquery-3.6.0.js"
    integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk="
    crossorigin="anonymous"></script>

<?php

$user = $USER->GetID(); // bitrix_support = 26
CModule::IncludeModule("main");
if (CModule::IncludeModule("crm")) {

    $DealUfCode_DateKD = 'UF_CRM_1573737968714';     // Сделка   "Дата КД"

    $DealUfCode_Tank = 'UF_CRM_1647427589046';       // Сделка   "Танк" Число
    $DealUfCode_CollProblems = 'UF_CRM_1656079065732';       // Сделка Проблемы с отбором

    $DealUfCode_Sender = 'UF_CRM_1639661869';     // Сделка   Список - "Отправка результатов" : Selex/Simplex/Плинор
    // $DealUfCode_Report = 'UF_CRM_1608030724297';  // Сделка   Файл   - "Заключения на отправку"
    $DealUfCode_Number = 'UF_CRM_ORDER_NUMBER';        // Сделка   Строка - "Номер заказа"
    $DealUfCode_Platform = 'UF_CRM_1618824524349';  // Сделка   Строка - "Площадка"

    $DealUfCode_qDone = 'UF_CRM_1570102184792';  // Сделка   Проб сделано - new
    $DealUfCode_qSour = 'UF_CRM_1572866891387';  // Сделка   Проб скисших - new
    $DealUfCode_qSmall = 'UF_CRM_1573211682076';  // Сделка   Проб с малым объемом - new
    $DealUfCode_qComplexSour = 'UF_CRM_1656079562166';  // Сделка   Комплексное скисание Да/Нет

    $DealUfCode_dateConclusionFact = 'UF_CRM_1570104297940'; // Сделка Дата выдачи заключения (факт)

    //$selexP_company_uf_code = 'UF_CRM_1649078587';        // Компания Строка - Ссылка для загрузки базы Селекс (площадка)
    //  $selexL_company_uf_code = 'UF_CRM_1649078608';        // Компания Строка - Ссылка для загрузки базы Селекс (ссылка)

    $fileUF = 'UF_CRM_4_FILES';

    $labPropertyId = 66; // ID свойства e-mail в списке лабораторий

    $fileLink = '2/';

    $linkUpstream = 'https://attachments.data.agroplem.ru/';

    $replacements = [
        '/upload/main/' => 'm/',
        '/upload/disk/' => 'd/',
        '/upload/crm/' => 'c/',
        '/upload/documentgenerator/' => 'dg/',
    ];

    $idSmartProcessElement = json_decode($_REQUEST['PLACEMENT_OPTIONS'], true)['ID'];

    $container = Service\Container::getInstance();
    $factory = $container->getFactory(1042);
    $elements = $factory->getItem($idSmartProcessElement);
    $smartProcessElements = $elements->getData();

    if (!$smartProcessElements['PARENT_ID_2']) { ?>
        <h1 style="color: red; text-align: center; margin-top: 20%;">Не выбрана сделка!</h1>
<?php
        die();
    }
    $id = $smartProcessElements['PARENT_ID_2'];
    // if (is_null($id) && $server == 'АО')  $id = 5097; //Тестовая сделка
    // if (is_null($id) && $server == 'ООО') $id = 983;  //Тестовая сделка
    // var_export($id);
    $deal = CCrmDeal::GetByID($id);
    $hasGenomicAssessment = false;
    $products = CAllCrmProductRow::LoadRows('D', $id);
    foreach ($products as $key => $product) {
        $article = Tanais\Alter\Crm\Catalog::getFieldValue($product['PRODUCT_ID'], 'ARTICLE_NUMBER');
        if ($article == 'GEBVOF_FEMALE' || $article == 'GEBVOF_MALE') {
            $hasGenomicAssessment = true;
        }
    }

    $dealUF = CCrmDeal::GetUserFields(false, $id);

    $arContacts = [];
    $company = CCrmCompany::GetByID($deal['COMPANY_ID']);
    $companyFields = CCrmFieldMulti::GetList(array(), array('ELEMENT_ID' => $deal['COMPANY_ID'], 'ENTITY_ID' => 'COMPANY', 'TYPE_ID' => 'EMAIL'));
    while ($companyField = $companyFields->Fetch()) {
        $resultContactSlug = UISelector\CrmEntity::getMultiKey(
            UISelector\CrmCompanies::PREFIX_SHORT . $companyField['ELEMENT_ID'],
            $companyField['VALUE']
        );
    }
    $companyUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields('CRM_COMPANY', $deal['COMPANY_ID']);
    //    $linkSelecsArea = $companyUF[$selexP_company_uf_code]['VALUE'];
    //    $linkSelecsLinks = $companyUF[$selexL_company_uf_code]['VALUE'];
    //    if (!empty($companyUF[$selexO_company_uf_code]['VALUE'])) {
    //        $linkSelecs = $companyUF[$selexO_company_uf_code]['VALUE'];
    //    }
    //    if (!empty($linkSelecsArea) && !empty($linkSelecsLinks)) {
    //        if (count($linkSelecsArea) > 1 && count($linkSelecsLinks) > 1) {
    //            foreach ($linkSelecsArea as $key => $value) {
    //                $linkSelecsArr[$value] = $linkSelecsLinks[$key];
    //            }
    //            $linkSelecs = $linkSelecsArr[$dealUF[$DealUfCode_Platform]['VALUE']];
    //
    //        } else if (count($linkSelecsLinks) == 1) {
    //            $linkSelecs = $linkSelecsLinks[0];
    //        }
    //    }


    $contacts = \Bitrix\Crm\Binding\DealContactTable::getDealBindings($id);
    $contactsEmail = [];
    foreach ($contacts as $contact) {
        $contactFields = CCrmFieldMulti::GetList(array(), array('ELEMENT_ID' => $contact['CONTACT_ID'], 'ENTITY_ID' => 'CONTACT', 'TYPE_ID' => 'EMAIL'));
        $cont = CCrmContact::GetByID($contact['CONTACT_ID']);
        while ($contactField = $contactFields->Fetch()) {
            // d($contactField);
            $contactsEmail[$contactField['ELEMENT_ID']]['FULL_NAME'] = $cont['FULL_NAME'];
            $contactsEmail[$contactField['ELEMENT_ID']]['EMAIL'][$contactField['ID']] = $contactField['VALUE'];
            $contactsEmailScript .= $contactField['VALUE'] . ',';
            $contactsEmailScriptName .= $cont['FULL_NAME'] . ',';
            $contactsEmailHTML[] = $cont['FULL_NAME'] . "[" . $contactField['VALUE'] . ']'; //Для вывода
            $resultContactSlug = UISelector\CrmEntity::getMultiKey(
                UISelector\CrmContacts::PREFIX_SHORT . $contact['CONTACT_ID'],
                $contactField['VALUE']
            );
            $arContact = [$resultContactSlug => 'contacts'];
            $arContacts = array_merge($arContact, $arContacts);
        };
    }
    $contactsEmailScript = substr($contactsEmailScript, 0, -1);
    $contactsEmailScriptName = substr($contactsEmailScriptName, 0, -1);

    // ЕСЛИ НЕ ВЫБРАН ТИП ПИСЬМА

    $topic = 'Результаты исследований Агроплем, заказ № ' . str_replace('"', '', $dealUF[$DealUfCode_Number]['VALUE']);
    $topicMolecularExpertise = 'Отчеты о проведении молекулярной генетической экспертизы';
    $company = CCrmCompany::GetByID($deal['COMPANY_ID']);
    //d($company);
    if (!empty($company)) {
        $topic .= ', ' . str_replace('"', '', $company['TITLE']);
        $topicMolecularExpertise .= ' ' . str_replace('"', '', $company['TITLE']);
    }
    $area = str_replace('"', '', $dealUF[$DealUfCode_Platform]['VALUE']);
    if (!empty($area)) {
        $topic .= ', площадка ' . $area;
    }

    if ($dealUF['UF_CRM_LABORATORY']['VALUE'] == '800') $labaratory = 'milk';     //АО Молоко
    else if ($dealUF['UF_CRM_LABORATORY']['VALUE'] == '811') $labaratory = 'milkEKB';  //АО Почва
    else if ($dealUF['UF_CRM_LABORATORY']['VALUE'] == '801') $labaratory = 'genetics';  //АО генетика
    else if ($dealUF['UF_CRM_LABORATORY']['VALUE'] == '802') $labaratory = 'soil';      //ООО Почва
    else if ($dealUF['UF_CRM_LABORATORY']['VALUE'] == '803') $labaratory = 'feed';      //ООО Корма
    else if ($dealUF['UF_CRM_LABORATORY']['VALUE'] == '804') $labaratory = 'microbio';  //ООО Микробиология
    else if ($dealUF['UF_CRM_LABORATORY']['VALUE'] == '805') $labaratory = 'microbiosoil';      //ООО Почва

    else $labaratory = 'all';

    $labId = $dealUF['UF_CRM_LABORATORY']['VALUE'];


    $res = CIBlockElement::GetProperty(17, $labId, array(), ['ID' => $labPropertyId]);
    $labEmail = $res->Fetch();


    $tryMade = $dealUF[$DealUfCode_qDone]['VALUE'];  // Проб сделано
    $trySour = $dealUF[$DealUfCode_qSour]['VALUE'];  // Проб скисших
    $trySmallVolume = $dealUF[$DealUfCode_qSmall]['VALUE']; // Проб с малым объемом
    $complexSouring = $dealUF[$DealUfCode_qComplexSour]['VALUE']; //Комплексное скисание

    $tryMade = ($tryMade / 100) * 10;

    $attachments = [];
    $arFiles = [];
    $fileSize = 0;
    $fileLinksHide = '';
    $fileLinksShow = '';
    $filess = $smartProcessElements[$fileUF];
    if (is_countable($filess) && count($filess) != 0) {
        foreach ($filess as $key => $file) {
            if ($key != 0) {
                //$files .= ', ';
            }
            $f = CFile::GetByID($file);

            $f = $f->Fetch();
            // d($f);
            //$arFiles[] = '<a href="https://' . $_SERVER['SERVER_NAME'] . CFile::GetPath($file) . '">' . $f['FILE_NAME'] . '</a>';
            $files .= '<span class="files">' . $f['ORIGINAL_NAME'] . '</span>';
            $fileSize = $fileSize + $f['FILE_SIZE'];
            $attachments[$f['ID']]['Content-Type'] = $f['CONTENT_TYPE'];
            $attachments[$f['ID']]['filename'] = $f['ORIGINAL_NAME'];

            if ($fileSize < MAX_FILESIZE_IN_BODY)
                $attachments[$f['ID']]['file'] = base64_encode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . CFile::GetPath($f['ID'])));
            // echo File::GetPath($f['ID'])."<br>";
            $filePath = CFile::GetPath($file);
            $filePathArr = explode('/', $filePath);
            end($filePathArr);
            $filePathArr[key($filePathArr)] = str_replace(" ", "%20", $filePathArr[key($filePathArr)]);
            $filePath = implode('/', $filePathArr);

            $fileLinksShow .= '<a href="https://' . $_SERVER['SERVER_NAME'] . $filePath . '">' . $f['ORIGINAL_NAME'] . '</a><br>';

            foreach ($replacements as $old_path => $new_path) {
                if (strpos($filePath, $old_path) !== false) {
                    $hideFilePath = str_replace($old_path, $new_path, $filePath);
                }
            }

            $fileLinksHide .= '<a href="' . $linkUpstream . $fileLink . $hideFilePath . '">' . $f['ORIGINAL_NAME'] . '</a><br>';
            // $fileLinksHide .= '<a href="' . $linkUpstream . $fileLink . $hideFilePath . '">' . $filePath . '</a><br>'; //DEbug

            $arFiles[] = $f['ID'];
        }
    }

    //1,4 - коэффициент увеличения размера base64. + 20000 на заголовки и тело письма
    $fileSize = $fileSize * 1.4 + 20000;

    $type = 'Отправка результатов клиенту по email';
    $fromEmail = 'noreply@agroplem.ru';
    $fromName = 'Лаборатория Агроплем';

    if ($labaratory == 'milk') {
        // $fromName = 'Молочная лаборатория Агроплем';
        //  $fromEmail = $labEmail["VALUE"];
        $toCopy = [];

        //ВНИМАНИЕ! $email_body для МОЛОКА ПЕРЕСОЗДАЕТСЯ В КОДЕ НИЖЕ
        $email_body = 'Добрый день!<br>';
        $email_body = '';
        $email_body = '<p>Если у вас остались вопросы, пожалуйста, свяжитесь с Назимой Нартуповой по телефону <a href="tel:+79266029089">+7 (926) 602-90-89</a> или по эл. почте – <a href="mailto:nnartupova@agroplem.ru">nnartupova@agroplem.ru</a></p>';
        $email_body .= '<br><br><p>Спасибо за заказ,</p><br>';
        $email_body .= '<p>С уважением,<br>';
        $email_body .= 'Команда лаборатории<br>';
        $email_body .= 'селекционного контроля качества молока<br>';
        $email_body .= '<a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p>';
        $email_body .= '<p>Мы очень хотим, чтобы наш сервис всегда был на высоте. И вы можете нам в этом помочь. Оцените, пожалуйста, качество предоставленной услуги по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>. Потратив несколько минут, Вы поможете нам стать лучше.</p><br><br>';
    }
    if ($labaratory == 'milkEKB') {
        //$fromName = 'Молочная лаборатория Агроплем Екатеринбург';
        //$fromEmail = 'milk_orders@agroplem.ru';
        // $fromEmail = $labEmail["VALUE"];

        //ВНИМАНИЕ! $email_body для МОЛОКА ПЕРЕСОЗДАЕТСЯ В КОДЕ НИЖЕ
        $email_body = 'Добрый день!<br>';
        $email_body = '';
        $email_body = '<p>Если у вас остались вопросы, пожалуйста, свяжитесь с Дарьей Торгониной  по телефону <a href="tel:+79250204873">+7 (925) 020-48-73</a> или по эл. почте – <a href="mailto:dtorgonina@agroplem.ru">dtorgonina@agroplem.ru</a></p>';
        $email_body .= '<br><br><p>Спасибо за заказ,</p><br>';
        $email_body .= '<p>С уважением,<br>';
        $email_body .= 'Команда лаборатории<br>';
        $email_body .= 'селекционного контроля качества молока<br>';
        $email_body .= '<a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p>';
        $email_body .= '<p>Мы очень хотим, чтобы наш сервис всегда был на высоте. И вы можете нам в этом помочь. Оцените, пожалуйста, качество предоставленной услуги по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>. Потратив несколько минут, Вы поможете нам стать лучше.</p><br><br>';
    }

    if ($labaratory == 'genetics') {
        // $fromName = 'Лаборатория генетики Агроплем';
        //$fromEmail = 'gen_orders@agroplem.ru';
        // $fromEmail = $labEmail["VALUE"];

        $topic = 'Агроплем, Заключение по заказу ' . str_replace('"', '', $dealUF[$DealUfCode_Number]['VALUE']);
        if (!empty($company)) {
            $topic .= " ( " . str_replace('"', '', $company['TITLE']) . ")";
        }

        if ($hasGenomicAssessment) {

            $email_body1 = '<div id="bodyMail">Добрый день!<br><br>';
            $email_body1 .= '<p>Ваш заказ был выполнен и по его результатам мы подготовили заключение, которое прикрепляем к данному письму. Большое спасибо за заказ, мы будем рады сотрудничать с Вами в области внедрения геномных технологий в племенное и товарное животноводство и в будущем.</p><br></div>';

            $email_body1 .= '<div id="mailFiles" class="mailFiles">';
            $email_body1 .= $fileLinksHide;
            $email_body1 .= '</div>';

            $email_body1 .= '<div id="signature"><br><p>Оригинал заключения мы перешлем Вам по почте.</p><br>';
            $email_body1 .= '<br><p>ВАЖНАЯ ИНФОРМАЦИЯ! Начиная с июля 2024 года в рамках предоставления отчетов по геномной оценке племенной ценности на базе CDCB для животных голштинской породы мы публикуем результаты носительства нового летального гаплотипа HMW (Синдром мышечной слабости). В связи с тем, что частота встречаемости нового гаплотипа по нашим данным может достигать 18% в отечественной популяции голштинской породы, мы вынесли данное заболевание в отдельный столбец в отчете.</p><br>';
            $email_body1 .= '<br><p>Если у вас остались вопросы, пожалуйста, свяжитесь с Назимой Нартуповой по телефону <a href="tel:+79266029089">+7 (926) 602-90-89</a>  (Звонок, WhatsApp, Telegram), <a href="tel:+74993711919">+7 (499) 371-19-19</a> или по эл. почте – <a href="mailto:nnartupova@agroplem.ru">nnartupova@agroplem.ru</a></p><br>';
            $email_body1 .= '<br><p>Спасибо за заказ,</p><br>';
            $email_body1 .= '<p>С уважением,<br>';
            $email_body1 .= 'Команда лабораторно-исследовательского центра<br>';
            $email_body1 .= '<a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';
            $email_body1 .= '<p>Мы очень хотим, чтобы наш сервис всегда был на высоте. И вы можете нам в этом помочь. Оцените, пожалуйста, качество предоставленной услуги по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>. Потратив несколько минут, Вы поможете нам стать лучше.</p><br><br></div>';
        } else {

            $email_body1 = '<div id="bodyMail">Добрый день!<br><br>';
            $email_body1 .= '<p>Ваш заказ был выполнен и по его результатам мы подготовили заключение, которое прикрепляем к данному письму. Большое спасибо за заказ, мы будем рады сотрудничать с Вами в области внедрения геномных технологий в племенное и товарное животноводство и в будущем.</p><br></div>';

            $email_body1 .= '<div id="mailFiles" class="mailFiles">';
            $email_body1 .= $fileLinksHide;
            $email_body1 .= '</div>';

            $email_body1 .= '<div id="signature"><br><p>Оригинал заключения вместе с Вашими копиями остальных документов мы перешлем Вам по почте.</p><br>';
            $email_body1 .= '<br><p>Если у вас остались вопросы, пожалуйста, свяжитесь с Назимой Нартуповой по телефону <a href="tel:+79266029089">+7 (926) 602-90-89</a>  (Звонок, WhatsApp, Telegram), <a href="tel:+74993711919">+7 (499) 371-19-19</a> или по эл. почте – <a href="mailto:nnartupova@agroplem.ru">nnartupova@agroplem.ru</a></p><br>';
            $email_body1 .= '<br><p>Спасибо за заказ,</p><br>';
            $email_body1 .= '<p>С уважением,<br>';
            $email_body1 .= 'Команда лабораторно-исследовательского центра<br>';
            $email_body1 .= '<a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';
            $email_body1 .= '<p>Мы очень хотим, чтобы наш сервис всегда был на высоте. И вы можете нам в этом помочь. Оцените, пожалуйста, качество предоставленной услуги по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>. Потратив несколько минут, Вы поможете нам стать лучше.</p><br><br></div>';
        }
        $email_body2 = 'Добрый день!<br><br> 
        <p>Ваш заказ был выполнен и по его результатам мы подготовили заключение, в котором для некоторых животных подобрались один или несколько возможных предков. Вы не могли бы предоставить нам информацию, работали ли подобранные предки в это время, чтобы мы могли подготовить итоговое заключение? </p><br>';

        $email_body2 .= '<div id="mailFiles" class="mailFiles">';
        $email_body2 .= $fileLinksHide;
        $email_body2 .= '</div>';

        $email_body2 .= '<br><p>Информацию необходимо направить нашему специалисту Назиме Нартуповой на электронную почту <a href="mailto:nnartupova@agroplem.ru">nnartupova@agroplem.ru</a>. Если у Вас остались вопросы Вы также можете обратиться к ней по телефону <a href="tel:+79266029089">+7 (926) 602-90-89</a>.</p><br>
        <p>С уважением,<br>
        Команда лабораторно-исследовательского центра<br>
        <a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';

        $email_body3 = 'Добрый день!<br><br> 
        <p>Направляем Вам повторно заключение по заказу ' . $dealUF[$DealUfCode_Number]['VALUE'] . ', в котором для некоторых животных подобрались один или несколько возможных предков. Просим предоставить нам информацию, работали ли подобранные предки в это время, чтобы мы могли подготовить итоговое заключение?</p><br>';

        $email_body3 .= '<div id="mailFiles" class="mailFiles">';
        $email_body3 .= $fileLinksHide;
        $email_body3 .= '</div>';

        $email_body3 .= '<p>Информацию необходимо направить нашему специалисту Назиме Нартуповой на электронную почту <a href="mailto:nnartupova@agroplem.ru">nnartupova@agroplem.ru</a>. Если у Вас остались вопросы Вы также можете обратиться к ней по телефону <a href="tel:+79266029089">+7 (926) 602-90-89</a>.</p><br>
        <p>В случае отсутствия ответа в течение 30 календарных дней с даты отправки данного письма мы удалим подбор животных, сделанный лабораторией молекулярно-генетической экспертизы Агроплем и Вам будет направлено итоговое заключение без подбора. Пробы будут утилизированы.</p><br>
        <p>В случае обращения о проведении повторного подбора по заказу ' . $dealUF[$DealUfCode_Number]['VALUE'] . ' по истечению 30 календарных дней с даты направления данного письма, услуга будет платной.</p><br>
        <p>С уважением,<br>
        Команда лабораторно-исследовательского центра<br>
        <a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';

        $email_body4 = 'Добрый день!<br><br> 
        <p>Направляем Вам отчеты о проведении молекулярной генетической экспертизы по форме, утвержденной Приказом Минсельхоза России № 713 от 30.10.2025, с целью возмещения части затрат при реализации мероприятий по развитию геномной селекции в области племенного животноводства.</p><br>';

        $email_body4 .= '<div id="mailFiles" class="mailFiles">';
        $email_body4 .= $fileLinksHide;
        $email_body4 .= '</div>';

        $email_body4 .= '<br><p>Если Вам необходимы оригиналы отчетов, сообщите нам об этом, мы подготовим и отправим Вам.</p><br>';

        $email_body4 .= '<p>В случае возникновения вопросов, пожалуйста, свяжитесь с Назимой Нартуповой по телефону <a href="tel:+79266029089">+7 (926) 602-90-89</a>  (Звонок, WhatsApp, Telegram), <a href="tel:+74993711919">+7 (499) 371-19-19</a> или по эл. почте – <a href="mailto:nnartupova@agroplem.ru">nnartupova@agroplem.ru</a></p><br>';

        $email_body4 .= '<p>С уважением,<br>
        Команда лабораторно-исследовательского центра<br>
        <a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';

        $email_body5 = 'Добрый день!<br><br> 
        <p>Ваш заказ был выполнен и добавлен в динамический отчет, ссылку* на который прикрепляем к данному письму:</p><br>';

        $email_body5 .= '<br><p>*Ссылка актуальна, пока в отчет не будут добавлены новые животные из следующих заказов, или пока не изменятся данные, когда произойдет международная переоценка, эти изменения мы внесем самостоятельно, Ваше участие не требуется, об изменении ссылки сообщим дополнительно. Сохраните ее в закладках, при необходимости работы с отчетом переходите по ссылке, скачивайте актуальный файл на локальный компьютер и осуществляйте с ним любые манипуляции. Так Вы сохраните исходные данные, которые всегда будут доступны по ссылке.</p><br>';

        $email_body5 .= '<p>Большое спасибо за заказ, мы будем рады сотрудничать с Вами в области внедрения геномных технологий в племенное и товарное животноводство и в будущем</p><br>';

        $email_body5 .= '<p>Если у вас остались вопросы, пожалуйста, свяжитесь с Назимой Нартуповой по телефону <a href="tel:+79266029089">+7 (926) 602-90-89</a>  (Звонок, WhatsApp, Telegram), <a href="tel:+74993711919">+7 (499) 371-19-19</a> или по эл. почте – <a href="mailto:nnartupova@agroplem.ru">nnartupova@agroplem.ru</a></p><br>';

        $email_body5 .= '<p>С уважением,<br>
        Команда лабораторно-исследовательского центра<br>
        <a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';

        $email_body6 = 'Добрый день!<br>
        <p>Направляем Вам отчеты о проведении молекулярной генетической экспертизы по форме, утвержденной Приказом Минсельхоза России № 713 от 30.10.2025, с целью возмещения части затрат при реализации мероприятий по развитию геномной селекции в области племенного животноводства.</p><br>';

        $email_body6 .= '<p>Если Вам необходимы оригиналы отчетов, сообщите нам об этом, мы подготовим и отправим Вам.</p><br>';

        $email_body6 .= '<p>При возникновении вопросов, пожалуйста, свяжитесь с Назимой Нартуповой по телефону <a href="tel:+79266029089">+7 (926) 602-90-89</a>  (Звонок, WhatsApp, Telegram), <a href="tel:+74993711919">+7 (499) 371-19-19</a> или по эл. почте – <a href="mailto:nnartupova@agroplem.ru">nnartupova@agroplem.ru</a></p><br>';

        $email_body6 .= '<p>С уважением,<br>
        Команда лабораторно-исследовательского центра<br>
        <a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';
        //        $email_body5 .= '<p>С уважением,<br>
        //        Команда лабораторно-исследовательского центра<br>
        //        <a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';
    }
    if ($labaratory == 'soil') {
        //  $fromName = 'Почвенная лаборатория Агроплем';
        //$fromEmail = 'bitrix-no-reply@agroplem.ru';
        //  $fromEmail = $labEmail["VALUE"];


        $email_body = '<div id="bodyMail">Добрый день!<br><br>';
        $email_body .= '<p>Во вложении результаты исследований по заказу № ' . $dealUF[$DealUfCode_Number]['VALUE'] . '.</p><br></div>';

        $email_body .= '<div id="mailFiles" class="mailFiles">';
        $email_body .= $fileLinksHide;
        $email_body .= '</div>';

        $email_body .= '<div id="signature"><p><br>Если у вас остались вопросы, пожалуйста, свяжитесь с Эльвирой Абаевой  по телефону 8 (499) 371-19-19 (доб. 2004), <a href="tel:+79262332347"> +7 (926) 233-2347</a> или по эл. почте – <a href="mailto:eabaeva@agroplem.ru">eabaeva@agroplem.ru</a></p>';
        $email_body .= '<p>Спасибо за заказ,</p><br>';
        $email_body .= '<p>С уважением,<br>';
        $email_body .= 'Команда лабораторно-исследовательского центра<br>';
        $email_body .= '<a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';
        $email_body .= '<p>Мы очень хотим, чтобы наш сервис всегда был на высоте. И вы можете нам в этом помочь. Оцените, пожалуйста, качество предоставленной услуги по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>. Потратив несколько минут, Вы поможете нам стать лучше.</p><br><br></div>';
    }

    if ($labaratory == 'microbiosoil') {
        //  $fromName = 'Почвенная лаборатория Агроплем';
        //$fromEmail = 'bitrix-no-reply@agroplem.ru';
        //  $fromEmail = $labEmail["VALUE"];


        $email_body = '<div id="bodyMail">Добрый день!<br><br>';
        $email_body .= '<p>Во вложении результаты исследований по заказу № ' . $dealUF[$DealUfCode_Number]['VALUE'] . '.</p><br></div>';

        $email_body .= '<div id="mailFiles" class="mailFiles">';
        $email_body .= $fileLinksHide;
        $email_body .= '</div>';

        $email_body .= '<div id="signature"><br><p>Если у вас остались вопросы, пожалуйста, свяжитесь с Эльвирой Абаевой  по телефону 8 (499) 371-19-19 (доб. 2004), <a href="tel:+79262332347"> +7 (926) 233-2347</a> или по эл. почте – <a href="mailto:eabaeva@agroplem.ru">eabaeva@agroplem.ru</a></p>';
        $email_body .= '<p>Спасибо за заказ,</p><br>';
        $email_body .= '<p>С уважением,<br>';
        $email_body .= 'Команда лабораторно-исследовательского центра<br>';
        $email_body .= '<a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';
        $email_body .= '<p>Мы очень хотим, чтобы наш сервис всегда был на высоте. И вы можете нам в этом помочь. Оцените, пожалуйста, качество предоставленной услуги по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>. Потратив несколько минут, Вы поможете нам стать лучше.</p><br><br></div>';
    }


    if ($labaratory == 'feed') {
        // $fromName = 'Лаборатория кормов Агроплем';
        //$fromEmail = 'bitrix-no-reply@agroplem.ru';
        //  $fromEmail = $labEmail["VALUE"];

        $email_body = '<div id="bodyMail">Добрый день!<br><br>';
        $email_body .= '<p>Во вложении результаты исследований по заказу № ' . $dealUF[$DealUfCode_Number]['VALUE'] . '.</p><br></div>';

        $email_body .= '<div id="mailFiles" class="mailFiles">';
        $email_body .= $fileLinksHide;
        $email_body .= '</div>';

        $email_body .= '<div id="signature"><br><p>Если у вас остались вопросы, пожалуйста, свяжитесь с Ириной Трегубовой  по телефону 8 (499) 371-19-19 (доб. 2003), <a href="tel:+79262076686"> +7 (926) 207-66-86</a>, или по эл. почте – <a href="mailto:itregubova@agroplem.ru">itregubova@agroplem.ru</a></p>';
        $email_body .= '<p>Спасибо за заказ,</p><br>';
        $email_body .= '<p>С уважением,<br>';
        $email_body .= 'Команда лабораторно-исследовательского центра<br>';
        $email_body .= '<a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';
        $email_body .= '<p>Мы очень хотим, чтобы наш сервис всегда был на высоте. И вы можете нам в этом помочь. Оцените, пожалуйста, качество предоставленной услуги по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>. Потратив несколько минут, Вы поможете нам стать лучше.</p><br><br></div>';
    }

    if ($labaratory == 'microbio') {
        // $fromName = 'Лаборатория микробиологии Агроплем';
        //$fromEmail = 'bitrix-no-reply@agroplem.ru';
        // $fromEmail = $labEmail["VALUE"];

        $email_body = '<div id="bodyMail">Добрый день!<br><br>';
        $email_body .= '<p>Во вложении результаты исследований по заказу № ' . $dealUF[$DealUfCode_Number]['VALUE'] . '.</p><br></div>';

        $email_body .= '<div id="mailFiles" class="mailFiles">';
        $email_body .= $fileLinksHide;
        $email_body .= '</div>';

        $email_body .= '<div id="signature"><br><p>Если у вас остались вопросы, пожалуйста, свяжитесь с Ириной Трегубовой  по телефону 8 (499) 371-19-19 (доб. 2003), <a href="tel:+79262076686"> +7 (926) 207-66-86</a>, или по эл. почте – <a href="mailto:itregubova@agroplem.ru">itregubova@agroplem.ru</a></p>';
        $email_body .= '<p>Спасибо за заказ,</p><br>';
        $email_body .= '<p>С уважением,<br>';
        $email_body .= 'Команда лабораторно-исследовательского центра<br>';
        $email_body .= '<a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';
        $email_body .= '<p>Мы очень хотим, чтобы наш сервис всегда был на высоте. И вы можете нам в этом помочь. Оцените, пожалуйста, качество предоставленной услуги по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>. Потратив несколько минут, Вы поможете нам стать лучше.</p><br><br></div>';
    }

    if ($labaratory == 'all') {
        // $fromName = 'Лаборатория Агроплем';
        // $fromEmail = 'bitrix-no-reply@agroplem.ru';
        // $fromEmail = $labEmail["VALUE"];

        $email_body = '<div id="bodyMail">Добрый день!<br><br>';
        $email_body .= '<p>Во вложении результаты исследований по заказу № ' . $dealUF[$DealUfCode_Number]['VALUE'] . '.</p><br></div>';

        $email_body .= '<div id="mailFiles" class="mailFiles">';
        $email_body .= $fileLinksHide;
        $email_body .= '</div>';

        $email_body .= '<div id="signature"><br><p>Спасибо за заказ,</p>';
        $email_body .= '<p>С уважением,<br>';
        $email_body .= 'Команда лабораторно-исследовательского центра<br>';
        $email_body .= '<a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';
        $email_body .= '<p>Мы очень хотим, чтобы наш сервис всегда был на высоте. И вы можете нам в этом помочь. Оцените, пожалуйста, качество предоставленной услуги по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>. Потратив несколько минут, Вы поможете нам стать лучше.</p><br><br></div>';
    }


    if (empty($toBCopy)) $toBCopy = [];
    if (!empty($smartProcessElements['OBSERVERS'])) {
        foreach ($smartProcessElements['OBSERVERS'] as $observer) {
            $toBCopyes[] = 'U' . $observer;
        }
    } else {
        $toBCopyes = $toBCopy;
    }
    $toBCopyes = array_unique($toBCopyes);


    if ($labaratory == 'milk' || $labaratory == 'milkEKB') {
        $type .= " (" . GetServiceNameById($dealUF[$DealUfCode_Sender]['VALUE']) . ")";
        $bodySend = '<div id="bodyMail">Добрый день!<br>';
        $bodySend .= '<p>Во вложении результаты по тестированию проб молока по заказу № ' . $dealUF[$DealUfCode_Number]['VALUE'] . ' и файл для загрузки данных в DairyComp.</p><br>';
        if ($typeSender == '476') {
        } // Не грузят в Селэкс
        else if ($typeSender == '477') { // Присылают нам базу
            //            if (empty($linkSelecs)) {
            //                $bodySend .= '<p style="color:red;">Ссылка на базы Селекс в <a href="/crm/company/details/' . $deal['COMPANY_ID'] . '/">компании</a> не внесена</p><br>';
            //            } else {
            //                $bodySend .= '<p>Ниже ссылка, по которой вам необходимо загрузить свою базу Селэкс: <br><a href="' . $linkSelecs . '" target="_blank">Ссылка для загрузки базы Селекс</a></p><br>';
            //            }
        } else if ($typeSender == '478') {
            $bodySend .= '<p>Для загрузки данных в Селэкс Вам необходимо прислать нам логин и пароль TeamViewer или данные другого ПО для удаленного доступа.</p><br>';
        } // Облачный Selex
        else if ($typeSender == '477') {
        } // Грузят через Simplex
        else if ($typeSender == '485') {
            $bodySend .= '<p>Пожалуйста, сообщите, необходима ли автоматическая загрузка данных в Селэкс.</p><br>';
        } // Присылают нам базу
        else if ($typeSender == '501') {
        } // Генетика
        else if ($typeSender == '515') { // Имеют доступ к ЛК Плинор
            $bodySend .= '<p>Загрузка данных в Селэкс осуществляется через ИАС «Молочная лаборатория» (Плинор), в котором мы создадим событие по текущему контрольному доению, если оно еще не создано, и подгрузим результаты.</p><br>';
            if (!empty($dealUF[$DealUfCode_DateKD]['VALUE'])) {
                $bodySend .= '<p>Просим сообщить, необходима ли загрузка данных, подтвердив дату контрольной дойки ' . $dealUF[$DealUfCode_DateKD]['VALUE'] . '.</p><br>';
            }
        } else if ($typeSender == '516') { // Нет в ЛК Плинор
            $bodySend .= '<p>Пожалуйста, сообщите, необходима ли автоматическая загрузка данных в Селэкс.</p><br>';
        }

        // d([$trySour, $tryMade, $complexSouring]);
        if ((($trySour >= $tryMade) || ($complexSouring == '1')) && ($tryMade != 0)):
            // $bodySend .= '<p>Обращаем внимание, что часть проб поступила с признаками скисания. Рекомендуем Вам провести мойку пробоотборников щелочными и кислотными растворами, и охлаждать пробы перед отправкой. Во вложении рекомендации по предотвращении скисания проб молока. </p><br>';
            $bodySend .= '<p>Обращаем внимание, что часть проб поступила с признаками скисания. Рекомендуем Вам провести мойку пробоотборников щелочными и кислотными растворами, и охлаждать пробы перед отправкой. <a href="https://www.agroplem.ru/upload/iblock/628/vnnahmfv3q1l7818m2oslhcbvgh45fyx.pdf" target="_blank">Рекомендации по предотвращении скисания проб молока.</a> </p><br>';
        endif;

        if (($trySmallVolume >= $tryMade) && ($tryMade != 0)):
            $bodySend .= '<p>Обращаем ваше внимание, что для ' . $dealUF[$DealUfCode_qSmall]['VALUE'] . ' проб объём молока был недостаточный для анализа. Рекомендуем при заборе проб наливать в стаканчик не менее 30 мл молока (две трети стаканчика) и плотно закрывать крышки.</p><br>';
        endif;
        if ($dealUF[$DealUfCode_Tank]['VALUE'] == '1'):
            $bodySend .= '<p>Информируем Вас, что на основании полученных результатов, лаборатория сделала вывод о том, что часть проб была забрана не от индивидуальных животных, а разлита из общей пробы.</p><br>';
        endif;
        if ($dealUF[$DealUfCode_CollProblems]['VALUE'] == '1'):
            $bodySend .= '<p>Информируем Вас, что на основании полученных результатов, лаборатория сделала вывод о наличии проблем с пробоотбором. Рекомендуем вызвать специалиста на следующую контрольную дойку.</p><br>';
        endif;
        $bodySend .= '</div>';

        $bodySend .= '<div id="mailFiles" class="mailFiles">';
        $bodySend .= $fileLinksHide;
        $bodySend .= '</div>';

        $bodySend .= '<div id="signature"><br><p>Если у вас остались вопросы, пожалуйста, свяжитесь с Дарьей Торгониной по телефону <a href="tel:+79250204873">+7 (925) 020-48-73</a> или по эл. почте – <a href="mailto:dtorgonina@agroplem.ru">dtorgonina@agroplem.ru</a></p><br>';


        $bodySend .= '<br><p>Спасибо за заказ,</p><br>';
        $bodySend .= '<p>С уважением,<br>';
        $bodySend .= 'Команда лаборатории<br>';
        $bodySend .= 'селекционного контроля качества молока<br>';
        $bodySend .= '<a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br>';
        $bodySend .= '<p>Мы очень хотим, чтобы наш сервис всегда был на высоте. И вы можете нам в этом помочь. Оцените, пожалуйста, качество предоставленной услуги по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>. Потратив несколько минут, Вы поможете нам стать лучше.</p><br></div>';
    } else { //Если любая другая лаборатория кроме молока
        $bodySend = $email_body;
    }
}

?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@400&display=swap');
</style>
<link href="<?= MODULE_PATH ?>/public/crm/sending/conclusion/style.css"
    rel="stylesheet"
    type="text/css" />

<?php
$buttons = [
    'calm' => [
        'name' => 'Отправить',
        'text' => 'Обычное состояние кнопки. Нажатие запустит процесс отправки письма.',
    ],
    'loading' => [
        'name' => '<img src="/crm-webhook/sendgrid-php-send-mail/spinning-circles.svg">',
        'text' => 'Идёт отправка письма.',
    ],
    'ready' => [
        'name' => 'Отправлено',
        'text' => 'Письмо отправлено.',
    ],
    'error' => [
        'name' => 'Ошибка',
        'text' => 'При отправки возникла ошибка.',
    ],
];

$inputParamsTo = [
    "ID" => "mail_to",
    "API_VERSION" => 3,
    "LIST" => $arContacts,
    "INPUT_NAME" => "email[to][]",
    "USE_SYMBOLIC_ID" => true,
    "BUTTON_SELECT_CAPTION" => 'Добавить получателя',
    "SELECTOR_OPTIONS" =>
    [
        "departmentSelectDisable" => "Y",
        'context' => 'MAIL_CLIENT_CONFIG_QUEUE',
        'contextCode' => 'U',
        'enableAll' => 'N',
        'crmPrefixType' => 'SHORT',
        'userSearchArea' => 'I',
        'onlyWithEmail' => 'Y',
        'returnMultiEmail' => 'Y',
        'returnJsonValue' => 'Y',
        'enableCrmContacts' => 'Y',
        'addTabCrmContacts' => 'Y',
        'enableCrmCompanies' => 'Y',
        'addTabCrmCompanies' => 'Y',
        'enableCrm' => 'Y',
        'allowEmailInvitation' => 'Y',
        'nameTemplate' => '#NAME# [#EMAIL#]',
    ]
];

$inputParamsCopy = [
    "ID" => "mail_copy",
    "API_VERSION" => 3,
    "LIST" => $toBCopyes,
    "INPUT_NAME" => "email[copy][]",
    "USE_SYMBOLIC_ID" => true,
    "BUTTON_SELECT_CAPTION" => 'Добавить получателя',
    "SELECTOR_OPTIONS" =>
    [
        "departmentSelectDisable" => "Y",
        'context' => 'MAIL_CLIENT_CONFIG_QUEUE',
        'contextCode' => 'U',
        'enableAll' => 'N',
        'crmPrefixType' => 'SHORT',
        'userSearchArea' => 'I',
        'onlyWithEmail' => 'Y',
        'returnMultiEmail' => 'Y',
        'returnJsonValue' => 'Y',
        'enableCrmContacts' => 'Y',
        'addTabCrmContacts' => 'Y',
        'enableCrm' => 'Y',
        'allowEmailInvitation' => 'Y',
        'nameTemplate' => '#NAME# [#EMAIL#]',
    ]
];

?>
<input type="hidden" name="from[]" value="0" />
<div id="from_custom" name="from"></div>
<div class="mail">
    <form method="POST" action="" id="custom-send" enctype="multipart/form-data">
        <div class="type">
            <div class="text help">
                <span><?= $type ?></span>
                <input type="hidden" name="smartId" value="<?= $idSmartProcessElement ?>">
                <input type="hidden" name="dealId" value="<?= $id ?>">
                <input type="hidden" name="companyId" value="<?= $deal['COMPANY_ID'] ?>">
                <input type="hidden" name="idSmartProcessElement" value="<?= $idSmartProcessElement ?>">
            </div>
        </div>
        <?php if ($labaratory == 'genetics') { ?>
            <div class="pattern">
                <span for="" class="pattern">Шаблон&nbsp;</span>
                <select id="select-pattern" name="pattern" onchange="changeFunc();">
                    <option class="set" id='1' value="1">Итоговое заключение</option>
                    <option class="set" id='2' value="2">Предварительное заключение</option>
                    <option class="set" id='3' value="3">Повторное Предварительное заключение</option>
                    <option class="set" id='4' value="4">Молекулярная экспертиза</option>
                    <option class="set" id='5' value="5">Динамический отчет</option>
                    <option class="set" id='6' value="6">Отчет по субсидиям</option>
                </select>
            </div>
        <? } ?>
        <div class="body">
            <div class="header from ">
                <span for="" class="title">От кого:&nbsp;</span>
                <?php //$APPLICATION->IncludeComponent('bitrix:main.user.selector', ' ', $inputParamsFrom,);
                ?>
                <div class="wrap-title__input">
                    <span class="value2"><span><?= $fromName ?> [<?= $fromEmail ?>]</span></span>
                    <input type="hidden" name="from" value="<?= $fromEmail ?>">
                </div>
            </div>
            <br>

            <div class="header from ">
                <?php //implode(', ', $contactsEmailHTML)
                ?>
                <span for="" class="title">Кому:&nbsp;</span>
                <?php $APPLICATION->IncludeComponent('bitrix:main.user.selector', ' ', $inputParamsTo); ?>
            </div>
            <br>

            <?php if (!empty($toCopy)): ?>
                <div class="header from ">
                    <span for="" class="title">Копия:&nbsp;</span>
                    <span class="value"><span><?= implode(', ', $toCopy) ?></span></span>
                </div><br>
            <?php endif; ?>

            <?php if (!empty($toBCopyes)): ?>
                <div class="header from ">
                    <span for="" class="title">С.Копия:&nbsp;</span>
                    <?php $APPLICATION->IncludeComponent('bitrix:main.user.selector', ' ', $inputParamsCopy); ?>
                </div><br>
            <?php endif; ?>

            <div class="header from ">
                <span for="" class="title">Тема:&nbsp;</span>
                <div class="wrap-title__input">
                    <input name="title" type="text" class="theme" value="<?= $topic ?>">
                </div>
            </div>
            <br>

            <div class="body-text">
                <?php if ($labaratory == 'genetics') {
                    $bodySend = $email_body1;
                ?>
                    <script>
                        function changeFunc() {
                            var selectPattern = document.getElementById("select-pattern");
                            var selectedValue = selectPattern.options[selectPattern.selectedIndex].value;

                            //if (selectedValue == '1') {
                            //    $('.bxlhe-editor-cell').find("iframe").contents().find("body").empty();
                            //    $('.bxlhe-frame').find("input").val('<?php //=addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body1))
                                                                        ?>//');
                            //    $('.bxlhe-editor-cell').find("iframe").contents().find("body").html('<?php //=addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body1))
                                                                                                        ?>//');
                            //    <? //$bodySend = $email_body2;
                                    ?>
                            //} else {
                            //    $('.bxlhe-editor-cell').find("iframe").contents().find("body").empty();
                            //    $('.bxlhe-frame').find("input").val('<?php //=addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body2))
                                                                        ?>//');
                            //    $('.bxlhe-editor-cell').find("iframe").contents().find("body").html('<?php //=addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body2))
                                                                                                        ?>//');
                            //    <? //$bodySend = $email_body1;
                                    ?>
                            //}
                            console.log(selectedValue);
                            if (selectedValue == '2') {
                                $('.bxlhe-editor-cell').find("iframe").contents().find("body").empty();
                                $('.bxlhe-frame').find("input").val('<?= addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body2)) ?>');
                                $('.bxlhe-editor-cell').find("iframe").contents().find("body").html('<?= addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body2)) ?>');
                            }
                            if (selectedValue == '3') {
                                $('.bxlhe-editor-cell').find("iframe").contents().find("body").empty();
                                $('.bxlhe-frame').find("input").val('<?= addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body3)) ?>');
                                $('.bxlhe-editor-cell').find("iframe").contents().find("body").html('<?= addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body3)) ?>');
                            }
                            if (selectedValue == '1') {
                                $('.bxlhe-editor-cell').find("iframe").contents().find("body").empty();
                                $('.bxlhe-frame').find("input").val('<?= addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body1)) ?>');
                                $('.bxlhe-editor-cell').find("iframe").contents().find("body").html('<?= addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body1)) ?>');
                            }
                            if (selectedValue == '4') {
                                $('.bxlhe-editor-cell').find("iframe").contents().find("body").empty();
                                $('.bxlhe-frame').find("input").val('<?= addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body4)) ?>');
                                $('.bxlhe-editor-cell').find("iframe").contents().find("body").html('<?= addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body4)) ?>');
                                $('input.theme').val("<?= $topicMolecularExpertise ?>");
                            }
                            if (selectedValue == '5') {
                                $('.bxlhe-editor-cell').find("iframe").contents().find("body").empty();
                                $('.bxlhe-frame').find("input").val('<?= addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body5)) ?>');
                                $('.bxlhe-editor-cell').find("iframe").contents().find("body").html('<?= addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body5)) ?>');
                            }
                            if (selectedValue == '6') {
                                $('.bxlhe-editor-cell').find("iframe").contents().find("body").empty();
                                $('.bxlhe-frame').find("input").val('<?= addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body6)) ?>');
                                $('.bxlhe-editor-cell').find("iframe").contents().find("body").html('<?= addslashes(str_replace(array("\r\n", "\r", "\n"), "<br>", $email_body6)) ?>');
                            } else {
                                $('input.theme').val("<?= $topic ?>");
                            }
                        }
                    </script>
                <?php } ?>
                <?php
                $APPLICATION->IncludeComponent(
                    "bitrix:fileman.light_editor",
                    "",
                    array(
                        "CONTENT" => $bodySend,
                        "INPUT_NAME" => "bodysend",
                        "INPUT_ID" => "bodysend",
                        "WIDTH" => "100%",
                        "HEIGHT" => "300px",
                        "RESIZABLE" => "Y",
                        "AUTO_RESIZE" => "Y",
                        "VIDEO_ALLOW_VIDEO" => "Y",
                        "VIDEO_MAX_WIDTH" => "640",
                        "VIDEO_MAX_HEIGHT" => "480",
                        "VIDEO_BUFFER" => "20",
                        "VIDEO_LOGO" => "",
                        "VIDEO_WMODE" => "transparent",
                        "VIDEO_WINDOWLESS" => "Y",
                        "VIDEO_SKIN" => "/bitrix/components/bitrix/player/mediaplayer/skins/bitrix.swf",
                        "USE_FILE_DIALOGS" => "Y",
                        "ID" => "",
                        "JS_OBJ_NAME" => "bodysend"
                    )
                );
                ?>
            </div>
            <br>
            <? if ($filess) { ?>
                <div class="files help" id="filesBox">
                    <span for="" class="title">Вложенные файлы:&nbsp;</span>
                    <span class="value"><?= $files ?></span>
                    <input name="files" id="filesSend" type="hidden"
                        value="<?= htmlentities(serialize($arFiles)) ?>">
                </div>
                <br>
                <input type="checkbox" id="linkCheckbox" name="linkCheckbox">
                <label for="linkCheckbox">Отправить файлы ссылками</label><br><br>
                <input type="checkbox" id="hideDomainCheckbox" name="hideDomainCheckbox" checked="checked" disabled>
                <label for="hideDomainCheckbox">Скрыть домен agrochemist.ru для файлов отправленных ссылками</label>
            <? } ?>
        </div>
        <?
        // if ($fileSize > 16777216) {
        //if ($fileSize > 9800000) {
        //
        ?>
        <!--                <div>-->
        <!--                    <button disabled id='btn_send' type='submit' class="button send calm"-->
        <!--                            style="background-color: gray; display:inline;">-->
        <!--                        <span class="calm">Отправить</span>-->
        <!--                    </button>-->
        <!--                    <span class="value2" style="font-weight: bold;">Размер приложенных к письму файлов превышает возможности почтового сервиса Unisender. Сообщение не может быть отправлено</span>-->
        <!--                </div>-->
        <? //} else {
        ?>
        <button id='btn_send' type='submit' class="button send calm">
            <span class="calm">Отправить</span>
            <span class="loading"><img src="/crm-webhook/sendgrid-php-send-mail/spinning-circles.svg"></span>
            <span class="ready">Отправлено</span>
            <span class="error">Ошибка</span>
        </button>
        <? // }
        ?>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filesBox = document.getElementById('filesBox');
        const checkbox = document.getElementById('linkCheckbox');
        const hideDomainCheckbox = document.getElementById('hideDomainCheckbox');
        const filesSend = document.getElementById('filesSend');
        let iframe = document.querySelector('.bx-core .mail .body .body-text .lha-iframe');
        let iframeDocument = iframe.contentDocument;
        const mailFiles = iframeDocument.querySelector('.mailFiles');
        const bodyMail = iframeDocument.getElementById('bodyMail');
        const signature = iframeDocument.getElementById('signature');
        const filesSendOriginalValue = filesSend.value;
        // if (<?= $fileSize ?> > MAX_FILESIZE_IN_BODY) {
        if (<?= $fileSize ?> > 9800000) {
            checkbox.checked = true;
            checkbox.disabled = true;
            filesBox.style.display = 'none';
        }

        function checkFiles() {
            if (checkbox.checked) {
                filesBox.style.display = 'none';
                filesSend.value = [];
                $('.bxlhe-editor-cell').find("iframe").contents().find("body").html(bodyMail.innerHTML + mailFiles.innerHTML + signature.innerHTML);
                $('.bxlhe-frame').find("input").val(bodyMail.innerHTML + mailFiles.innerHTML + signature.innerHTML);
            } else {
                filesBox.style.display = 'flex';
                filesSend.value = filesSendOriginalValue;
                $('.bxlhe-editor-cell').find("iframe").contents().find("body").html(bodyMail.innerHTML + signature.innerHTML);
                $('.bxlhe-frame').find("input").val(bodyMail.innerHTML + signature.innerHTML);
            }
        }

        function changeLinks() {
            if (hideDomainCheckbox.checked) {
                mailFiles.innerHTML = '<?= $fileLinksHide ?>';
            } else {
                mailFiles.innerHTML = '<?= $fileLinksShow ?>';
            }
            checkFiles();
        }

        hideDomainCheckbox.addEventListener('change', changeLinks);
        checkbox.addEventListener('change', checkFiles);
        checkFiles();
    }, );

    $('#custom-send').on("submit", function() {
        event.preventDefault();
        let formData = $('#custom-send').serialize();
        let button = $('#btn_send');
        if ($(button).hasClass('calm')) {
            let rezult = $('#rezult pre');
            $(button).toggleClass('calm loading');
            $.ajax({
                url: "/alter/crm/sending/conclusion/bitrix_send.php",
                method: 'POST',
                data: formData,
                success: function(data) {
                    $(button).toggleClass('loading ready');
                    $(rezult).html(data);
                },
                error: function(data) {
                    $(button).toggleClass('loading error');
                    $(rezult).html(data['status']);
                },
            });
        }
    });

    $('.mail .type .question').click(function() {
        $(this).toggleClass('active');
        $('.mail').toggleClass('question');
    });

    if ($(button).hasClass("ready")) {

        alert("У элемента есть класс ready!");

        // Здесь может быть любой другой ваш код

    }

    /*(function() { const tagSelector = new BX.UI.EntitySelector.TagSelector({
        id: 'from_custom',
        multiple: 'Y',
        dialogOptions: {
            context: 'MY_MODULE_CONTEXT',
            entities: [
                {data
                    id: 'user', // пользователи
                },
                {
                    id: 'project', // группы и проекты
                },
                {
                    id: 'department', // структура компании
                    options: {
                        selectMode: 'usersAndDepartments' // выбор пользователей и отделов
                    }
                },
                {
                    id: 'meta-user',
                    options: {
                        'all-users': true // Все сотрудники
                    }
                },
            ],
        }
    });
        tagSelector.renderTo(document.getElementById('from_custom'))})();*/
</script>
<?php
echo file_get_contents('help.html');

//}


function GetServiceNameById($id)
{
    static $serviceCache = array();
    if (isset($serviceCache[$id]))
        return $serviceCache[$id];
    $rsService = CUserFieldEnum::GetList([], ["ID" => $id]);
    if ($arService = $rsService->GetNext()) {
        $serviceCache[$id] = $arService['VALUE'];
    }
    return $serviceCache[$id];
}

?>