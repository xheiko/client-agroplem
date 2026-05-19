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

	public function companyTestAction(): ?array
	{
		return ["TEST return"];
	}

	public function getAction($companyId): ?array
	{
		if (!(intval($companyId) > 0)) {
			$this->addError(new Error("Empty company id=$companyId"), 400);
			return ["Empty company id=$companyId"];
		}
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Company);
		$company = $factory->getItem($companyId);
		if ($company) {
			$companyData = $company->getCompatibleData();
			return [$companyData];
		}
		$this->addError(new Error("Could not find company id=$companyId"), 400);
		return ["Could not find company id=$companyId"];
	}

	public function listAction(): ?array
	{
		\Bitrix\Main\Loader::includeModule('crm');
		$sql="
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
				REPLACE(CONCAT(IFNULL(adr.PROVINCE, ''),', ',IFNULL(adr.REGION, ''),', ',IFNULL(adr.CITY, ''),', ',IFNULL(adr.ADDRESS_1, ''),' ',IFNULL(adr.ADDRESS_2, '')),', , , ','') as ADDRESS
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
			LIMIT 10000
		";
		$companies=\Tanais\ClientAGR\DB::select($sql);
		return $companies;
	}
}
