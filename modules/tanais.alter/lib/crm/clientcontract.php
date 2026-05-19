<?

namespace Tanais\Alter\Crm;

class ClientContract
{
	const ENTITY_ID 	= 1050;

	public static function getCompatibleData($clientContractId = null)
	{
		if ((empty($clientContractId)) or (intval($clientContractId) == 0))
			return [];
		$container = \Bitrix\Crm\Service\Container::getInstance();
		$factory = $container->getFactory(self::ENTITY_ID);
		$clientContract = $factory->getItem($clientContractId);
		if ($clientContract)
			return $clientContract->getCompatibleData();
		return [];
	}
}
