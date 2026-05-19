<?

namespace Tanais\ClientAGR;

class DB
{

    static public function select($sql):array
    {
        if (empty($sql))
            return [];
        
        // получаем соединение
        $connection = \Bitrix\Main\Application::getConnection();
        $sqlHelper  = $connection->getSqlHelper();

        $sql = $sql;
        $return=[];
        $dbResult = $connection->query($sql);
        while ($row = $dbResult->fetch())
        {
            $return[]=$row;
        }
        return $return;   
    }

}
