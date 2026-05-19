<?php

namespace Tanais\Alter\Service;

use \Bitrix\Main,
    \Bitrix\Crm\Service;

Main\Loader::requireModule('crm');
//define('ENTITY_TYPE_ID_ACCOUNT', 1046);
//define('ENTITY_TYPE_ID_SUPPLIERS', 1074);

class Container extends Service\Container
{
    const SUPPLY_CONTRACT_TYPE_ID = 1074;
    const INVOICE_TYPE_ID = 1046;
    const HOLDING_ENTITY_ID = 1112; //Справочник холдинги

    const REGION_ENTITY_ID = 1108; //Справочник регионов

    const MANAGEMENT_SYSTEMS_ENTITY_ID = 1116; //Справочник Системы управления стадом

    const ACTIVITY_TYPE_ENTITY_ID = 1120; //Справочник Вид деятельности
    const AGR_COMPANYES_ENTITY_ID = 1126; //Справочник Компании группы AGR

    const VISITS_ENTITY_ID = 1132; //Справочник Компании группы AGR

    public function getFactory(int $entityTypeId): ?Service\Factory
    {
        // Если наш тип - подменяем
        if ($entityTypeId == self::INVOICE_TYPE_ID || $entityTypeId == self::SUPPLY_CONTRACT_TYPE_ID || $entityTypeId == self::HOLDING_ENTITY_ID
            || $entityTypeId == self::REGION_ENTITY_ID || $entityTypeId == self::MANAGEMENT_SYSTEMS_ENTITY_ID || $entityTypeId == self::ACTIVITY_TYPE_ENTITY_ID
            || $entityTypeId == self::AGR_COMPANYES_ENTITY_ID || $entityTypeId == self::VISITS_ENTITY_ID) {
            // Сгенерируем название сервиса ->
            $identifier = static::getIdentifierByClassName(static::$dynamicFactoriesClassName, [$entityTypeId]);
            // ... и проверим - вдруг уже есть объект класса?
            if (Main\DI\ServiceLocator::getInstance()->has($identifier)) {
                return Main\DI\ServiceLocator::getInstance()->get($identifier);
            }

            $type = $this->getTypeByEntityTypeId($entityTypeId);
            if ($type) {
                if ($entityTypeId == self::INVOICE_TYPE_ID) {
                    $factory = new \Tanais\Alter\Service\InvoicePayment\Factory($type);
                }
                if ($entityTypeId == self::SUPPLY_CONTRACT_TYPE_ID) {
                    $factory = new \Tanais\Alter\Service\SupplyContract\Factory($type);
                }
                if ($entityTypeId == self::HOLDING_ENTITY_ID) {
                    $factory = new \Tanais\Alter\Service\Dynamic1112\Factory($type);
                }
                if ($entityTypeId == self::REGION_ENTITY_ID) {
                    $factory = new \Tanais\Alter\Service\Dynamic1108\Factory($type);
                }
                if ($entityTypeId == self::MANAGEMENT_SYSTEMS_ENTITY_ID) {
                    $factory = new \Tanais\Alter\Service\Dynamic1116\Factory($type);
                }
                if ($entityTypeId == self::ACTIVITY_TYPE_ENTITY_ID) {
                    $factory = new \Tanais\Alter\Service\Dynamic1120\Factory($type);
                }
                if ($entityTypeId == self::AGR_COMPANYES_ENTITY_ID) {
                    $factory = new \Tanais\Alter\Service\Dynamic1126\Factory($type);
                }
                if ($entityTypeId == self::VISITS_ENTITY_ID) {
                    $factory = new \Tanais\Alter\Service\Dynamic1132visit\Factory($type);
                }
            }

            Main\DI\ServiceLocator::getInstance()->addInstance(
                $identifier,
                $factory
            );
            // Вернем подмененную фабрику
            return $factory;
        }
        // Если тип не наш - передаем в родительский метод
        return parent::getFactory($entityTypeId);
    }
}