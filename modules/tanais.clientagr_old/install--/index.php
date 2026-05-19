<?
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);


class tanais_clientagr extends CModule
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
        $this->MODULE_ID            = 'tanais.clientagr'; //Строго Lower case
        $this->MODULE_NAME_SPACE    = 'Tanais\ClientAGR'; //Для автозагрузчика классов
        $this->MODULE_NAME          = 'Карточка клиента AGR';
        $this->MODULE_DESCRIPTION   = 'Синхронизация между порталами группы';
        $this->PARTNER_NAME         = "Tanais";
        $this->PARTNER_URI          = "https://tanais.ru";
    }

    function DoInstall()
    {
        RegisterModule($this->MODULE_ID);
        \Bitrix\Main\Loader::includeModule("tanais.clientagr");
        \Tanais\ClientAGR\Install::installFiles();
        \Tanais\ClientAGR\Cron::registerAgents();
        \Tanais\ClientAGR\EventHandler::registerHandlers();
        // \Tanais\WorkPlace\Install::installSQL();
        // \Tanais\WorkPlace\Install::installAdminFiles();
        // \Tanais\WorkPlace\Install::setOptions();
        if ((class_exists('Tanais\ClientAGR\Cron')) && (class_exists('Tanais\ClientAGR\Cron','registerAgents')))
            \Tanais\ClientAGR\Cron::registerAgents();
        if ((class_exists('Tanais\ClientAGR\EventHandler')) && (class_exists('Tanais\ClientAGR\Cron','registerHandlers')))
            \Tanais\ClientAGR\EventHandler::registerHandlers();
    }

    function DoUninstall()
    {   
        \Bitrix\Main\Loader::includeModule("tanais.clientagr");
        \Tanais\ClientAGR\Install::unInstallFiles();
        if ((class_exists('\Tanais\ClientAGR\Cron')) && (class_exists('\Tanais\ClientAGR\Cron','unRegisterAgents')))
            \Tanais\ClientAGR\Cron::unRegisterAgents();
        if ((class_exists('\Tanais\ClientAGR\EventHandler')) && (class_exists('\Tanais\ClientAGR\Cron','unRegisterHandlers')))
            \Tanais\ClientAGR\EventHandler::unRegisterHandlers();
        // \Tanais\ClientAGR\Install::unInstallAdminFiles();
        UnRegisterModule($this->MODULE_ID);
    }
}
