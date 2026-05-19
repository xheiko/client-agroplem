<?

namespace Tanais\ClientAGR\Controller;

use \Bitrix\Main\Error;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Main\Diag\Debug;

class Reference extends \Bitrix\Main\Engine\Controller
{

	public function testAction(): ?array
	{
		return ["Return from \Tanais\ClientAGR\Controller\Reference "];
	}


	public function getAction($refId): ?array
	{
		$optionName = \Tanais\ClientAGR\Reference::REFERENCE_OPTIONS[$refId];
		if (empty($optionName)) {
			$error = "Неизвестный справочник {$refId}";
			$this->addError(new Error($error), 400);
			return [$error];
		}
		return \Tanais\ClientAGR\Reference::getItems($refId);
	}

	//Вебхук для удаленного сервера, когда у него меняется справочник
	public function webhookAction($server = null, $reference = null, $code = null)
	{
		if (empty($server)) {
			$errorText = "Wrong Server parameter";
			$this->addError(new Error($errorText), 400);
			return [
				'result' => false,
				'method' => 'tanais.clientagr.reference.webhook',
				'server' => \Bitrix\Main\Config\Option::get("main", "server_name", ""),
				'message' => $errorText
			];
		}
		if (empty($reference)) {
			$errorText = "Wrong reference parameter";
			$this->addError(new Error($errorText), 400);
			return [
				'result' => false,
				'method' => 'tanais.clientagr.reference.webhook',
				'server' => \Bitrix\Main\Config\Option::get("main", "server_name", ""),
				'message' => $errorText
			];
		}

		\Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Controller\Reference::webhookAction Получили сигнал об изменении элементов справочника {$reference}:{$code}@{$server}");
		define("TANAIS_CLIENTAGR_STOP_SEND_WEBHOOK", true); //Если мы получили вебхук и что меняем, то никого не уведомляем
		\Tanais\ClientAGR\Reference::synchronize($server, $reference);

		return [
			'result' => true,
			'method' => 'tanais.clientagr.reference.webhook',
			'server' => \Bitrix\Main\Config\Option::get("main", "server_name", "")
		];
	}
}
