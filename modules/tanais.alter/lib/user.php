<?

namespace Tanais\Alter;

class User
{
    const  MODULE_ID = 'tanais.alter';
    const  LOG_PREFIX = 'LOG_';

    //\Tanais\Alter\User::getUserNames();

    public static function sendPersonalMessageAgent($userId)
    {
        $senderId = 129;

        $arParams["FIELDS"] = ["NAME"];
        $rsUsers = \CUser::GetList([], [], ['ID' => $userId], $arParams)->Fetch();

        $messageText = $rsUsers['NAME'] . ", добрый день! 
    \nМеня зовут Леонид, я являюсь генеральным директором компании TANAiS. Искренне рад приветствовать тебя в команде профессионалов! Желаю тебе успешного пути вместе с нами и отличных результатов!
    \nПоделись, как первые впечатления?
    \nБуду также признателен, если по итогу первой недели совместной работы ты также предоставишь мне обратную связь. Для меня это очень ценно!";
        \CModule::IncludeModule("im");

        \CIMMessenger::Add(array(
            "TO_USER_ID" => $userId,
            "FROM_USER_ID" => $senderId,
            "MESSAGE" => $messageText,
            "MESSAGE_TYPE" => IM_MESSAGE_PRIVATE
        ));

        \CAgent::RemoveAgent('\Tanais\Alter\User::sendPersonalMessageAgent(' . $userId . ');', "tanais.alter");

        return "";
    }

    public static function getUserNames($id = null)
    {
        $arFilter =
            [
                '!UF_DEPARTMENT' => 'a:0:{}',
                '!UF_DEPARTMENT' => '',
            ];
        $dbDate = \Bitrix\Main\UserTable::getList(array(
            "select" => array("ID", "NAME", "LAST_NAME"),
            "filter" => $arFilter,
        ));
        while ($row = $dbDate->fetch()) {
            $users[$row['ID']] = implode(' ', [$row['LAST_NAME'], $row['NAME']]);
        }
        if (is_array($id)) {
            foreach ($id as $userId) {
                $usersStr .= $users[$userId] . ' ';
            }
            return $usersStr;
        }
        if ($id) {
            return $users[$id];
        }
        return $users;
    }

    //Возвращает сотрудника и всех подчинённых
    public static function getSubordinateEmployees($managerId)
    {
        $subEmployeesDB = \CIntranetUtils::getSubordinateEmployees($managerId, true, 'N');
        while ($subEmployee = $subEmployeesDB->GetNext()) {
            $subEmployees[] = $subEmployee['ID'];
        }
        return $subEmployees;

    }

    public static function getAllUsers()
    {
        $users = [];
        $rsUsers = \CUser::GetList([], [], []);
        while ($user = $rsUsers->Fetch()) {
            $users[$user['ID']] = $user['LAST_NAME'] . ' ' . $user['NAME'];
        }
        return $users;
    }


}