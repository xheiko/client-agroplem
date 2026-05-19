<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CBPDealOneCNumberNormalize
extends \Bitrix\Bizproc\Activity\BaseActivity
{
	protected static $requiredModules = ["crm", "tanais.alter"];

	public function __construct($name)
	{
		parent::__construct($name);

		//Объявление входных и выходных параметров активити
		$this->arProperties = [];
	}

	//Обязательный метод, возвращающий имя файла с классом активити
	protected static function getFileName(): string
	{
		return __FILE__;
	}

	protected function internalExecute(): \Bitrix\Main\ErrorCollection
	{
		$errors = parent::internalExecute();
		try {
			$rootActivity = $this->getRootActivity();

			//Работа через DocumentService
			$documentService = $this->workflow->getService('DocumentService');
			$document = $documentService->getDocument($this->getDocumentId());

			$onecNumber = explode('-', $document["UF_CRM_DEAL_3867773618127"]);
			if (is_array($onecNumber) && count($onecNumber) == 2) {
				$onecNumber[1] = str_pad($onecNumber[1], 6, "0", STR_PAD_LEFT);
				$newNumber = $onecNumber[0] . '-' . $onecNumber[1];
				$fieldValues = [
					'UF_CRM_DEAL_3867773618127' => $newNumber,
				];
				if ($newNumber != $onecNumber) {
					$this->log("Номер 1С: {$document["UF_CRM_DEAL_3867773618127"]} -> {$newNumber}");
					$documentService->UpdateDocument($this->getDocumentId(), $fieldValues);
				}		
			}

			return $errors;
		} catch (\Throwable $e) {
			$this->writeToTrackingService(sprintf("Error %s on line %s in file %s", $e->getMessage(), $e->getLine(), $e->getFile()), 0, \CBPTrackingType::Error);
		}
		return $errors;
	}

	public static function getPropertiesDialogMap(?\Bitrix\Bizproc\Activity\PropertiesDialog $dialog = null): array
	{
		$map = [
			// 'ParamStr' => [
			// 	'Name' => "Строковый параметр",
			// 	'FieldName' => 'ParamStr',
			// 	'Type' => \Bitrix\Bizproc\FieldType::STRING,
			// 	'Required' => true,
			// 	'Options' => [],
			// ],
			// 'User' => [
			// 	'Name' => "Выбор пользователя",
			// 	'FieldName' => 'User',
			// 	'Type' => \Bitrix\Bizproc\FieldType::USER,
			// 	'Required' => true,
			// 	'Options' => [],
			// ],
			// 'Comment' => [
			// 	'Name' => "Комментарий",
			// 	'FieldName' => 'Comment',
			// 	'Type' => \Bitrix\Bizproc\FieldType::TEXT,
			// 	'Required' => true,
			// 	'Options' => [],
			// ],
		];
		return $map;
	}

	// FieldType::BOOL (bool)
	// FieldType::DATE (date)
	// FieldType::DATETIME (datetime)
	// FieldType::DOUBLE (double)
	// FieldType::FILE (file)
	// FieldType::INT (int)
	// FieldType::SELECT (select)
	// FieldType::INTERNALSELECT (internalselect)
	// FieldType::STRING (string)
	// FieldType::TEXT (text)
	// FieldType::USER (user)
	// FieldType::TIME (time)
}
