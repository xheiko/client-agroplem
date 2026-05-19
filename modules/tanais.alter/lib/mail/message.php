<?php

namespace Tanais\Alter\Mail;

use Tanais\Alter\Log\Loggers\MailLogger;

class Message
{

    public static function sendMessage($to, $subject, $message, $additionalHeaders)
    {
        if (is_array($to))
            foreach ($to as &$toElement)
                $toElement = idn_to_ascii($toElement);
        if (is_string($to)) {
            $to = iconv_mime_decode($to);
            $to = idn_to_ascii($to);            
        }
        

        $debugFileName = '/home/bitrix/www/local/log/mail_debug/message_' . date('YmdHis') . '.log';
        file_put_contents($debugFileName, var_export([
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'additionalHeader' => $additionalHeader
        ], true));

        $logger = new  \Tanais\Alter\Log\Loggers\MailLogger();
        $headers = array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-KEY' => '6x6yjyeqatsucdohs39hggp3d35k6ara71e8ms7y',
        );

        $headersCustom = self::customParseHeaders($additionalHeaders, $to);
        $headersCustom = self::filterBccEmails($headersCustom);
        $message = self::customParseMessage($message, $headersCustom);
        $files = $message['attachments'] ? self::prepareFileToUnisender($message['attachments']) : [];

        if (count(self::prepareEmails($to, $headersCustom)) > 1) {
            $requestBody = [
                "message" => [
                    "recipients" => self::prepareEmails($to, $headersCustom),
                    "global_language" => "ru",
                    "body" => [
                        "html" => $message['html'],
                    ],
                    "skip_unsubscribe" => 1,
                    "subject" => iconv_mime_decode($subject),
                    "from_name" => "Лаборатория Агроплем",
                    "from_email" => "info@agroplem.ru",
                    "reply_to" => "info@agroplem.ru",
                    "track_links" => 0,
                    "track_read" => 0,
                    "bypass_global" => 0,
                    "bypass_unavailable" => 0,
                    "bypass_unsubscribed" => 0,
                    "bypass_complained" => 0,
                    "headers" => $headersCustom,
                    "attachments" => $files,
                ]
            ];
        } else {
            $requestBody = [
                "message" => [
                    "recipients" => [
                        [
                            "email" => rtrim($to, ','),
                        ]
                    ],
                    "skip_unsubscribe" => 1,
                    "global_language" => "ru",
                    "body" => [
                        "html" => $message['html'],
                    ],
                    "subject" => $subject,
                    "from_name" => "Лаборатория Агроплем",
                    "from_email" => "info@agroplem.ru",
                    "reply_to" => "info@agroplem.ru",
                    "track_links" => 0,
                    "track_read" => 0,
                    "bypass_global" => 0,
                    "bypass_unavailable" => 0,
                    "bypass_unsubscribed" => 0,
                    "bypass_complained" => 0,
                    "headers" => $headersCustom,
                    "attachments" => $files
                ]
            ];
        }

        if (empty($requestBody['message']['body']['html']))
            $requestBody['message']['body']['html'] = 'пустое сообщение';
        if (empty($requestBody['message']['subject']))
            $requestBody['message']['subject'] = 'Сообщение без темы';

        $requestBody['bypass_global'] = 1;
        $result = self::sendUnisenderMessage($headers, $requestBody, $headersCustom);
        file_put_contents($debugFileName, "\r\n---------------\r\n", FILE_APPEND);
        file_put_contents($debugFileName, var_export([
            'headers' => $headers,
            'headersCustom' => $headersCustom,
            'requestBody' => $requestBody,
        ], true), FILE_APPEND);
        file_put_contents($debugFileName, var_export([
            'result' => $result
        ], true), FILE_APPEND);

        return $result;
    }

    private static function parseHeaders($headersString): array
    {
        $headersArray = [];

        $lines = explode("\n", $headersString);

        foreach ($lines as $line) {
            $parts = explode(': ', $line, 2);

            if (count($parts) === 2) {
                $key = $parts[0];
                $value = trim($parts[1]);
                $headersArray[$key] = $value;
            }
        }

        return $headersArray;
    }

    private static function filterBccEmails(array $headersCustom): array
    {
        if (isset($headersCustom['Bcc'])) {
            $bcc_emails = array_map('trim', explode(',', $headersCustom['Bcc']));
            $filtered_bcc_emails = array_filter($bcc_emails, function ($email) {
                return strpos($email, '@agroplem.ru') !== false;
            });
            $headersCustom['Bcc'] = implode(',', $filtered_bcc_emails);

            if (empty($filtered_bcc_emails)) {
                unset($headersCustom['Bcc']);
            }
        }


        return $headersCustom;
    }

    private static function prepareEmails($email_string, $headers = '')
    {
        $email_array = array_filter(explode(',', $email_string));

        $new_email_array = array();

        foreach ($email_array as $email) {
            $email = trim($email, ",");
            $email = trim($email);
            $new_email_array[]['email'] = $email;
        }
        if (isset($headers['cc'])) {
            $headers['Cc'] = $headers['cc'];
            unset($headers['cc']);
        }
        if (isset($headers['CC'])) {
            $headers['Cc'] = $headers['CC'];
            unset($headers['CC']);
        }
        if (isset($headers['bcc'])) {
            $headers['Bcc'] = $headers['bcc'];
            unset($headers['bcc']);
        }
        if (isset($headers['BCc'])) {
            $headers['Bcc'] = $headers['BCc'];
            unset($headers['BCc']);
        }
        if (isset($headers['BCC'])) {
            $headers['Bcc'] = $headers['BCC'];
            unset($headers['BCC']);
        }

        if (isset($headers['Cc'])) {
            $cc_emails = self::prepareEmails($headers['Cc']);
            $new_email_array = array_merge($new_email_array, $cc_emails);
        }

        if (isset($headers['Bcc'])) {
            $bcc_emails = self::prepareEmails($headers['Bcc']);
            $bcc_emails = array_filter($bcc_emails, function ($email) {
                return strpos($email['email'], '@agroplem.ru') !== false;
            });
            $new_email_array = array_merge($new_email_array, $bcc_emails);
        }

        return $new_email_array;
    }

    private static function getImageFromMessage($string)
    {
        $img_pattern = '/<img[^>]*\ssrc="[^"]*\/pub\/mail\/read\.php[^"]*"[^>]*>/';

        if (preg_match($img_pattern, $string, $img_matches)) {
            return $img_matches[0];
        }
    }

    private static function getFilesFormMessage($message)
    {
        preg_match('/mix\d+(.+)/s', array_reverse(explode("mix", $message))[0], $matches);
        if (isset($matches[1])) {
            $extracted_text = $matches[1];
            return $extracted_text;
        }
    }

    private static function sendUnisenderMessage($headers, $requestBody, $headersCustom)
    {
        $logger = new MailLogger();

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://go2.unisender.ru/ru/transactional/api/v1/'
        ]);

        try {
            $response = $client->request(
                'POST',
                'email/send.json',
                array(
                    'headers' => $headers,
                    'json' => $requestBody,
                )
            );
            $logger->logSendedMail([$headersCustom, $requestBody]);


            return true;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $message = [
                'error' => $e->getMessage(),
                'headers' => $headersCustom,
                'requestBody' => $requestBody
            ];
            $logger->log($message);

            return false;
        }
    }

    private static function customParseMessage(string $data, array $headers): array
    {
        $separator = "--" . $headers["boundary"];
        $parts = explode($separator, $data);
        $parsedMessage = [];

        switch ($headers["Content-Type"]) {
            case "multipart/mixed":
                $parsedMessage['attachments'] = self::CustomMailSendgrid_MessageAttachments($parts);
                $subHeaders = explode(PHP_EOL, $parts[1]);
                $subHeaders = self::CustomMailSendgrid_Headers($subHeaders[1]);
                $newSeparator = "--" . $subHeaders["boundary"];
                $subParts = explode($newSeparator, $parts[1]);
                $parsedMessage['html'] =  self::CustomMailSendgrid_MessageHtml($subParts[2]);
                break;
            case "multipart/alternative":
                $parsedMessage['html'] =  self::CustomMailSendgrid_MessageHtml($parts[2]);
                break;
            default:
                $parsedMessage['html'] =  $data;
                break;
        }

        return $parsedMessage;
    }

    private static function customParseHeaders($data, $to): array
    {
        $parsedHeaders = [];
        $data = explode(PHP_EOL, $data);
        $data = array_filter($data, fn($value) => !is_null($value) && $value !== ''); //Удаляем пустые значения

        foreach ($data as $line) {
            $line = explode(";", trim($line));
            if ($line) {
                foreach ($line as $header) {
                    if (strpos($header, '="') !== false) {
                        $headerParts = explode('="', $header);
                        $headerKey = trim($headerParts[0]);
                        $headerValue = substr($headerParts[1], 0, -1);
                    } else {
                        $headerParts = explode(": ", $header);
                        $headerKey = $headerParts[0];
                        $headerValue = $headerParts[1];
                    }

                    $parsedHeaders[$headerKey] = $headerValue;
                }
            }
        }

        if (strpos($parsedHeaders['From'], " <") !== false) {
            $fromParts = explode(" <", $parsedHeaders['From']);
            $fromNameParts = explode("?=", substr($fromParts[0], 10));
            $parsedHeaders['From-Name'] = base64_decode($fromNameParts[0]);
            $parsedHeaders['From-Email'] = substr($fromParts[1], 0, -1);
        }

        foreach ($parsedHeaders as $key => $value) {
            if ($key)
                $parsedHeaders[$key] = trim($value);
        }
        $parsedHeaders['To'] = rtrim($to, ',');
        return $parsedHeaders;
    }

    private static function prepareFileToUnisender($files): array
    {
        $logger = new MailLogger();
        $filesNames = [];
        $count = [];

        foreach ($files as $file) {

            $fileName = self::decodeFileName($file['name']);

            if (in_array($fileName, $filesNames)) {
                $count[$fileName]++;
                $fileInfo = pathinfo($fileName);
                $filesNames[] = $fileName;
                $fileName = $fileInfo['filename'] . '(' . $count[$fileName] . ').' . $fileInfo['extension'];
            } else {
                $filesNames[] = $fileName;
            }

            $attachments[] = [
                'type' => $file['Content-Type'],
                'name' => $fileName,
                'content' => $file['file'],
            ];
        }

        return $attachments;
    }

    private static function decodeFileName($text): false|string
    {
        $startPos = strpos($text, '=?UTF-8?B?') + strlen('=?UTF-8?B?');
        $encodedString = substr($text, $startPos);

        $decodedText = base64_decode($encodedString);

        return $decodedText;
    }

    static function CustomMailSendgrid_Headers($data)
    {
        $array = [];
        $data = explode(PHP_EOL, $data);
        // file_put_contents("/home/bitrix/www/local/log/custom_mail.debug.90.data", var_export($data,true));
        // file_put_contents("/home/bitrix/www/local/log/custom_mail.debug.91.lines", '');
        // file_put_contents("/home/bitrix/www/local/log/custom_mail.debug.92.linesV", '');
        foreach ($data as $key => $line) {
            $line = explode(";", trim($line));
            // file_put_contents("/home/bitrix/www/local/log/custom_mail.debug.91.lines", var_export($line,true),FILE_APPEND);
            foreach ($line as $k => $v) {
                if (strpos($v, '="') != false) {
                    $v = explode('="', $v);
                    $t = trim($v[0]);
                    $v[0] = $t;
                    $t = substr($v[1], 0, -1);
                    $v[1] = $t;
                } else {
                    $v = explode(": ", $v);
                }

                // file_put_contents("/home/bitrix/www/local/log/custom_mail.debug.92.linesV", var_export(['v0'=>$v[0],'v1'=>$v[1]],true),FILE_APPEND);
                $array[$v[0]] = $v[1];
            }
        }
        if (strpos($array['From'], " <") != false) {
            $from = explode(" <", $array['From']);
            $fromName = explode("?=", substr($from[0], 10));
            $array['From-Name'] = base64_decode($fromName[0]);
            $array['From-Email'] = substr($from[1], 0, -1);
        }
        foreach ($array as $key => $value) {
            $array[$key] = trim($array[$key]);
        }

        return $array;
    }

    #-----------------------------------------------------------------------------------------
    static function CustomMailSendgrid_MessageHtml($data)
    {
        $array = explode(PHP_EOL, $data);
        unset($array[0]);
        unset($array[1]);
        unset($array[2]);
        $arr = implode("\n", $array);
        return $arr;
    }

    #-----------------------------------------------------------------------------------------
    static function CustomMailSendgrid_MessageAttachments($data)
    {
        $attachments = [];
        foreach ($data as $kkk => $att) {
            if (strpos($att, "Content-Disposition: attachment;") != false) {
                $att = trim($att);
                $att = explode(PHP_EOL, $att);
                foreach ($att as $kk => $param) {
                    if (empty($param)) {
                        $mode = 'f';
                        continue;
                    } else {
                        if ($mode != 'f') {
                            $mode = 'p';
                        }
                    };
                    switch ($mode) {
                        case 'p':
                            $param = explode(";", $param);
                            foreach ($param as $k => $par) {
                                if (strpos($par, "=\"") != false) {
                                    $par = explode("=\"", $par);
                                    $t = trim($par[0]);
                                    $par[0] = $t;
                                    $t = substr($par[1], 0, -1);
                                    $par[1] = $t;
                                }
                                // if ((strpos($par, ':') != false)) {
                                if (is_string($par) && (strpos($par, ':') != false)) {
                                    $par = explode(": ", $par);
                                }
                                $atta[$par[0]] = $par[1];
                            }
                            break;
                        case 'f':
                            $f .= $param;
                            break;
                    }
                }
                $atta['file'] = $f;
                array_push($attachments, $atta);
                unset($atta);
                unset($f);
                unset($mode);
            }
        }
        return $attachments;
    }

    #-----------------------------------------------------------------------------------------
    static function CustomMailSendgrid_Message($data, $headers)
    {
        $separator = "--" . $headers["boundary"];
        $array = explode($separator, $data);

        switch ($headers["Content-Type"]) {
            case "multipart/mixed":
                $arr['attachments'] = self::CustomMailSendgrid_MessageAttachments($array);
                $headers = explode(PHP_EOL, $array[1]);
                $headers = self::CustomMailSendgrid_Headers($headers[1]);
                $separator = "--" . $headers["boundary"];
                $array = explode($separator, $array[1]);
                $arr['html'] = self::CustomMailSendgrid_MessageHtml($array[2]);
                break;
            case "multipart/alternative":
                $arr['html'] = self::CustomMailSendgrid_MessageHtml($array[2]);
                break;
            default:
                break;
        }
        return $arr;
    }
}
