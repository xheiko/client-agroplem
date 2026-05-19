<?

namespace Tanais\ClientAGR;

use Bitrix\Main\EventResult;
use Bitrix\Main\Event;

class EventHandler
{
    const LOG_PATH = '/home/bitrix/www/local/log/ClientAGR/';
    const EVENT_HANDLERS = [
        ["main", "OnProlog", "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnProlog"], //в начале визуальной части пролога сайта
        ["main", "OnPageStart", "tanais.clientagr", "\Tanais\ClientAGR\EventHandler", "doOnPageStart"], //в начале выполняемой части пролога сайта, после подключения всех библиотек и отработки агентов
    ];

    public static function registerHandlers()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        foreach (self::EVENT_HANDLERS as $handler)
            $eventManager->registerEventHandler($handler[0], $handler[1], $handler[2], $handler[3], $handler[4]);
    }

    public static function unRegisterHandlers()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        foreach (self::EVENT_HANDLERS as $handler)
            $eventManager->unregisterEventHandler($handler[0], $handler[1], $handler[2], $handler[3], $handler[4]);
    }

    public static function listHandlers($module = 'main', $event = 'OnBuildGlobalMenu')
    {
        echo "<hr><pre>" . var_export(GetModuleEvents($module, $event, true), true) . "</pre><hr>";
    }


    public static function doOnProlog()
    {
        $scriptURL = $_SERVER['SCRIPT_URL'];
        //     if ((str_starts_with($scriptURL, '/workgroups/group/')) && (str_contains($scriptURL, '/edit/'))) {
        //         \Bitrix\Main\UI\Extension::load('tanais.alter.crm.project');
        //     }
        return true;
    }

    public static function doOnPageStart() {
        return true;
    }

}
