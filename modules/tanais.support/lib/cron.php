<?

namespace Tanais\Support;

class Cron
{
	const  MODULE_ID = 'tanais.workplace';

	static public function registerAgents()
	{
		\CAgent::RemoveModuleAgents(self::MODULE_ID);
		$agentID = [];

		// $agentID[] = \CAgent::AddAgent(
		// 	"\Tanais\Alta\Cron::doEvery30Minutes();", // имя функции
		// 	'tanais.alta',                          	// идентификатор модуля
		// 	"N",                                  		// N next_exec = дата последнего запуска + interval
		// 	1800,                                		// интервал запуска 30 минут
		// 	"",                							// дата первой проверки на запуск
		// 	"Y",                                  		// агент активен
		// 	"",                							// дата первого запуска
		// 	30,
		// 	1											// запускать от Системного бота
		// );
		// $agentID[] = \CAgent::AddAgent(
		// 	"\Tanais\Alta\Cron::doInMorning();", 		// имя функции
		// 	'tanais.alta',                          	// идентификатор модуля
		// 	"Y",                                  		// В точное время
		// 	86400,                                		// интервал запуска 30 минут
		// 	"",                							// дата первой проверки на запуск
		// 	"Y",                                  		// агент активен
		// 	date('d.m.Y 06:00:00'),          			// дата первого запуска
		// 	30,
		// 	1											// запускать от Системного бота
		// );

		$result = 'Зарегистрированы агенты ' . implode(', ', $agentID);
		return $result;
	}

	static public function unRegisterAgents()
	{
		\CAgent::RemoveModuleAgents(self::MODULE_ID);
	}

	// static function doEvery30Minutes()
	// {
	// 	return '\Tanais\Alta\Cron::doEvery30Minutes();';
	// }

	// static function doInMorning()
	// {
	// 	return '\Tanais\Alta\Cron::doInMorning();';
	// }
}
