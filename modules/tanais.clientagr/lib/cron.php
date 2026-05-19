<?

namespace Tanais\ClientAGR;

class Cron
{
    const  MODULE_ID = 'tanais.clientagr';

    static public function registerAgents()
    {
        \CAgent::RemoveModuleAgents(self::MODULE_ID);
        $agentID = [];
        $agentID[] = \CAgent::AddAgent(
            "\Tanais\ClientAGR\Cron::doEvery30Minutes();", // имя функции
            'tanais.clientagr',                            // идентификатор модуля
            "N",                                        // N next_exec = дата последнего запуска + interval
            1800,                                        // интервал запуска 30 минут
            "",                                            // дата первой проверки на запуск
            "Y",                                        // агент активен
            "",                                            // дата первого запуска
            30,
            1                                            // запускать от Системного бота
        );
        $agentID[] = \CAgent::AddAgent(
            "\Tanais\ClientAGR\Cron::runMorningTasks();",        // имя функции
            'tanais.clientagr',                            // идентификатор модуля
            "Y",                                        // В точное время
            86400,                                        // интервал запуска 30 минут
            "",                                            // дата первой проверки на запуск
            "Y",                                        // агент активен
            date('d.m.Y 06:17:00'),                    // дата первого запуска
            30,
            1                                            // запускать от Системного бота
        );
        $agentID[] = \CAgent::AddAgent(
            "\Tanais\ClientAGR\Cron::runLateMorningTasks();",        // имя функции
            'tanais.clientagr',                            // идентификатор модуля
            "Y",                                        // В точное время
            86400,                                        // интервал запуска 30 минут
            "",                                            // дата первой проверки на запуск
            "Y",                                        // агент активен
            date('d.m.Y 08:17:00'),                    // дата первого запуска
            30,
            1                                            // запускать от Системного бота
        );
        $agentID[] = \CAgent::AddAgent(
            "\Tanais\ClientAGR\Cron::doInWeek();",        // имя функции
            'tanais.clientagr',                            // идентификатор модуля
            "Y",                                        // В точное время
            604800,                                        // интервал запуска 1 неделя
            "",                                            // дата первой проверки на запуск
            "Y",                                        // агент активен
            date('d.m.Y 08:00:00'),                    // дата первого запуска
            30,
            1                                            // запускать от Системного бота
        );

        $result = 'Зарегистрированы агенты ' . implode(', ', $agentID);
        return $result;
    }

    static public function unRegisterAgents()
    {
        \CAgent::RemoveModuleAgents(self::MODULE_ID);
    }

    static function doEvery30Minutes()
    {
        return '\Tanais\ClientAGR\Cron::doEvery30Minutes();';
    }

    static function runMorningTasks()
    {
        \Tanais\ClientAGR\Company::updateABC(); //Обновляем ABC локальных компаний
        \Tanais\ClientAGR\Company::linkAutoAllServers(); //Автоматическое связывание компаний
        \Tanais\ClientAGR\Company::updateAllCompanyAGRRegionByRequisite(false); //Автоустановка регионов компаниям с пустыми регионами
        return '\Tanais\ClientAGR\Cron::runMorningTasks();';
    }

    static function runLateMorningTasks()
    {
        \Tanais\ClientAGR\Company::syncABCAllServers(); //Получаем значения ABC от партнерских серверов
        return '\Tanais\ClientAGR\Cron::runLateMorningTasks();';
    }

    static function doInWeek()
    {
        \Tanais\ClientAGR\StateBreedingRegister::updateBreedingRegisterFields();
        return '\Tanais\ClientAGR\Cron::doInWeek();';
    }
}
