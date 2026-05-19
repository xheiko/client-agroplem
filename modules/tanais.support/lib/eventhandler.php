<?

namespace Tanais\Support;

use Bitrix\Main\EventResult;
use Bitrix\Main\Event;

class EventHandler
{
    const LOG_PATH = '/home/bitrix/www/local/log/tanais/support';
    const EVENT_HANDLERS = [
        ["main", "OnProlog", "tanais.support", "\Tanais\Support\EventHandler", "doOnProlog"], //в начале визуальной части пролога сайта
        ["main", "OnPageStart", "tanais.support", "\Tanais\Support\EventHandler", "doOnPageStart"], //в начале выполняемой части пролога сайта, после подключения всех библиотек и отработки агентов
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
        // $scriptURL = $_SERVER['SCRIPT_URL'];
        //     if ((str_starts_with($scriptURL, '/support/group/')) && (str_contains($scriptURL, '/edit/'))) {
        //         \Bitrix\Main\UI\Extension::load('tanais.alter.crm.project');
        //     }

        \Bitrix\Main\UI\Extension::load('tanais.support.supportbtn');

        return true;
    }

    public static function doOnPageStart() {
        return true;
    }

}
