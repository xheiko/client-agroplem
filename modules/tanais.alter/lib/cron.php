<?

namespace Tanais\Alter;

use Tanais\Alter\Crm\Deal;

class Cron
{
    const  MODULE_ID = 'tanais.alter';

    static public function registerAgents()
    {
        \CAgent::RemoveModuleAgents(self::MODULE_ID);
        $agentID = [];
        $agentID[] = \CAgent::AddAgent(
            "\Tanais\Alter\Cron::doEvery30Minutes();", // имя функции
            'tanais.alter',                              // идентификатор модуля
            "N",                                        // N next_exec = дата последнего запуска + interval
            1800,                                       // интервал запуска 30 минут
            "",                                         // дата первой проверки на запуск
            "Y",                                        // агент активен
            "",                                         // дата первого запуска
            30,
            1                                           // запускать от Системного бота
        );
        $agentID[] = \CAgent::AddAgent(
            "\Tanais\Alter\Cron::doEvery2Hour();",       // имя функции
            'tanais.alter',                              // идентификатор модуля
            "N",                                        // N next_exec = дата последнего запуска + interval
            7200,                                       // интервал запуска 30 минут
            "",                                         // дата первой проверки на запуск
            "Y",                                        // агент активен
            "",                                         // дата первого запуска
            30,
            1                                           // запускать от Системного бота
        );

        $agentID[] = \CAgent::AddAgent(
            "\Tanais\Alter\Cron::doInMorning();",        // имя функции
            'tanais.alter',                              // идентификатор модуля
            "Y",                                        // В точное время
            86400,                                      // интервал запуска 30 минут
            "",                                         // дата первой проверки на запуск
            "Y",                                        // агент активен
            date('d.m.Y 06:00:00'),                     // дата первого запуска
            30,
            1                                           // запускать от Системного бота
        );
        $agentID[] = \CAgent::AddAgent(
            "\Tanais\Alter\Cron::doInLateMorning();",        // имя функции
            'tanais.alter',                              // идентификатор модуля
            "Y",                                        // В точное время
            86400,                                      // интервал запуска 30 минут
            "",                                         // дата первой проверки на запуск
            "Y",                                        // агент активен
            date('d.m.Y 09:00:00'),                     // дата первого запуска
            30,
            1                                           // запускать от Системного бота
        );
        $agentID[] = \CAgent::AddAgent(
            "\Tanais\Alter\Cron::doInEvening();",        // имя функции
            'tanais.alter',                              // идентификатор модуля
            "Y",                                        // В точное время
            86400,                                      // интервал запуска 30 минут
            "",                                         // дата первой проверки на запуск
            "Y",                                        // агент активен
            date('d.m.Y 19:00:00'),                     // дата первого запуска
            30,
            1                                           // запускать от Системного бота
        );
        $agentID[] = \CAgent::AddAgent(
            "\Tanais\Alter\Cron::doInMidday();",         // имя функции
            'tanais.alter',                              // идентификатор модуля
            "Y",                                        // В точное время
            86400,                                      // интервал запуска 30 минут
            "",                                         // дата первой проверки на запуск
            "Y",                                        // агент активен
            date('d.m.Y 14:00:00'),                     // дата первого запуска
            30,
            1                                           // запускать от Системного бота
        );
        $agentID[] = \CAgent::AddAgent(
            "\Tanais\Alter\Cron::doInNight();",        // имя функции
            'tanais.alter',                              // идентификатор модуля
            "Y",                                        // В точное время
            86400,                                      // интервал запуска 30 минут
            "",                                         // дата первой проверки на запуск
            "Y",                                        // агент активен
            date('d.m.Y 21:00:00'),                     // дата первого запуска
            30,
            1                                           // запускать от Системного бота
        );
        $result = 'Зарегистрированы агенты ' . implode(', ', $agentID);
        \COption::SetOptionString(self::MODULE_ID, "agents", json_encode($agentID));
        \Tanais\Alter\Log::save('\Tanais\Alter\Cron::registerAgents()', $result);

        return $result;
    }

    static public function unRegisterAgents()
    {
        \CAgent::RemoveModuleAgents(self::MODULE_ID);
        \Tanais\Alter\Log::save('\Tanais\Alter\Cron::unRegisterAgents()', 'Удалены все агенты');
    }

    static function doEvery30Minutes()
    {

        return '\Tanais\Alter\Cron::doEvery30Minutes();';
    }

    static function doEvery2Hour()
    {

        return '\Tanais\Alter\Cron::doEvery2Hour();';
    }

    static function doInMorning()
    {
        // \Tanais\Alter\Crm\Deal::regulatoryDeadline();
        \Tanais\Alter\Crm\Company::updateSpentDays(); //Время выполнения: 0.322251
        \Tanais\Alter\Crm\Company::companyUpdateRevenue(); //Время выполнения: 2.202540
        \Tanais\Alter\Crm\Company::getRegionForm(); //Время выполнения: 0.587793
        \Tanais\Alter\Crm\Deal::updateAllOverdueDebts();
        \Tanais\Alter\Crm\Company::updateAllCompanyOverdueDebts();
        \Tanais\Alter\Crm\Company::setUsedLaboratory();
        // \Tanais\Alter\Crm\StateBreedingRegister::updateCompanyBreedingRegister(); //Долго!!!
        return '\Tanais\Alter\Cron::doInMorning();';
    }

    static function doInLateMorning()
    {
        \Tanais\Alter\Crm\Deal::regulatoryDeadline();
        return '\Tanais\Alter\Cron::doInLateMorning();';
    }

    static function doInEvening()
    {


        return '\Tanais\Alter\Cron::doInEvening();';
    }

    static function doInNight()
    {
        \Tanais\Alter\Crm\Deal::resendTodayDealsToOne();

        return '\Tanais\Alter\Cron::doInNight();';
    }

    static function doInMidday()
    {

        return '\Tanais\Alter\Cron::doInMidday();';
    }
}