<?

namespace Tanais\ClientAGR\Controller;

use \Bitrix\Main\Error;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Main\Diag\Debug;

class Company extends \Bitrix\Main\Engine\Controller
{

	public function testAction(): ?array
	{
		return ["Return from \Tanais\ClientAGR\Controller\Company "];
	}

	//Получить компанию по ID
	public function getAction($companyId): ?array
	{
		if (!(intval($companyId) > 0)) {
			$this->addError(new Error("Empty company id=$companyId"), 400);
			return ["Empty company id=$companyId"];
		}
		$companyData = \Tanais\ClientAGR\Company::getCompatibleData($companyId);
		if ($companyData)
			return $companyData;
		$this->addError(new Error("Could not find company id=$companyId"), 400);
		return ["Could not find company id=$companyId"];
	}

	//Получить список всех компаний
	public function listAction(): ?array
	{
		\Bitrix\Main\Loader::includeModule('crm');
		$sql = "
			SELECT 
				comp.ID,
				comp.TITLE,
				uts.UF_CRM_COMPANY_AGR_LINK,
				uts.UF_CRM_COMPANY_AGR_UPDATED,
				uts.UF_CRM_COMPANY_AGR_UPDATED_BY,
				uts.UF_CRM_COMPANY_AGR_UPDATED_BY_ID,
				uts.UF_CRM_COMPANY_AGR_UPDATED_SITE,
				IFNULL(req.RQ_INN, '') as RQ_INN,
				IFNULL(req.RQ_KPP, '') as RQ_KPP,
				IFNULL(req.RQ_OGRN, '') as RQ_OGRN,
				REPLACE(CONCAT(IFNULL(adr.PROVINCE, ''),', ',IFNULL(adr.REGION, ''),', ',IFNULL(adr.CITY, ''),', ',IFNULL(adr.ADDRESS_1, ''),' ',IFNULL(adr.ADDRESS_2, '')),', , , ','') as ADDRESS,
				uts.UF_CRM_COMPANY_AGR_UPDATED as UF_CRM_COMPANY_AGR_UPDATED,
				uts.UF_CRM_COMPANY_AGR_UPDATED_SITE as UF_CRM_COMPANY_AGR_UPDATED_SITE,
				uts.UF_CRM_COMPANY_AGR_UPDATED_BY as UF_CRM_COMPANY_AGR_UPDATED_BY,
				uts.UF_CRM_COMPANY_AGR_UPDATED_BY_ID as UF_CRM_COMPANY_AGR_UPDATED_BY_ID,
				uts.UF_CRM_COMPANY_AGR_LINK as UF_CRM_COMPANY_AGR_LINK,
				UF_CRM_COMPANY_AGR_A_CLIENT	as UF_CRM_COMPANY_AGR_A_CLIENT,
				UF_CRM_COMPANY_AGR_B_CLIENT	as UF_CRM_COMPANY_AGR_B_CLIENT,
				UF_CRM_COMPANY_AGR_C_CLIENT as UF_CRM_COMPANY_AGR_C_CLIENT
			FROM 
				b_crm_company comp
				LEFT JOIN b_crm_requisite req on (req .ENTITY_ID= comp.ID)
				LEFT JOIN b_crm_addr      adr on (adr .ENTITY_ID= req .ID)
				LEFT JOIN b_uts_crm_company uts  on (uts.VALUE_ID= comp .ID)
			WHERE
				comp.COMPANY_TYPE='CUSTOMER' and
				req.ENTITY_TYPE_ID=4 and
				adr.ENTITY_TYPE_ID=8 and
				adr.TYPE_ID=6					
			GROUP BY comp.ID
			ORDER BY comp.ID ASC
			LIMIT 100000
		";
		$companies = \Tanais\ClientAGR\DB::select($sql);
		$return = [];

		//Делаем массив для конвертации Организаций в Коды
		$reference = new \Tanais\ClientAGR\Controller\Reference();
		$referenceValues = $reference->getAction('Mycompany');
		$myCompanies = [];
		foreach ($referenceValues as $referenceKey => $referenceValue) {
			$myCompanies[$referenceValue["ID"]] = $referenceKey;
		}
		unset($reference);
		unset($referenceValues);

		$localServerCode = \Tanais\ClientAGR\Reference::getThisServerRef()['CODE'];

		foreach ($companies as $key => &$company) {
			if (is_string($company['UF_CRM_COMPANY_AGR_LINK']))
				$company['UF_CRM_COMPANY_AGR_LINK'] = unserialize($company['UF_CRM_COMPANY_AGR_LINK'], ['allowed_classes' => false]);
			else
				$company['UF_CRM_COMPANY_AGR_LINK'] = [];

			//Разбираем множественное поле

			if (is_string($company['UF_CRM_COMPANY_AGR_A_CLIENT']) && !empty($company['UF_CRM_COMPANY_AGR_A_CLIENT']))
				$company['UF_CRM_COMPANY_AGR_A_CLIENT'] = unserialize($company['UF_CRM_COMPANY_AGR_A_CLIENT']);
			if (is_string($company['UF_CRM_COMPANY_AGR_B_CLIENT']) && !empty($company['UF_CRM_COMPANY_AGR_B_CLIENT']))
				$company['UF_CRM_COMPANY_AGR_B_CLIENT'] = unserialize($company['UF_CRM_COMPANY_AGR_B_CLIENT']);
			if (is_string($company['UF_CRM_COMPANY_AGR_C_CLIENT']) && !empty($company['UF_CRM_COMPANY_AGR_C_CLIENT']))
				$company['UF_CRM_COMPANY_AGR_C_CLIENT'] = unserialize($company['UF_CRM_COMPANY_AGR_C_CLIENT']);
			if (!is_array($company['UF_CRM_COMPANY_AGR_A_CLIENT']))
				$company['UF_CRM_COMPANY_AGR_A_CLIENT'] = [];
			if (!is_array($company['UF_CRM_COMPANY_AGR_B_CLIENT']))
				$company['UF_CRM_COMPANY_AGR_B_CLIENT'] = [];
			if (!is_array($company['UF_CRM_COMPANY_AGR_C_CLIENT']))
				$company['UF_CRM_COMPANY_AGR_C_CLIENT'] = [];


			//Кновертируем из ID на коды по справочнику
			foreach ($company['UF_CRM_COMPANY_AGR_A_CLIENT'] as &$companyCode)
				$companyCode = $myCompanies[$companyCode];
			foreach ($company['UF_CRM_COMPANY_AGR_B_CLIENT'] as &$companyCode)
				$companyCode = $myCompanies[$companyCode];
			foreach ($company['UF_CRM_COMPANY_AGR_C_CLIENT'] as &$companyCode)
				$companyCode = $myCompanies[$companyCode];

			//ABC_CLIENT отражаем местный сервер в фейкполе
			if (is_array($company['UF_CRM_COMPANY_AGR_A_CLIENT']) && in_array($localServerCode, $company['UF_CRM_COMPANY_AGR_A_CLIENT']))
				$company['ABC_CLIENT'] = 'A';
			if (is_array($company['UF_CRM_COMPANY_AGR_B_CLIENT']) && in_array($localServerCode, $company['UF_CRM_COMPANY_AGR_B_CLIENT']))
				$company['ABC_CLIENT'] = 'B';
			if (is_array($company['UF_CRM_COMPANY_AGR_C_CLIENT']) && in_array($localServerCode, $company['UF_CRM_COMPANY_AGR_C_CLIENT']))
				$company['ABC_CLIENT'] = 'C';
			$company['ABC_REF_CODE'] = $localServerCode;
			$return[$company['ID']] = $company;
		}
		return $return;
	}

	//Получить список всех компаний
	public function dataReportAction(): ?array
	{
		\Bitrix\Main\Loader::includeModule('crm');
		$sql = "
			SELECT 
				comp.ID,
				comp.TITLE,
				uts.UF_CRM_COMPANY_AGR_LINK,
				uts.UF_CRM_COMPANY_AGR_UPDATED,
				uts.UF_CRM_COMPANY_AGR_UPDATED_BY,
				uts.UF_CRM_COMPANY_AGR_UPDATED_BY_ID,
				uts.UF_CRM_COMPANY_AGR_UPDATED_SITE,
				IFNULL(req.RQ_INN, '') as RQ_INN,
				IFNULL(req.RQ_KPP, '') as RQ_KPP,
				IFNULL(req.RQ_OGRN, '') as RQ_OGRN,
				REPLACE(CONCAT(IFNULL(adr.PROVINCE, ''),', ',IFNULL(adr.REGION, ''),', ',IFNULL(adr.CITY, ''),', ',IFNULL(adr.ADDRESS_1, ''),' ',IFNULL(adr.ADDRESS_2, '')),', , , ','') as ADDRESS,
				uts.UF_CRM_COMPANY_AGR_UPDATED as UF_CRM_COMPANY_AGR_UPDATED,
				uts.UF_CRM_COMPANY_AGR_UPDATED_SITE as UF_CRM_COMPANY_AGR_UPDATED_SITE,
				uts.UF_CRM_COMPANY_AGR_UPDATED_BY as UF_CRM_COMPANY_AGR_UPDATED_BY,
				uts.UF_CRM_COMPANY_AGR_UPDATED_BY_ID as UF_CRM_COMPANY_AGR_UPDATED_BY_ID,
				uts.UF_CRM_COMPANY_AGR_AGROHOLDING as UF_CRM_COMPANY_AGR_AGROHOLDING,
				uts.UF_CRM_COMPANY_AGR_GROUP_COMPANY as UF_CRM_COMPANY_AGR_GROUP_COMPANY,
				uts.UF_CRM_COMPANY_AGR_REGION as UF_CRM_COMPANY_AGR_REGION,
				uts.UF_CRM_COMPANY_AGR_ACTIVITY_TYPE as UF_CRM_COMPANY_AGR_ACTIVITY_TYPE,
				uts.UF_CRM_COMPANY_AGR_TOTAL_HEADS_ALL_KINDS as UF_CRM_COMPANY_AGR_TOTAL_HEADS_ALL_KINDS,
				uts.UF_CRM_COMPANY_AGR_DAIRY_COWS as UF_CRM_COMPANY_AGR_DAIRY_COWS,
				uts.UF_CRM_COMPANY_AGR_HEIFER as UF_CRM_COMPANY_AGR_HEIFER,
				uts.UF_CRM_COMPANY_AGR_LINK as UF_CRM_COMPANY_AGR_LINK,
				UF_CRM_COMPANY_AGR_A_CLIENT as UF_CRM_COMPANY_AGR_A_CLIENT,
				UF_CRM_COMPANY_AGR_B_CLIENT as UF_CRM_COMPANY_AGR_B_CLIENT,
				UF_CRM_COMPANY_AGR_C_CLIENT as UF_CRM_COMPANY_AGR_C_CLIENT,
				UF_CRM_COMPANY_AGR_COMMENT as UF_CRM_COMPANY_AGR_COMMENT,
				deals.DEAL_BEGINDATE_FIRST,
				deals.DEAL_BEGINDATE_FIRST_ID,
				deals.DEAL_BEGINDATE_LAST,
				deals.DEAL_BEGINDATE_LAST_ID,
				deals.DEAL_SUCCESS_SUM,
				deals.DEAL_QC_SUM,
				deals.DEAL_Q1_SUM,
				deals.DEAL_Q2_SUM,
				deals.DEAL_Q3_SUM,
				deals.DEAL_Q4_SUM
			FROM 
				b_crm_company comp
				LEFT JOIN b_crm_requisite req on (req.ENTITY_ID = comp.ID)
				LEFT JOIN b_crm_addr adr on (adr.ENTITY_ID = req.ID)
				LEFT JOIN b_uts_crm_company uts on (uts.VALUE_ID = comp.ID)
				LEFT JOIN (
					SELECT 
						d.COMPANY_ID,

						-- первая успешная сделка
						MIN(d.BEGINDATE) AS DEAL_BEGINDATE_FIRST,
						SUBSTRING_INDEX(
							GROUP_CONCAT(d.ID ORDER BY d.BEGINDATE ASC),
							',',
							1
						) AS DEAL_BEGINDATE_FIRST_ID,

						-- последняя успешная сделка
						MAX(d.BEGINDATE) AS DEAL_BEGINDATE_LAST,
						SUBSTRING_INDEX(
							GROUP_CONCAT(d.ID ORDER BY d.BEGINDATE DESC),
							',',
							1
						) AS DEAL_BEGINDATE_LAST_ID,

						-- сумма успешных сделок
						SUM(d.OPPORTUNITY_ACCOUNT) AS DEAL_SUCCESS_SUM,
						
						-- оборот успешных сделок текущего квартала
						SUM(
							CASE 
								WHEN QUARTER(d.BEGINDATE) = QUARTER(CURDATE())
								AND YEAR(d.BEGINDATE) = YEAR(CURDATE())
								THEN d.OPPORTUNITY_ACCOUNT
								ELSE 0
							END
						) AS DEAL_QC_SUM,

						-- сумма успешных сделок 1 квартал назад
						SUM(
							CASE
								WHEN QUARTER(d.BEGINDATE) = QUARTER(DATE_SUB(CURDATE(), INTERVAL 1 QUARTER))
								AND YEAR(d.BEGINDATE)   = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 QUARTER))
								THEN d.OPPORTUNITY_ACCOUNT
								ELSE 0
							END
						) AS DEAL_Q1_SUM,

						-- сумма успешных сделок 2 квартала назад
						SUM(
							CASE
								WHEN QUARTER(d.BEGINDATE) = QUARTER(DATE_SUB(CURDATE(), INTERVAL 2 QUARTER))
								AND YEAR(d.BEGINDATE)   = YEAR(DATE_SUB(CURDATE(), INTERVAL 2 QUARTER))
								THEN d.OPPORTUNITY_ACCOUNT
								ELSE 0
							END
						) AS DEAL_Q2_SUM,

						-- сумма успешных сделок 3 квартала назад
						SUM(
							CASE
								WHEN QUARTER(d.BEGINDATE) = QUARTER(DATE_SUB(CURDATE(), INTERVAL 3 QUARTER))
								AND YEAR(d.BEGINDATE)   = YEAR(DATE_SUB(CURDATE(), INTERVAL 3 QUARTER))
								THEN d.OPPORTUNITY_ACCOUNT
								ELSE 0
							END
						) AS DEAL_Q3_SUM,

						-- сумма успешных сделок 4 квартала назад
						SUM(
							CASE
								WHEN QUARTER(d.BEGINDATE) = QUARTER(DATE_SUB(CURDATE(), INTERVAL 4 QUARTER))
								AND YEAR(d.BEGINDATE)   = YEAR(DATE_SUB(CURDATE(), INTERVAL 4 QUARTER))
								THEN d.OPPORTUNITY_ACCOUNT
								ELSE 0
							END
						) AS DEAL_Q4_SUM
					FROM b_crm_deal d
					WHERE d.STAGE_SEMANTIC_ID = 'S'
					GROUP BY d.COMPANY_ID
				) AS deals ON deals.COMPANY_ID = comp.ID
			WHERE
				comp.COMPANY_TYPE='CUSTOMER' AND
				req.ENTITY_TYPE_ID=4 AND
				adr.ENTITY_TYPE_ID=8 AND
				adr.TYPE_ID=6

			GROUP BY comp.ID
			ORDER BY comp.ID ASC
			LIMIT 100000
		";
		$companies = \Tanais\ClientAGR\DB::select($sql);

		$reference = new \Tanais\ClientAGR\Controller\Reference();

		//Делаем массив для конвертации Организаций в Коды
		$referenceValues = $reference->getAction('Mycompany');
		$myCompanies = [];
		$agrCompanies = [];
		foreach ($referenceValues as $referenceKey => $referenceValue) {
			$myCompanies[$referenceValue["ID"]] = $referenceKey;
			$agrCompanies[$referenceValue["ID"]] = $referenceValue;
		}
		unset($referenceValues);

		$holdings = [];
		$referenceValues = $reference->getAction('Holding');
		foreach ($referenceValues as $referenceKey => $referenceValue) {
			$holdings[$referenceValue["ID"]] = $referenceValue;
		}
		unset($referenceValues);

		$regions = [];
		$referenceValues = $reference->getAction('Region');
		foreach ($referenceValues as $referenceKey => $referenceValue) {
			$regions[$referenceValue["ID"]] = $referenceValue;
		}
		unset($referenceValues);

		$businesss = [];
		$referenceValues = $reference->getAction('Business');
		foreach ($referenceValues as $referenceKey => $referenceValue) {
			$businesss[$referenceValue["ID"]] = $referenceValue; // Вид деятельности
		}
		unset($referenceValues);

		// d($businesss);
		// die;

		unset($reference);

		$return = [];

		$localServerCode = \Tanais\ClientAGR\Reference::getThisServerRef()['CODE'];

		foreach ($companies as $key => &$company) {
			if (is_string($company['UF_CRM_COMPANY_AGR_LINK']))
				$company['UF_CRM_COMPANY_AGR_LINK'] = unserialize($company['UF_CRM_COMPANY_AGR_LINK'], ['allowed_classes' => false]);
			else
				$company['UF_CRM_COMPANY_AGR_LINK'] = [];

			if (is_string($company['UF_CRM_COMPANY_AGR_REGION']))
				$company['UF_CRM_COMPANY_AGR_REGION'] = unserialize($company['UF_CRM_COMPANY_AGR_REGION'], ['allowed_classes' => false]);
			else
				$company['UF_CRM_COMPANY_AGR_REGION'] = [];

			if (is_string($company['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE'])) {
				$company['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE'] = unserialize($company['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE'], ['allowed_classes' => false]);
			} else {
				$company['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE'] = [];
			}

			//Разбираем множественное поле
			if (is_string($company['UF_CRM_COMPANY_AGR_A_CLIENT']) && !empty($company['UF_CRM_COMPANY_AGR_A_CLIENT']))
				$company['UF_CRM_COMPANY_AGR_A_CLIENT'] = unserialize($company['UF_CRM_COMPANY_AGR_A_CLIENT']);
			if (is_string($company['UF_CRM_COMPANY_AGR_B_CLIENT']) && !empty($company['UF_CRM_COMPANY_AGR_B_CLIENT']))
				$company['UF_CRM_COMPANY_AGR_B_CLIENT'] = unserialize($company['UF_CRM_COMPANY_AGR_B_CLIENT']);
			if (is_string($company['UF_CRM_COMPANY_AGR_C_CLIENT']) && !empty($company['UF_CRM_COMPANY_AGR_C_CLIENT']))
				$company['UF_CRM_COMPANY_AGR_C_CLIENT'] = unserialize($company['UF_CRM_COMPANY_AGR_C_CLIENT']);
			if (!is_array($company['UF_CRM_COMPANY_AGR_A_CLIENT']))
				$company['UF_CRM_COMPANY_AGR_A_CLIENT'] = [];
			if (!is_array($company['UF_CRM_COMPANY_AGR_B_CLIENT']))
				$company['UF_CRM_COMPANY_AGR_B_CLIENT'] = [];
			if (!is_array($company['UF_CRM_COMPANY_AGR_C_CLIENT']))
				$company['UF_CRM_COMPANY_AGR_C_CLIENT'] = [];

			$UF_CRM_COMPANY_AGR_A_CLIENT = $company['UF_CRM_COMPANY_AGR_A_CLIENT'];
			$UF_CRM_COMPANY_AGR_B_CLIENT = $company['UF_CRM_COMPANY_AGR_B_CLIENT'];
			$UF_CRM_COMPANY_AGR_C_CLIENT = $company['UF_CRM_COMPANY_AGR_C_CLIENT'];

			//Кновертируем из ID на коды по справочнику
			foreach ($company['UF_CRM_COMPANY_AGR_A_CLIENT'] as &$companyCode)
				$companyCode = $myCompanies[$companyCode];
			foreach ($company['UF_CRM_COMPANY_AGR_B_CLIENT'] as &$companyCode)
				$companyCode = $myCompanies[$companyCode];
			foreach ($company['UF_CRM_COMPANY_AGR_C_CLIENT'] as &$companyCode)
				$companyCode = $myCompanies[$companyCode];

			//ABC_CLIENT отражаем местный сервер в фейкполе
			if (is_array($company['UF_CRM_COMPANY_AGR_A_CLIENT']) && in_array($localServerCode, $company['UF_CRM_COMPANY_AGR_A_CLIENT']))
				$company['ABC_CLIENT'] = 'A';
			if (is_array($company['UF_CRM_COMPANY_AGR_B_CLIENT']) && in_array($localServerCode, $company['UF_CRM_COMPANY_AGR_B_CLIENT']))
				$company['ABC_CLIENT'] = 'B';
			if (is_array($company['UF_CRM_COMPANY_AGR_C_CLIENT']) && in_array($localServerCode, $company['UF_CRM_COMPANY_AGR_C_CLIENT']))
				$company['ABC_CLIENT'] = 'C';
			$company['ABC_REF_CODE'] = $localServerCode;

			$company['UF_CRM_COMPANY_AGR_AGROHOLDING'] = (array)$company['UF_CRM_COMPANY_AGR_AGROHOLDING'];
			$tmp = [];
			foreach ($company['UF_CRM_COMPANY_AGR_AGROHOLDING'] as $holdingId) {
				if (!empty($holdings[$holdingId]['TITLE'])) {
					$tmp[] = $holdings[$holdingId]['TITLE'];
				}
			}
			$company['UF_CRM_COMPANY_AGR_AGROHOLDING'] = $tmp;

			$company['UF_CRM_COMPANY_AGR_REGION'] = (array)$company['UF_CRM_COMPANY_AGR_REGION'];
			$tmp = [];
			foreach ($company['UF_CRM_COMPANY_AGR_REGION'] as $regionId) {
				if (!empty($regions[$regionId]['TITLE'])) {
					$tmp[] = $regions[$regionId]['TITLE'];
				}
			}
			$company['UF_CRM_COMPANY_AGR_REGION'] = $tmp;

			$company['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE'] = (array)$company['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE'];
			$tmp = [];
			foreach ($company['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE'] as $businessId) {
				if (!empty($businesss[$businessId]['TITLE'])) {
					$tmp[] = $businesss[$businessId]['TITLE'];
				}
			}
			$company['UF_CRM_COMPANY_AGR_ACTIVITY_TYPE'] = $tmp;

			//
			$tmp = [];
			foreach ($UF_CRM_COMPANY_AGR_A_CLIENT as $agrCompaniesId) {
				if (!empty($agrCompanies[$agrCompaniesId]['TITLE'])) {
					$tmp[] = $agrCompanies[$agrCompaniesId]['TITLE'];
				}
			}
			$company['UF_CRM_COMPANY_AGR_A_CLIENT_AR'] = $tmp;

			$tmp = [];
			foreach ($UF_CRM_COMPANY_AGR_B_CLIENT as $agrCompaniesId) {
				if (!empty($agrCompanies[$agrCompaniesId]['TITLE'])) {
					$tmp[] = $agrCompanies[$agrCompaniesId]['TITLE'];
				}
			}
			$company['UF_CRM_COMPANY_AGR_B_CLIENT_AR'] = $tmp;

			$tmp = [];
			foreach ($UF_CRM_COMPANY_AGR_C_CLIENT as $agrCompaniesId) {
				if (!empty($agrCompanies[$agrCompaniesId]['TITLE'])) {
					$tmp[] = $agrCompanies[$agrCompaniesId]['TITLE'];
				}
			}
			$company['UF_CRM_COMPANY_AGR_C_CLIENT_AR'] = $tmp;
			// /

			$return[$company['ID']] = $company;
		}
		return $return;
	}


	//Вебхук для удаленного сервера, когда у него меняется компания
	public function webhookAction($server = null, $companyId = null)
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
		if (!(intval($companyId) > 0)) {
			$errorText = "Wrong companyId parameter";
			$this->addError(new Error($errorText), 400);
			return [
				'result' => false,
				'method' => 'tanais.clientagr.reference.webhook',
				'server' => \Bitrix\Main\Config\Option::get("main", "server_name", ""),
				'message' => $errorText
			];
		}
		\Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Controller\Company::webhookAction Получили сигнал об изменении компании id=$companyId на $server");
		$localCompaniesIds = \Tanais\ClientAGR\Company::getLinkedCompanyId($companyId, $server);
		foreach ($localCompaniesIds as $localCompanyId) {
			// \Tanais\ClientAGR\Log::add("\Tanais\ClientAGR\Controller\Company::webhookAction Должны были синхронизовать компанияю id=$localCompanyId с $server");
			define("TANAIS_CLIENTAGR_STOP_SEND_WEBHOOK", true); //Если мы получили вебхук и что меняем, то никого не уведомляем
			\Tanais\ClientAGR\Company::synchronize($localCompanyId, $server);
		}
		return [
			'result' => true,
			'method' => 'tanais.clientagr.company.webhook',
			'server' => \Bitrix\Main\Config\Option::get("main", "server_name", "")
		];
	}
}
