<?
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);


class tanais_support extends CModule
{
    public $MODULE_ID;
    public $MODULE_NAME;
    public $MODULE_NAME_SPACE;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_DESCRIPTION;

    function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');
        $this->MODULE_VERSION       = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE  = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_ID            = 'tanais.support'; //Строго Lower case
        $this->MODULE_NAME_SPACE    = 'Tanais\Support'; //Для автозагрузчика классов
        $this->MODULE_NAME          = 'TANAiS: Поддержка';
        $this->MODULE_DESCRIPTION   = 'TANAIS. Поддержка';
        $this->PARTNER_NAME         = "Tanais";
        $this->PARTNER_URI          = "https://tanais.ru";
    }

    function DoInstall()
    {
        RegisterModule($this->MODULE_ID);
        \Bitrix\Main\Loader::includeModule("tanais.support");
        \Tanais\Support\Install::installSQL();
        \Tanais\Support\Install::installPublicFiles();
        \Tanais\Support\Install::installAdminFiles();
        \Tanais\Support\Install::setOptions();
        if ((class_exists('\Tanais\Support\Cron')) && (class_exists('\Tanais\Support\Cron','registerAgents')))
            \Tanais\Support\Cron::registerAgents();
        if ((class_exists('\Tanais\Support\EventHandler')) && (class_exists('\Tanais\Support\Cron','registerHandlers')))
            \Tanais\Support\EventHandler::registerHandlers();
    }

    function DoUninstall()
    {   
        // if ((class_exists('\Tanais\Support\Cron')) && (class_exists('\Tanais\Support\Cron','unRegisterAgents')))
        //     \Tanais\Support\Cron::unRegisterAgents();
        // if ((class_exists('\Tanais\Support\EventHandler')) && (class_exists('\Tanais\Support\Cron','unRegisterHandlers')))
        //     \Tanais\Support\EventHandler::unRegisterHandlers();
        // \Tanais\Support\Install::unInstallPublicFiles();
        // \Tanais\Support\Install::unInstallAdminFiles();
        UnRegisterModule($this->MODULE_ID);
    }
}
