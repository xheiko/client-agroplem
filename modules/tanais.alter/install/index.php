<?
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);


class tanais_alter extends CModule
{
    public $MODULE_ID;
    public $MODULE_NAME;
    public $MODULE_NAME_SPACE;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_DESCRIPTION;

    function __construct()
    {
        $this->MODULE_VERSION = "1.0.0.0";
        $this->MODULE_VERSION_DATE = "2025-01-01 00:00:00";
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');
        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_ID = 'tanais.alter'; //Строго Lower case
        $this->MODULE_NAME_SPACE = 'Tanais\Alter'; //Для автозагрузчика классов

        $this->PUBLIC_PATH = '/alter'; //Папка где будет размещены файлы из дериктории /public модуля
        $this->MODULE_PATH = '/local/modules/' . $this->MODULE_ID;    //папка в которой сам модуль
        $this->JS_PATH = '/local/js/tanais/alter'; //Папка в которой будут размещены JS extensions

        //Сохраним пути в опциях модуля. Используются в скриптах установки/удаления модуля
        \COption::SetOptionString($this->MODULE_ID, "module_path", $_SERVER["DOCUMENT_ROOT"] . $this->MODULE_PATH);
        \COption::SetOptionString($this->MODULE_ID, "public_path", $_SERVER["DOCUMENT_ROOT"] . $this->PUBLIC_PATH);
        \COption::SetOptionString($this->MODULE_ID, "js_path", $_SERVER["DOCUMENT_ROOT"] . $this->JS_PATH);
        \COption::SetOptionString($this->MODULE_ID, "log_path", $_SERVER["DOCUMENT_ROOT"] . "/local/log/" . $this->MODULE_ID);

        $this->MODULE_NAME = 'AGR: Модификация портала';
        $this->MODULE_DESCRIPTION = 'Модуль модификации портала bitrix.agroplem.ru';
        $this->PARTNER_NAME = "Tanais";
        $this->PARTNER_URI = "https://tanais.ru";
    }

    function DoInstall()
    {
        RegisterModule($this->MODULE_ID);

        \Bitrix\Main\Loader::includeModule("tanais.alter");

//        \Tanais\Alter\Install::installSQL();
        \Tanais\Alter\Install::installPublicFiles();
//        \Tanais\Alter\Install::installAdminFiles();
//        \Tanais\Alter\Install::setOptions();
        \Tanais\Alter\Cron::registerAgents();
        \Tanais\Alter\EventHandler::registerHandlers();
    }

    function DoUninstall()
    {
        \Bitrix\Main\Loader::includeModule("tanais.alter");
        \Tanais\Alter\Cron::unRegisterAgents();
        \Tanais\Alter\EventHandler::unRegisterHandlers();
        \Tanais\Alter\Install::unInstallPublicFiles();
        // \Tanais\Alter\Install::unInstallAdminFiles();

        UnRegisterModule($this->MODULE_ID);
    }
}