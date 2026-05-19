<?php

namespace Tanais\Support\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Error;
use Bitrix\Main\UserTable;

class Support extends Controller
{
    protected function getDefaultPreFilters()
    {
        return [
            new ActionFilter\Authentication(),
            new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
            new ActionFilter\Csrf(),
        ];
    }

    
    public function getDataFromServerAction()
    {
        global $USER;
        
        $userId = $USER->GetID();
        
        if (!$userId) {
            $this->addError(new Error('Пользователь не авторизован', 'UNAUTHORIZED'));
            return null;
        }
        
        $userData = UserTable::getById($userId)->fetch();
        
        if (!$userData) {
            $this->addError(new Error('Пользователь не найден', 'NOT_FOUND'));
            return null;
        }
        
        $lastName = $userData['LAST_NAME'] ?? '';
        $firstName = $userData['NAME'] ?? '';
        $secondName = $userData['SECOND_NAME'] ?? '';
        // $livechatHash = $_COOKIE['LIVECHAT_HASH'] ?? null;
        
        $fullName = trim($lastName . ' ' . $firstName . ' ' . $secondName);
        
        if (empty($fullName)) {
            $fullName = $USER->GetFullName();
        }
        
        if (empty($fullName)) {
            $fullName = 'Сотрудник';
        }
        
        return [
            'id' => (int)$userId,
            'lastName' => $lastName,
            'firstName' => $firstName,
            'secondName' => $secondName,
            'fullName' => $fullName,
            'email' => $userData['EMAIL'] ?? '',
            'login' => $userData['LOGIN'] ?? '',
            // 'livechatHash' => $livechatHash ?? '',
        ];
    }
}