<?php

namespace Tanais\Alter\Log\Loggers;

use Tanais\Alter\Log\Interfaces\LoggerInterface,
    Bitrix\Main\Diag\Debug;

class MailLogger implements LoggerInterface
{
    public function writeToLogFile($fileName, $message): void
    {				
		$data = "\r\n" . date('d.m.Y H:i:s ') . "\r\n" .var_export($message,true);
        file_put_contents($fileName, $data , FILE_APPEND);
    }

    public function log($message): void
    {
        $fileName = '/home/bitrix/www/local/log/unisender_error.log';
        $this->writeToLogFile($fileName, $message);
    }

    public function logSendedMail($message): void
    {
        $fileName = '/home/bitrix/www/local/log/unisender_mail.log';
		// $this->debug($message);

		$fileCount=0;
		if (is_array($message[1]['message']['attachments']))
			$fileCount=count($message[1]['message']['attachments']);
		
		$recipients=[];
		if (is_array($message[1]['message']['recipients']))
			$recipients=$message[1]['message']['recipients'];
		foreach ($recipients as &$recipient)
			if (is_array($recipient))
				$recipient=$recipient['email'];
		$subject=iconv_mime_decode($message[1]['message']['subject']);
		if (empty($subject))
			$subject=$message[1]['message']['subject'];
		
		$html = $message[1]['message']['body']['html'];
		
        $data = "\r\n" . date('d.m.Y H:i:s ') .' '.\Bitrix\Main\Engine\CurrentUser::get()->getLogin(). "\r\n";
		$data .= $message[1]['message']['from_email'].' -> '. implode(', ', $recipients). "\r\n";
		$data .= 'subject: '. iconv_mime_decode($message[1]['message']['subject']). "\r\n";				
		$data .= 'message: '. strlen($html). " байт, ".mb_substr(str_replace(array("\r","\n"),"",trim(strip_tags($html))),0,60)."....\r\n";	
		$data .= 'attachments: '. $fileCount. " файл(ов) \r\n";		
        file_put_contents($fileName, $data, FILE_APPEND);    
	}

    public function debug($message): void
    {
        $fileName = '/home/bitrix/www/local/log/unisender_debug.log';
        $this->writeToLogFile($fileName, $message);
    }


}

?>