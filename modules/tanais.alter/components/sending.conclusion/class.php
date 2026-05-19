<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\Loader;
use Bitrix\Crm;
use Bitrix\Crm\Service;
use Bitrix\Crm\Integration\Main\UISelector;

class SendingConclusion extends CBitrixComponent
{
    const MODULE_PATH = '/local/modules/tanais.alter';
    protected array $replacements = [
        '/upload/main/' => 'm/',
        '/upload/disk/' => 'd/',
        '/upload/crm/' => 'c/',
        '/upload/documentgenerator/' => 'dg/',
    ];

    protected $linkUpstream = 'https://attachments.data.agroplem.ru/';
    protected int $labPropertyId = 66;

    public function configureActions(): array
    {
        return [
            'sendEmail' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([Main\Engine\ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    public function executeComponent()
    {
        global $APPLICATION, $USER;

        $container = Service\Container::getInstance();

        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        $placementOptions = (string)$request->get('PLACEMENT_OPTIONS');

        if ($placementOptions !== '') {
            try {
                $options = \Bitrix\Main\Web\Json::decode($placementOptions);
                $idConclusion = (int)($options['ID'] ?? 0);
            } catch (\Throwable $e) {
                $idConclusion = 0;
            }
        }

        if (!$idConclusion) {
            $this->arResult['ERROR'] = 'Элемент смарт-процесса не найден';
            $this->includeComponentTemplate();
            return;
        }

        $factory = $container->getFactory(1042);
        $item = $factory->getItem($idConclusion);
        if (!$item) {
            $this->arResult['ERROR'] = 'Элемент смарт-процесса не найден';
            $this->includeComponentTemplate();
            return;
        }

        $smartData = $item->getData();
        if (empty($smartData['PARENT_ID_2'])) {
            $this->arResult['ERROR'] = 'Не выбрана сделка!';
            $this->includeComponentTemplate();
            return;
        }

        $dealId = (int)$smartData['PARENT_ID_2'];
        $deal = CCrmDeal::GetByID($dealId);
        $dealUF = CCrmDeal::GetUserFields(false, $dealId);

        $this->arResult['SMART_ID'] = $idConclusion;
        $this->arResult['SMART_ITEM_ID'] = $idConclusion;
        $this->arResult['DEAL_ID'] = $dealId;
        $this->arResult['COMPANY_ID'] = (int)$deal['COMPANY_ID'];

        // Лаборатория
        $labId = (int)($dealUF['UF_CRM_LABORATORY']['VALUE'] ?? 0);
        $this->arResult['LAB'] = $this->resolveLab($labId);
        $this->arResult['LAB_ID'] = $labId;

        // Тема письма
        $topic = 'Результаты исследований Агроплем, заказ № ' . str_replace('"', '', (string)$dealUF['UF_CRM_ORDER_NUMBER']['VALUE']);
        $company = CCrmCompany::GetByID($deal['COMPANY_ID']);
        if (!empty($company)) {
            $topic .= ', ' . str_replace('"', '', $company['TITLE']);
        }
        $area = str_replace('"', '', (string)$dealUF['UF_CRM_1618824524349']['VALUE']);
        if (!empty($area)) {
            $topic .= ', площадка ' . $area;
        }
        $this->arResult['TOPIC'] = $topic;

        // Получатели: контакты сделки
        $this->arResult['SELECTOR_TO_LIST'] = $this->buildToSelectorList($dealId);

        // BCC: наблюдатели элемента
        $this->arResult['SELECTOR_BCC_LIST'] = $this->buildBccSelectorList($smartData);

        // Вложения из смарт-элемента
        $filesInfo = $this->collectFiles($smartData['UF_CRM_4_FILES'] ?? []);
        $this->arResult = array_merge($this->arResult, $filesInfo);

        if ($this->arResult['LABORATORY'] === 'all') {
            $this->arResult['EMAIL_BODIES'] = $this->buildEmailBodies($deal, $dealUF, $company, $this->arResult['LAB'], $filesInfo);
        } else {
            // Тела писем по типам лабораторий
            $this->arResult['EMAIL_BODIES'] = $this->buildEmailBodies($deal, $dealUF, $company, $this->arResult['LAB'], $filesInfo);
        }

        // Кому
        $this->arResult["INPUT_NAME_TO"] = "email[to][]";

        // С.Копия
        $this->arResult["INPUT_NAME_COPY"] = "email[copy][]";

        // Заголовок письма
        $this->arResult["SUBJECT"] = $this->arParams["SUBJECT"] ?? '';

        $this->arResult["CONTACTS_LIST"] = [];
        $this->arResult["COPY_LIST"] = [];

        $this->arResult["TYPE"] = 'Отправка результатов клиенту по email';
        $this->arResult["FROM_NAME"] = "Лаборатория Агроплем";
        $this->arResult["FROM_EMAIL"] = "noreply@agroplem.ru";


        $this->includeComponentTemplate();
    }

    protected function buildToSelectorList(int $dealId): array
    {
        $arContacts = [];
        $bindings = \Bitrix\Crm\Binding\DealContactTable::getDealBindings($dealId);
        foreach ($bindings as $b) {
            $emails = CCrmFieldMulti::GetList([], ['ELEMENT_ID' => $b['CONTACT_ID'], 'ENTITY_ID' => 'CONTACT', 'TYPE_ID' => 'EMAIL']);
            $contact = CCrmContact::GetByID($b['CONTACT_ID']);
            while ($em = $emails->Fetch()) {
                $slug = UISelector\CrmEntity::getMultiKey(UISelector\CrmContacts::PREFIX_SHORT . $b['CONTACT_ID'], $em['VALUE']);
                $arContacts[$slug] = 'contacts';
            }
        }
        $inputParams = [
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
        return $inputParams;
    }

    protected function buildBccSelectorList(array $smartData): array
    {
        $result = [];
        if (!empty($smartData['OBSERVERS']) && is_array($smartData['OBSERVERS'])) {
            foreach ($smartData['OBSERVERS'] as $uid) {
                $result[] = 'U' . $uid;
            }
        }
        $arBccList = array_unique($result);

        $inputParams = [
            "ID" => "mail_copy",
            "API_VERSION" => 3,
            "LIST" => $arBccList,
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


        return $inputParams;
    }

    protected function collectFiles($fileIds): array
    {

        $fileLinksShow = '';
        $fileLinksHide = '';
        $ids = [];
        $sum = 0;
        if (is_countable($fileIds) && count($fileIds)) {
            foreach ($fileIds as $fid) {
                $f = CFile::GetByID($fid)->Fetch();
                if (!$f) {
                    continue;
                }
                $sum += (int)$f['FILE_SIZE'];
                $path = CFile::GetPath($f['ID']);

                $pathArr = explode('/', $path);
                $pathArr[count($pathArr) - 1] = str_replace(' ', '%20', end($pathArr));
                $safePath = implode('/', $pathArr);

                $files .= '<span class="files">' . $f['ORIGINAL_NAME'] . '</span>';

                $fileLinksShow .= '<a href="https://' . $_SERVER['SERVER_NAME'] . $safePath . '">' . $f['ORIGINAL_NAME'] . '</a><br>';

                $hidePath = $safePath;
                foreach ($this->replacements as $old => $new) {
                    if (strpos($hidePath, $old) !== false) {
                        $hidePath = str_replace($old, $new, $hidePath);
                    }
                }
                $fileLinksHide .= '<a href="' . $this->linkUpstream . '2/' . $hidePath . '">' . $f['ORIGINAL_NAME'] . '</a><br>';
                $ids[] = (int)$f['ID'];
            }
        }
        $approxEmailSize = $sum * 1.4 + 20000; // + заголовки
        return [
            'FILE_IDS' => $ids,
            'FILE_ORIGINAL_NAME' => $files,
            'FILE_SIZE_TOTAL' => $approxEmailSize,
            'FILE_LINKS_SHOW' => $fileLinksShow,
            'FILE_LINKS_HIDE' => $fileLinksHide,
        ];
    }

    protected function resolveLab(int $labId): string
    {
        $map = [
            800 => 'milk',
            811 => 'milkEKB',
            801 => 'genetics',
            802 => 'soil',
            803 => 'feed',
            804 => 'microbio',
            805 => 'microbiosoil',
        ];
        return $map[$labId] ?? 'all';
    }

    protected function hasGenomicAssessment(int $dealId): bool
    {
        $rows = CAllCrmProductRow::LoadRows('D', $dealId) ?: [];
        foreach ($rows as $r) {
            $article = \Tanais\Alter\Crm\Catalog::getFieldValue($r['PRODUCT_ID'], 'ARTICLE_NUMBER');
            if ($article === 'GEBVOF_FEMALE' || $article === 'GEBVOF_MALE') {
                return true;
            }
        }
        return false;
    }

    protected function buildEmailBodies(array $deal, array $dealUF, array $company, string $lab, array $files): array
    {
        $topicNumber = $dealUF['UF_CRM_ORDER_NUMBER']['VALUE'] ?? '';
        $companyTitle = !empty($company) ? str_replace('"', '', $company['TITLE']) : '';
        $filesBlockHide = '<div id="mailFiles" class="mailFiles">' . $files['FILE_LINKS_HIDE'] . '</div>';
        $sigCommon = '<div id="signature"><br><p>Спасибо за заказ,</p><br><p>С уважением,<br>Команда лабораторно-исследовательского центра<br><a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br><p>Мы очень хотим, чтобы наш сервис всегда был на высоте. И вы можете нам в этом помочь. Оцените, пожалуйста, качество предоставленной услуги по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>.</p><br><br></div>';

        $bodies = [];

        if (in_array($lab, ['milk', 'milkEKB'], true)) {
            $bodies['default'] = '<div id="bodyMail">Добрый день!<br><p>Во вложении результаты по тестированию проб молока по заказу № ' . htmlspecialcharsbx($topicNumber) . '.</p></div>'
                . $filesBlockHide .
                '<div id="signature"><br><p>Если у вас остались вопросы, пожалуйста, свяжитесь с Назимой Нартуповой по телефону <a href="tel:+79266029089">+7 (926) 602-90-89</a> или по эл. почте – <a href="mailto:nnartupova@agroplem.ru">nnartupova@agroplem.ru</a></p><br><p>Спасибо за заказ,</p><p>С уважением,<br>Команда лаборатории селекционного контроля качества молока<br><a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br><p>Мы очень хотим, чтобы наш сервис всегда был на высоте. Оцените, пожалуйста, качество по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>.</p><br></div>';
        } elseif ($lab === 'all') {
            $hasGA = $this->hasGenomicAssessment((int)$deal['ID']);
            $introCommon = '<div id="bodyMail">Добрый день!<br><br><p>Ваш заказ был выполнен и по его результатам мы подготовили заключение, которое прикрепляем к данному письму. Большое спасибо за заказ.</p><br></div>';
            $extra1 = $hasGA
                ? '<br><p>ВАЖНО: в отчет добавлен столбец по носительству гаплотипа HMW (Синдром мышечной слабости).</p><br>'
                : '<br><p>Оригинал заключения вместе с копиями документов направим почтой.</p><br>';
            $contactNazima = '<br><p>Если у вас остались вопросы, пожалуйста, свяжитесь с Назимой Нартуповой по телефону <a href="tel:+79266029089">+7 (926) 602-90-89</a>, <a href="tel:+74993711919">+7 (499) 371-19-19</a> или по эл. почте – <a href="mailto:nnartupova@agroplem.ru">nnartupova@agroplem.ru</a></p><br>';
            $bodies['default'] = $introCommon . $filesBlockHide . '<div id="signature">' . $extra1 . $contactNazima . '<p>Спасибо за заказ,</p><p>С уважением,<br>Команда лабораторно-исследовательского центра<br><a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br><p>Мы очень хотим, чтобы наш сервис всегда был на высоте. Оцените качество по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>.</p><br><br></div>';

            $bodies['gen_final'] = $introCommon . $filesBlockHide . '<div id="signature">' . $extra1 . $contactNazima . '<p>Спасибо за заказ,</p><p>С уважением,<br>Команда лабораторно-исследовательского центра<br><a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br><p>Мы очень хотим, чтобы наш сервис всегда был на высоте. Оцените качество по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>.</p><br><br></div>';

            $bodies['gen_pre'] = 'Добрый день!<br><br><p>Ваш заказ выполнен. В приложении предварительное заключение: для некоторых животных подобрались возможные предки. Сообщите, работали ли они в этот период, чтобы мы подготовили итоговое заключение.</p>'
                . $filesBlockHide . '<br><p>Информацию направьте Назиме Нартуповой: <a href="mailto:nnartupova@agroplem.ru">nnartupova@agroplem.ru</a>, тел. <a href="tel:+79266029089">+7 (926) 602-90-89</a>.</p><br>';

            $bodies['gen_pre_repeat'] = 'Добрый день!<br><br><p>Направляем повторно предварительное заключение по заказу ' . htmlspecialcharsbx($topicNumber) . '. Просим сообщить по подобранным предкам для подготовки итогового заключения.</p>'
                . $filesBlockHide . '<p>Информацию направьте Назиме Нартуповой: <a href="mailto:nnartupova@agroplem.ru">nnartupova@agroplem.ru</a>, тел. <a href="tel:+79266029089">+7 (926) 602-90-89</a>.</p><br><p>При отсутствии ответа в течение 30 календарных дней подбор будет удален, пробы утилизированы. Повторный подбор будет платной услугой.</p>';
        } elseif (in_array($lab, ['soil', 'feed', 'microbio', 'microbiosoil', 'all'], true)) {
            $contact = [
                'soil' => 'Эльвирой Абаевой (доб. 2004), <a href="tel:+79262332347">+7 (926) 233-23-47</a>, <a href="mailto:eabaeva@agroplem.ru">eabaeva@agroplem.ru</a>',
                'microbiosoil' => 'Эльвирой Абаевой (доб. 2004), <a href="tel:+79262332347">+7 (926) 233-23-47</a>, <a href="mailto:eabaeva@agroplem.ru">eabaeva@agroplem.ru</a>',
                'feed' => 'Ириной Трегубовой (доб. 2003), <a href="tel:+79262076686">+7 (926) 207-66-86</a>, <a href="mailto:itregubova@agroplem.ru">itregubova@agroplem.ru</a>',
                'microbio' => 'Ириной Трегубовой (доб. 2003), <a href="tel:+79262076686">+7 (926) 207-66-86</a>, <a href="mailto:itregubova@agroplem.ru">itregubova@agroplem.ru</a>',
                'all' => 'Командой лабораторно-исследовательского центра',
            ][$lab] ?? 'Командой лабораторно-исследовательского центра';

            $bodies['default'] = '<div id="bodyMail">Добрый день!<br><br><p>Во вложении результаты исследований по заказу № ' . htmlspecialcharsbx($topicNumber) . '.</p></div>'
                . $filesBlockHide .
                '<div id="signature"><p><br>Если у вас остались вопросы, свяжитесь с ' . $contact . '.</p><p>Спасибо за заказ,</p><p>С уважением,<br>Команда лабораторно-исследовательского центра<br><a href="https://agroplem.ru/" target="_blank">«Агроплем»</a></p><br><p>Оцените качество услуги по <a href="https://forms.yandex.ru/u/633ab0b77eca0afc9dcb8a28/">ссылке</a>.</p><br><br></div>';
        }

        return $bodies;
    }

}
