<?php

namespace Tanais\Alter\Crm;

use \Bitrix\Crm\Service;
use DocxMerge\DocxMerge;
use DocxMerge\libraries\TbsZip;
use PhpOffice\PhpWord\PhpWord;

class DocumentGenerator
{
    const TEMPLATE_AO = 20;
    const TEMPLATE_AO_DOC = 21;
    const TEMPLATE_AO_APP = 22;
    const TEMPLATE_OOO = 23;
    const TEMPLATE_OOO_DOC = 24;
    const TEMPLATE_OOO_APP = 26;
    const TEMPLATE_OOO_FZ_DOC = 25;

    public static function CombainedContractCreate(\Bitrix\Main\Event $event)
    {
        \Bitrix\Main\Loader::includeModule('documentgenerator');
        $document = $event->getParameter('document');
        $template = $document->getTemplate();
        $resultMainDocument = $document->getFile();
        $idMainDocument = $resultMainDocument->getData()["id"];


        if ($template->ID == self::TEMPLATE_AO) {
            $provider = $document->getProvider();
            $elementId = $provider->getSource();
            $container = Service\Container::getInstance();
            $factory = $container->getFactory(1050);
            $element = $factory->getItem($elementId);
            if ($element) {
                $smartProcessElements = $element->getCompatibleData();
            }

            $docTitle = str_replace(["\\", "/", "=", "&", "$", "%", "@", "*", "<", ">"], '', $smartProcessElements['TITLE']);

            if ($smartProcessElements['UF_CRM_6_DOCUMENT_TYPE'] == 51) {
                $paragraphs = [
                    'paragraph3.3' => ['VALUE' => 'Заказчик вносит 100% предоплату, согласно поступившему счету.'],
                    'paragraph1.6' => ['VALUE' => 'Срок выполнения исследования начинает течь с момента поступления материала Заказчика в лабораторию Исполнителя и получения предоплаты согласно п. 3.3. настоящего Договора.'],
                    'paragraph5.3' => ['VALUE' => '0,1'],
                ];
            }
            if ($smartProcessElements['UF_CRM_6_DOCUMENT_TYPE'] == 52) {
                $paragraphs = [
                    'paragraph3.3' => ['VALUE' => 'Оплата стоимости лабораторных услуг производится согласно поступившему счету в течение ____ (______________) _______ дней с даты выставления УПД/счета.'],
                    'paragraph1.6' => ['VALUE' => 'Срок выполнения исследования начинает течь с момента поступления материала Заказчика в лабораторию Исполнителя.'],
                    'paragraph5.3' => ['VALUE' => '0,2'],
                ];
            }

            $arContractPrice = [];
            foreach ($smartProcessElements['PRODUCT_ROWS'] as $priceListProduct) {
                $productId = \CCatalogSku::GetProductInfo($priceListProduct['PRODUCT_ID']);
                $arContractPrice[$priceListProduct['PRODUCT_ID']] = $priceListProduct['PRICE'];
            }

            $arPrice = self::getPriceProducts();
            $arMilkProducts = self::getMilkProducts('N');
            $arGenProducts = self::getGenProductsNoCategory('N');

            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $phpWord->setDefaultParagraphStyle(
                array(
                    'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(0), // Отступ после абзаца
                    'lineHeight' => 1.0, // Межстрочный интервал
                )
            );

            $templateDocText = \Bitrix\DocumentGenerator\Template::loadById(self::TEMPLATE_AO_DOC);
            $templateDocText->setSourceType(\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Dynamic1050::class);
            $documentDocText = \Bitrix\DocumentGenerator\Document::createByTemplate($templateDocText, $elementId);
            $documentDocText->setFields($paragraphs);
            $resultDocText = $documentDocText->getFile();
            if ($resultDocText->isSuccess()) {
                $fileId = $resultDocText->getData()["emailDiskFile"];
                $documentTextDocId = $resultDocText->getData()["id"];
                $fileDocText = \Bitrix\Disk\File::getById($fileId);
                $filePathDocText = \CFile::GetPath($fileDocText->getFileId());
                //$docText = \PhpOffice\PhpWord\IOFactory::load('/home/bitrix/www' . $filePathDocText);
            }
            $templateDocApp = \Bitrix\DocumentGenerator\Template::loadById(self::TEMPLATE_AO_APP);
            $templateDocApp->setSourceType(\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Dynamic1050::class);
            $documentDocApp = \Bitrix\DocumentGenerator\Document::createByTemplate($templateDocApp, $elementId);
            $resultDocApp = $documentDocApp->getFile();
            if ($resultDocApp->isSuccess()) {
                $fileIdApp = $resultDocApp->getData()["emailDiskFile"];
                $documentAppDocId = $resultDocApp->getData()["id"];
                $fileApp = \Bitrix\Disk\File::getById($fileIdApp);
                $filePathDoxApp = \CFile::GetPath($fileApp->getFileId());
            }

            if (in_array(801, $smartProcessElements['UF_CRM_6_LABORATORIES']) || in_array(800, $smartProcessElements['UF_CRM_6_LABORATORIES']) ||
                in_array(811, $smartProcessElements['UF_CRM_6_LABORATORIES'])) {

                $section = $phpWord->addSection([
                    'breakType' => 'nextPage',
                    'marginTop' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1, 25),
                    'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1, 25),
                    'marginLeft' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1, 25),
                    'marginRight' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1, 25),
                ]);
                //  $phpWord->addTitleStyle(3, ['size' => 11, 'bold' => true, 'name' => 'Times New Roman'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
                $section->addText('Приложение № 1', ['size' => 11, 'bold' => true, 'name' => 'Times New Roman'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
                // $phpWord->addTitleStyle(4, ['size' => 11, 'bold' => true, 'name' => 'Times New Roman'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                $styleTable = [
                    'borderSize' => 6,
                    'cellMargin' => 80,
                    'name' => 'Times New Roman',
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
                ];

                $cellStyleH = [
                    'valign' => 'center',
                    'name' => 'Times New Roman',
                    'size' => 10,
                ];

                if ($smartProcessElements['UF_CRM_6_CONTRACT_PRODUCT'] == 201) {

                    $section->addTextBreak(1);
                    $section->addText('Перечень лабораторных услуг', ['size' => 11, 'bold' => true, 'name' => 'Times New Roman'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $section->addTextBreak(1);

                    $phpWord->addTableStyle('Product Table Milk', $styleTable);
                    $tableProducts = $section->addTable('Product Table Milk');

                    $tableProducts->addRow();
                    $tableProducts->addCell(700, $cellStyleH)->addText('№ п/п', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                    $tableProducts->addCell(2000, $cellStyleH)->addText('Артикул лабораторных услуг', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                    $tableProducts->addCell(8000, $cellStyleH)->addText('Наименование лабораторных услуг', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                    $tableProducts->addCell(2000, $cellStyleH)->addText('Срок выполнения исследова-ния, раб. дней', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                    $tableProducts->addCell(2000, $cellStyleH)->addText('Цена за одно исследова-ние, руб.', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                    //                  $tableProducts->addCell(2000, $cellStyleH)->addText('Срок выполнения исследова-ния, раб. дней', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);

                    $countProducts = 1;

                    $arProducts = $arGenProducts + $arMilkProducts;

                    foreach ($arContractPrice as $productId => $productPrice) {


                        $originalProductId = \CCatalogSku::GetProductInfo($productId);
                        if ($originalProductId['ID']) {
                            $productId = $originalProductId['ID'];
                        }

                        $days = $arProducts[$productId]['PROPERTY_86_VALUE'] ? $arProducts[$productId]['PROPERTY_86_VALUE'] : '-';
                        if (!$productPrice || $productPrice == '0.00 ' && !$arPrice[$productId] || $arPrice[$productId] == '0.00 ') {
                            $price = 'По запросу';
                        } else {
                            $price = $productPrice ? $productPrice : $arPrice[$productId];
                        }
                        $previewText = safeDocxText($arProducts[$productId]['PREVIEW_TEXT']);

                        $tableProducts->addRow();
                        $tableProducts->addCell(700, $cellStyleH)->addText($countProducts, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        $tableProducts->addCell(2000, $cellStyleH)->addText($arProducts[$productId]['PROPERTY_69_VALUE'], ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        $tableProducts->addCell(8000, $cellStyleH)->addText($previewText, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'left']);
                        $tableProducts->addCell(2000, $cellStyleH)->addText($days, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        $tableProducts->addCell(2000, $cellStyleH)->addText($price, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);

                        $countProducts++;
                    }
                }
                if ($smartProcessElements['UF_CRM_6_CONTRACT_PRODUCT'] == 200) {

                    if (in_array(800, $smartProcessElements['UF_CRM_6_LABORATORIES']) ||
                        in_array(811, $smartProcessElements['UF_CRM_6_LABORATORIES'])) {


                        $section->addTextBreak(1);
                        $section->addText('Перечень лабораторных услуг молока', ['size' => 11, 'bold' => true, 'name' => 'Times New Roman'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                        $section->addTextBreak(1);

                        $phpWord->addTableStyle('Product Table Milk', $styleTable);
                        $tableMilk = $section->addTable('Product Table Milk');

                        $tableMilk->addRow();
                        $tableMilk->addCell(700, $cellStyleH)->addText('№ п/п', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        $tableMilk->addCell(2000, $cellStyleH)->addText('Артикул лабораторных услуг', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        $tableMilk->addCell(8000, $cellStyleH)->addText('Наименование лабораторных услуг', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        $tableMilk->addCell(2000, $cellStyleH)->addText('Срок выполнения исследова-ния, раб. дней', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        $tableMilk->addCell(2000, $cellStyleH)->addText('Цена за одно исследова-ние, руб.', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        //    $tableMilk->addCell(2000, $cellStyleH)->addText('Срок выполнения исследова-ния, раб. дней', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);

                        $countProducts = 1;

                        foreach ($arMilkProducts as $milkProduct) {

                            //   $days = $milkProduct['PROPERTY_81_VALUE'] ? $milkProduct['PROPERTY_81_VALUE'] : '-';
                            if (!$arContractPrice[$milkProduct['ID']] || $arContractPrice[$milkProduct['ID']] == '0.00 ' && !$arPrice[$milkProduct['ID']] || $arPrice[$milkProduct['ID']] == '0.00 ') {
                                $price = 'По запросу';
                            } else {
                                $price = $arContractPrice[$milkProduct['ID']] ? $arContractPrice[$milkProduct['ID']] : $arPrice[$milkProduct['ID']];
                            }
                            $previewText = safeDocxText($milkProduct['PREVIEW_TEXT']);

                            $tableMilk->addRow();
                            $tableMilk->addCell(700, $cellStyleH)->addText($countProducts, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                            $tableMilk->addCell(2000, $cellStyleH)->addText($milkProduct['PROPERTY_69_VALUE'], ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                            $tableMilk->addCell(8000, $cellStyleH)->addText($previewText, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'left']);
                            $tableMilk->addCell(2000, $cellStyleH)->addText($days, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                            $tableMilk->addCell(2000, $cellStyleH)->addText($price, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);


                            $countProducts++;
                        }
                    }


                    if (in_array(801, $smartProcessElements['UF_CRM_6_LABORATORIES'])) {

                        $products = [];
                        $section->addTextBreak(1);
                        $section->addText('Перечень лабораторных услуг генетики', ['size' => 11, 'bold' => true, 'name' => 'Times New Roman'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                        $section->addTextBreak(1);

                        $phpWord->addTableStyle('Product Table Gen', $styleTable);
                        $tableGen = $section->addTable('Product Table Gen');

                        $tableGen->addRow();
                        $tableGen->addCell(700, $cellStyleH)->addText('№ п/п', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        $tableGen->addCell(2000, $cellStyleH)->addText('Артикул лабораторных услуг', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        $tableGen->addCell(8000, $cellStyleH)->addText('Наименование лабораторных услуг', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        $tableGen->addCell(2000, $cellStyleH)->addText('Срок выполнения исследова-ния, раб. дней', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        $tableGen->addCell(2000, $cellStyleH)->addText('Цена за одно исследова-ние, руб.', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                        //  $tableGen->addCell(2000, $cellStyleH)->addText('Срок выполнения исследова-ния, раб. дней', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);

                        $countProducts = 1;

                        foreach ($arGenProducts as $genProduct) {

                            //   $days = $genProduct['PROPERTY_81_VALUE'] ? $genProduct['PROPERTY_81_VALUE'] : '-';
                            if (!$arContractPrice[$genProduct['ID']] || $arContractPrice[$genProduct['ID']] == '0.00 ' && !$arPrice[$genProduct['ID']] || $arPrice[$genProduct['ID']] == '0.00 ') {
                                $price = 'По запросу';
                            } else {
                                $price = $arContractPrice[$genProduct['ID']] ? $arContractPrice[$genProduct['ID']] : $arPrice[$genProduct['ID']];
                            }
                            $previewText = safeDocxText($genProduct['PREVIEW_TEXT']);

                            $tableGen->addRow();
                            $tableGen->addCell(700, $cellStyleH)->addText($countProducts, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                            $tableGen->addCell(2000, $cellStyleH)->addText($genProduct['PROPERTY_69_VALUE'], ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                            $tableGen->addCell(8000, $cellStyleH)->addText($previewText, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'left']);
                            $tableGen->addCell(2000, $cellStyleH)->addText($days, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                            $tableGen->addCell(2000, $cellStyleH)->addText($price, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                            //   $tableGen->addCell(2000, $cellStyleH)->addText($days, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);

                            $countProducts++;
                        }

                    }
                }

                $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $objWriter->save('/home/bitrix/www/upload/tanais.document/Приложение1_' . $docTitle . '.docx');
            }
            $arAppProp = \CFile::makeFileArray('/home/bitrix/www/upload/tanais.document/Приложение1_' . $docTitle . '.docx');
            $arAppProp['del'] = 'Y';
            $arAppProp['MODULE_ID'] = 'tanais.document';
            $fileDocId = \CFile::SaveFile($arAppProp, 'tanais.document');
            $container = \Bitrix\Crm\Service\Container::getInstance()->getFileUploader();
            $collectionFields = $factory->getFieldsCollection();
            //$customApp1Field = $collectionFields->getField('UF_CRM_14_APPLICATION_1');
            //$customApp2Field = $collectionFields->getField('UF_CRM_14_APPLICATION_2');
            // $customDocField = $collectionFields->getField('UF_CRM_14_CONTRACT');
            //$customDoc = $collectionFields->getField('UF_CRM_14_CONTRACT_FULL');
            // $customApp1FieldId = $container->saveFileTemporary($customApp1Field, \CFile::makeFileArray(\CFile::GetPath($fileDocId)));
            //$customApp2FieldId = $container->saveFileTemporary($customApp2Field, \CFile::makeFileArray('/home/bitrix/www' . $filePathDoxApp));
            //$customDocFieldId = $container->saveFileTemporary($customDocField, \CFile::makeFileArray('/home/bitrix/www' . $filePathDocText));

            $createFilePath = "/home/bitrix/www/upload/tanais.document/" . $docTitle . ".docx";
            $dm = new DocxMerge();
            $dm->merge([
                '/home/bitrix/www' . $filePathDocText,
                '/home/bitrix/www/upload/tanais.document/Приложение1_' . $docTitle . '.docx',
                '/home/bitrix/www' . $filePathDoxApp,
            ], $createFilePath);

            $arDocProp = \CFile::makeFileArray($createFilePath);
            $arDocProp['del'] = 'Y';
            $arDocProp['MODULE_ID'] = 'tanais.document';
            $fileDocFullId = \CFile::SaveFile($arDocProp, 'tanais.document');
            //$customDocId = $container->saveFileTemporary($customDoc, \CFile::makeFileArray(\CFile::GetPath($fileDocFullId)));


            if (!empty($documentAppDocId)) {
                $arAppTimeline = \Bitrix\Crm\Timeline\DocumentEntry::getListByDocumentId($documentAppDocId);
                $appTimelineId = $arAppTimeline[0]['ID'];
                \Bitrix\Crm\Timeline\TimelineEntry::delete($appTimelineId);
            }
            if (!empty($documentTextDocId)) {
                $arDocTimeline = \Bitrix\Crm\Timeline\DocumentEntry::getListByDocumentId($documentTextDocId);
                $docTimelineId = $arDocTimeline[0]['ID'];
                \Bitrix\Crm\Timeline\TimelineEntry::delete($docTimelineId);
            }

            if (!empty($idMainDocument)) {
                $arFilter = ["ID" => $idMainDocument];
                $dbDate = \Bitrix\DocumentGenerator\Model\DocumentTable::getList(array(
                    "select" => array("*"),
                    "filter" => $arFilter,
                ));
                if ($row = $dbDate->fetch()) {
                    $generatorFileId = $row['FILE_ID'];
                }
            }

            if (!empty($generatorFileId)) {
                $arFilter = ["ID" => $generatorFileId];
                $dbDate = \Bitrix\DocumentGenerator\Model\FileTable::getList(array(
                    "select" => array("*"),
                    "filter" => $arFilter,
                ));
                if ($row = $dbDate->fetch()) {
                    $diskObjectId = $row['STORAGE_WHERE'];
                }
            }

            if (!empty($diskObjectId)) {
                $arFilter = ["ID" => $diskObjectId];
                $dbDate = \Bitrix\Disk\Internals\ObjectTable::getList(array(
                    "select" => array("*"),
                    "filter" => $arFilter,
                ));
                if ($row = $dbDate->fetch()) {
                    if (!empty($row['ID'])) {
                        $test = $row['FILE_ID'];
                        // \CFile::Delete($row['FILE_ID']);
                        $dbDate = \Bitrix\Disk\Internals\ObjectTable::update($diskObjectId, ['FILE_ID' => $fileDocFullId]);
                    }
                }
            }

            // $element->set('UF_CRM_14_CONTRACT', $customDocFieldId);
            // $element->set('UF_CRM_14_APPLICATION_1', $customApp1FieldId);
            // $element->set('UF_CRM_14_APPLICATION_2', $customApp2FieldId);
            // $element->set('UF_CRM_14_CONTRACT_FULL', $customDocId);
            // $element->set('UF_CRM_14_DEBUG', 'ИЗ ГЕНЕРАТОРА: ' . $idMainDocument . ' Объединенный файл: ' . $fileDocFullId . ' на удаление ' . $test);
            // $operation = $factory->getUpdateOperation($element);
            // $operation->disableAllChecks();
            //  $operation->disableBizProc();
            // $operationResult = $operation->launch();
        }

        if ($template->ID == self::TEMPLATE_OOO) {
            $provider = $document->getProvider();
            $elementId = $provider->getSource();
            $container = Service\Container::getInstance();
            $factory = $container->getFactory(1050);
            $element = $factory->getItem($elementId);
            if ($element) {
                $smartProcessElements = $element->getCompatibleData();
            }

            $arContractPrice = [];
            foreach ($smartProcessElements['PRODUCT_ROWS'] as $priceListProduct) {
                $arContractPrice[$priceListProduct['PRODUCT_ID']] = $priceListProduct['PRICE'];
            }

            $docTitle = str_replace(["\\", "/", "=", "&", "$", "%", "@", "*", "<", ">"], '', $smartProcessElements['TITLE']);

            $fields = [];
            if ($smartProcessElements['UF_CRM_6_DOCUMENT_TYPE'] == 51) {
                $fields = [
                    'paragraph3.2' => ['VALUE' => 'Заказчик оплачивает стоимость услуг авансом на основании счета Исполнителя в течение 3 (трех) рабочих дней с момента получения Счета на оплату.'],
                ];
            }
            if ($smartProcessElements['UF_CRM_6_DOCUMENT_TYPE'] == 52) {
                $fields = [
                    'paragraph3.2' => ['VALUE' => 'Заказчик оплачивает стоимость услуг на основании счета Исполнителя в течение ______ (____________) рабочих дней с даты выставления УПД и Счета на оплату.'],
                ];
            }
            if ($smartProcessElements['UF_CRM_6_SIGNATORY_US'] == 50) {
                $fields = [
                    'SignatoryUs' => ['VALUE' => 'руководителя лаборатории анализа кормов и сельскохозяйственной продукции Маракулина Станислава Игоревича, действующего на основании доверенности № 9 от 21.10.2023 года'],
                    'Signatory' => ['VALUE' => 'Маракулин С. И.'],
                    'Post' => ['VALUE' => 'Руководитель лаборатории'],
                    'Basis' => ['VALUE' => 'действует на основании доверенности № 9 от 21.10.2023 г.'],
                ];
                $fields2 = [
                    'SignatoryUs' => ['VALUE' => 'руководителя лаборатории анализа кормов и сельскохозяйственной продукции Маракулина Станислава Игоревича, действующего на основании доверенности № 9 от 21.10.2023 года'],
                    'Signatory' => ['VALUE' => 'Маракулин С. И.'],
                    'Post' => ['VALUE' => 'Руководитель лаборатории'],
                    'Basis' => ['VALUE' => 'действует на основании доверенности № 9 от 21.10.2023 г.'],
                ];
            }
            if ($smartProcessElements['UF_CRM_6_SIGNATORY_US'] == 49) {
                $fields = [
                    'SignatoryUs' => ['VALUE' => 'руководителя лабораторно-испытательного центра Натансона Павла Константиновича, действующего на основании доверенности № 03 от 04.03.2024 года'],
                    'Signatory' => ['VALUE' => 'Натансон П. К.'],
                    'Post' => ['VALUE' => 'Руководитель лабораторно-испытательного центра'],
                    'Basis' => ['VALUE' => 'действует на основании доверенности № 03 от 04.03.2024 г.'],
                ];
                $fields2 = [
                    'SignatoryUs' => ['VALUE' => 'руководителя лабораторно-испытательного центра Натансона Павла Константиновича, действующего на основании доверенности № 03 от 04.03.2024 года'],
                    'Signatory' => ['VALUE' => 'Натансон П. К.'],
                    'Post' => ['VALUE' => 'Руководитель лабораторно-испытательного центра'],
                    'Basis' => ['VALUE' => 'действует на основании доверенности № 03 от 04.03.2024 г.'],
                ];
            }
            if ($smartProcessElements['UF_CRM_6_SIGNATORY_US'] == 48) {
                $fields = [
                    'SignatoryUs' => ['VALUE' => 'генерального директора Павленко Романа Васильевича, действующего на основании Устава'],
                    'Signatory' => ['VALUE' => 'Павленко Р. В.'],
                    'Post' => ['VALUE' => 'Генеральный директор'],
                ];
                $fields2 = [
                    'SignatoryUs' => ['VALUE' => 'генерального директора Павленко Романа Васильевича, действующего на основании Устава'],
                    'Signatory' => ['VALUE' => 'Павленко Р. В.'],
                    'Post' => ['VALUE' => 'Генеральный директор'],
                ];
            }
            if ($smartProcessElements['UF_CRM_6_SIGNATORY_US'] == 638) {
                $fields = [
                    'SignatoryUs' => ['VALUE' => 'руководителя лаборатории анализа почв и оценки почвенного плодородия Свешникова Вячеслава Евгеньевича, действующего на основании доверенности № 9 от 21.10.2023 года'],
                    'Signatory' => ['VALUE' => 'Свешников В. Е.'],
                    'Post' => ['VALUE' => 'Руководитель лаборатории'],
                    'Basis' => ['VALUE' => 'действует на основании доверенности №6 от 23.09.2025 г.'],
                ];
                $fields2 = [
                    'SignatoryUs' => ['VALUE' => 'руководителя лаборатории анализа почв и оценки почвенного плодородия Свешникова Вячеслава Евгеньевича, действующего на основании доверенности № 9 от 21.10.2023 года'],
                    'Signatory' => ['VALUE' => 'Свешников В. Е.'],
                    'Post' => ['VALUE' => 'Руководитель лаборатории'],
                    'Basis' => ['VALUE' => 'действует на основании доверенности №6 от 23.09.2025 г.'],
                ];
            }

            $arPrice = self::getPriceProducts();
            $arAllProducts = self::getAllProducts();
            $arPackages = self::getPackages();
            $arProductsFeed = self::getProductsFeed();
            $arProductsWed = self::getProductsWed();
            $arProductsSoil = self::getProductsSoil();
            $arProductsMbSoil = self::getProductsMbSoil();

            if ($smartProcessElements['UF_CRM_6_CONTRACT_WITH'] == 56) {
                $templateDocText = \Bitrix\DocumentGenerator\Template::loadById(self::TEMPLATE_OOO_FZ_DOC);
            }
            if ($smartProcessElements['UF_CRM_6_CONTRACT_WITH'] == 55) {
                $templateDocText = \Bitrix\DocumentGenerator\Template::loadById(self::TEMPLATE_OOO_DOC);
            }
            $templateDocText->setSourceType(\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Dynamic1050::class);
            $documentDocText = \Bitrix\DocumentGenerator\Document::createByTemplate($templateDocText, $elementId);
            $documentDocText->setFields($fields);
            $resultDocText = $documentDocText->getFile();
            if ($resultDocText->isSuccess()) {
                $fileId = $resultDocText->getData()["emailDiskFile"];
                $documentTextDocId = $resultDocText->getData()["id"];
                $fileDocText = \Bitrix\Disk\File::getById($fileId);
                $filePathDocText = \CFile::GetPath($fileDocText->getFileId());
            }

            $templateDocApp = \Bitrix\DocumentGenerator\Template::loadById(self::TEMPLATE_OOO_APP);
            $templateDocApp->setSourceType(\Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Dynamic1050::class);
            $documentDocApp = \Bitrix\DocumentGenerator\Document::createByTemplate($templateDocApp, $elementId);
            $documentDocApp->setFields($fields);
            $resultDocApp = $documentDocApp->getFile();
            if ($resultDocApp->isSuccess()) {
                $fileIdApp = $resultDocApp->getData()["emailDiskFile"];
                $documentAppDocId = $resultDocApp->getData()["id"];
                $fileApp = \Bitrix\Disk\File::getById($fileIdApp);
                $filePathDoxApp = \CFile::GetPath($fileApp->getFileId());
            }

            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $phpWord->setDefaultParagraphStyle(
                array(
                    'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(0), // Отступ после абзаца
                    'lineHeight' => 1.0, // Межстрочный интервал
                )
            );
            $section = $phpWord->addSection([
                'breakType' => 'nextPage',
                'marginTop' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1, 25),
                'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1, 25),
                'marginLeft' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1, 25),
                'marginRight' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1, 25),
            ]);
            //  $phpWord->addTitleStyle(3, ['size' => 11, 'bold' => true, 'name' => 'Times New Roman'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            //$section->addText('Приложение № 1', ['size' => 11, 'bold' => true, 'name' => 'Times New Roman'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            // $phpWord->addTitleStyle(4, ['size' => 11, 'bold' => true, 'name' => 'Times New Roman'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $styleTable = [
                'borderSize' => 6,
                'cellMargin' => 80,
                'name' => 'Times New Roman',
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
            ];

            $cellStyleH = [
                'valign' => 'center',
                'name' => 'Times New Roman',
                'size' => 10,
            ];

            if ($smartProcessElements['UF_CRM_6_CONTRACT_PRODUCT'] == 201) {
                $section->addTextBreak(1);
                $section->addText('Перечень лабораторных услуг', ['size' => 11, 'bold' => true, 'name' => 'Times New Roman'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                $section->addTextBreak(1);

                $phpWord->addTableStyle('Product Table product', $styleTable);
                $tableProducts = $section->addTable('Product Table product');

                $tableProducts->addRow();
                $tableProducts->addCell(700, $cellStyleH)->addText('№ п/п', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                $tableProducts->addCell(2000, $cellStyleH)->addText('Артикул лабораторных услуг', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                $tableProducts->addCell(8000, $cellStyleH)->addText('Наименование лабораторных услуг', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                $tableProducts->addCell(2000, $cellStyleH)->addText('Цена за одно исследова-ние, руб.', ['bold' => true, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);

                $countProducts = 1;


                foreach ($arContractPrice as $productId => $productPrice) {

                    $originalProductId = \CCatalogSku::GetProductInfo($productId);
                    if ($originalProductId['ID']) {
                        $productId = $originalProductId['ID'];
                    }

                    // $days = $arAllProducts[$productId]['PROPERTY_83_VALUE'] ? $arAllProducts[$productId]['PROPERTY_83_VALUE'] : '-';
                    if (!$productPrice || $productPrice == '0.00 ' && !$arPrice[$productId] || $arPrice[$productId] == '0.00 ') {
                        $price = 'По запросу';
                    } else {
                        $price = $productPrice ? $productPrice : $arPrice[$productId];
                    }

                    $tableProducts->addRow();
                    $tableProducts->addCell(700, $cellStyleH)->addText($countProducts, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                    $tableProducts->addCell(2000, $cellStyleH)->addText($arAllProducts[$productId]['PROPERTY_69_VALUE'], ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                    $tableProducts->addCell(8000, $cellStyleH)->addText($arAllProducts[$productId]['NAME'], ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'left']);
                    $tableProducts->addCell(2000, $cellStyleH)->addText($price, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);
                    //   $tableProducts->addCell(2000, $cellStyleH)->addText($days, ['bold' => false, 'name' => 'Times New Roman', 'size' => 11], ['align' => 'center']);

                    $countProducts++;
                }
            }
            if ($smartProcessElements['UF_CRM_6_CONTRACT_PRODUCT'] == 200) {
                $styleTable = [
                    'borderSize' => 6,
                    'cellMargin' => 80,
                    'name' => 'Inter',
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, // Выравнивание таблицы по центру
                ];

                $cellStyleH = [
                    'valign' => 'center',
                    'align' => 'center',
                    'name' => 'Inter',
                    'size' => 10,
                    'bgColor' => 'FF7D7D',
                ];

                $cellStyleHFeed = [
                    'valign' => 'center',
                    'align' => 'center',
                    'name' => 'Inter',
                    'size' => 10,
                    'bgColor' => 'C5E0B3',
                ];

                $cellStyleHWed = [
                    'valign' => 'center',
                    'align' => 'center',
                    'name' => 'Inter',
                    'size' => 10,
                    'bgColor' => 'FFF2CC',
                ];

                $cellStyleHMicrobio = [
                    'valign' => 'center',
                    'align' => 'center',
                    'name' => 'Inter',
                    'size' => 10,
                    'bgColor' => 'DEBDFF',
                ];

                $cellStyleHMBSoil = [
                    'valign' => 'center',
                    'align' => 'center',
                    'name' => 'Inter',
                    'size' => 10,
                    'bgColor' => 'FF66CC',
                ];

                $cellStyle = [
                    'valign' => 'center',
                    'align' => 'center',
                    'name' => 'Inter',
                    'size' => 10,
                ];

                $cellStyleFeed = [
                    'align' => 'center',
                    'name' => 'Inter',
                    'size' => 10,
                ];

                $cellStyleGray = [
                    'valign' => 'center',
                    'align' => 'center',
                    'name' => 'Inter',
                    'size' => 10,
                    'bgColor' => 'E7E6E6',
                    'gridSpan' => 4
                ];

                $cellStyleRestart = [
                    'valign' => 'center',
                    'align' => 'center',
                    'name' => 'Inter',
                    'size' => 10,
                    'vMerge' => 'restart'
                ];

                $cellStyleCon = [
                    'valign' => 'center',
                    'align' => 'center',
                    'name' => 'Inter',
                    'size' => 10,
                    'vMerge' => 'continue',
                ];

                if (in_array(802, $smartProcessElements['UF_CRM_6_LABORATORIES'])) {
                    $section->addText('ЛАБОРАТОРИЯ ПОЧВ', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $section->addTextBreak(1);
                    $section->addText('Пакеты анализов', ['size' => 11, 'bold' => true, 'underline' => 'single', 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $section->addTextBreak(1);

                    $phpWord->addTableStyle('Product Table Package', $styleTable);
                    $tablePackage = $section->addTable('Product Table Package');

                    $tablePackage->addRow();
                    $tablePackage->addCell(1000, $cellStyleH)->addText('№ П/П', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    $tablePackage->addCell(2000, $cellStyleH)->addText('Объект измерений', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    $tablePackage->addCell(7000, $cellStyleH)->addText('Наименование услуги', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    $tablePackage->addCell(2000, $cellStyleH)->addText('Цена, руб. с НДС', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    // $tablePackage->addCell(2000, $cellStyleH)->addText('Срок выполнения', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);


                    $countRows = 1;
                    foreach ($arPackages as $arPackage) {
                        foreach ($arPackage as $packageKey => $product) {

                            $tablePackage->addRow();

                            if ($packageKey == 0) {
                                $tablePackage->addCell(1000, $cellStyleRestart)->addText($countRows, ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                                $tablePackage->addCell(2000, $cellStyleRestart)->addText($product['PROPERTY_82_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                            } else {
                                $tablePackage->addCell(null, $cellStyleCon);
                                $tablePackage->addCell(null, $cellStyleCon);
                            }

                            // $days = intval($product['PROPERTY_83_VALUE']) ? $product['PROPERTY_83_VALUE'] . ' раб. дн.' : '-';
                            $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';

                            $tablePackage->addCell(7000, $cellStyle)->addText($product['NAME'], ['name' => 'Inter', 'size' => 11], ['align' => 'left']);
                            $tablePackage->addCell(2000, $cellStyle)->addText($price, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                        }
                        $countRows++;
                    }

                    $section->addTextBreak(3);
                    $section->addText('Единичные анализы', ['size' => 11, 'bold' => true, 'underline' => 'single', 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $section->addTextBreak(1);
                    $phpWord->addTableStyle('Product Table Soli', $styleTable);
                    $tableSoil = $section->addTable('Product Table Soli');

                    $tableSoil->addRow();
                    $tableSoil->addCell(1700, $cellStyleH)->addText('Артикул', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    $tableSoil->addCell(8000, $cellStyleH)->addText('Наименование услуги', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    $tableSoil->addCell(2000, $cellStyleH)->addText('Цена, руб. с НДС', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);

                    $countRows = 1;
                    foreach ($arProductsSoil as $categoryName => $productsSoil) {
                        if (!$categoryName) {
                            continue;
                        }
                        foreach ($productsSoil as $productKey => $product) {

                            if ($productKey == 0) {
                                $tableSoil->addRow();
                                $tableSoil->addCell(2500, $cellStyleGray)->addText($categoryName, ['size' => 14, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                            }

                            //  $days = intval($product['PROPERTY_83_VALUE']) ? $product['PROPERTY_83_VALUE'] . ' раб. дн.' : '-';
                            $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';

                            $tableSoil->addRow();
                            $tableSoil->addCell(1700, $cellStyle)->addText($product['PROPERTY_69_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                            $tableSoil->addCell(8000, $cellStyle)->addText($product['NAME'], ['name' => 'Inter', 'size' => 11], ['align' => 'left']);
                            $tableSoil->addCell(2000, $cellStyle)->addText($price, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                            //    $tableSoil->addCell(2000, $cellStyle)->addText($days, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                        }
                    }
                }
                if (in_array(803, $smartProcessElements['UF_CRM_6_LABORATORIES'])) {
                    if ($arProductsFeed) {
                        $section->addTextBreak(1);
                        $section->addText('ЛАБОРАТОРИЯ КОРМОВ', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                        $section->addTextBreak(1);
                        $section->addText('Мокрая химия, микроскопия и другие', ['size' => 14, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                        $section->addTextBreak(1);
                        $phpWord->addTableStyle('Product Table Feed', $styleTable);
                        $tableFeed = $section->addTable('Product Table Feed');

                        $tableFeed->addRow();
                        $tableFeed->addCell(1700, $cellStyleHFeed)->addText('Артикул', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                        $tableFeed->addCell(8000, $cellStyleHFeed)->addText('Наименование услуги', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                        $tableFeed->addCell(2000, $cellStyleHFeed)->addText('Цена, руб. с НДС ', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                        // $tableFeed->addCell(2000, $cellStyleHFeed)->addText('Срок выполнения', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);

                        foreach ($arProductsFeed as $categoryName => $productsFeed) {
//                            if (!$categoryName || $categoryName == 'НИР') {
//                                continue;
//                            }
                            foreach ($productsFeed as $productKey => $product) {

                                if ($productKey == 0) {
                                    $tableFeed->addRow();
                                    $tableFeed->addCell(2500, $cellStyleGray);
                                }

                                //  $days = intval($product['PROPERTY_83_VALUE']) ? $product['PROPERTY_83_VALUE'] . ' раб. дн.' : '-';
                                $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';

                                $tableFeed->addRow();
                                $tableFeed->addCell(1700, $cellStyle)->addText($product['PROPERTY_69_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                                $tableFeed->addCell(8000, $cellStyle)->addText($product['NAME'], ['name' => 'Inter', 'size' => 11], ['align' => 'left']);
                                $tableFeed->addCell(2000, $cellStyle)->addText($price, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                                //   $tableFeed->addCell(2000, $cellStyle)->addText($days, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                            }
                        }
                    }
                }
                if (in_array(804, $smartProcessElements['UF_CRM_6_LABORATORIES'])) {
                    $section->addTextBreak(1);
                    $section->addText('ЛАБОРАТОРИЯ ВЕТЕРИНАРНОЙ ДИАГНОСТИКИ', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $section->addTextBreak(1);
                    $phpWord->addTableStyle('Product Table Wet', $styleTable);
                    $tableWet = $section->addTable('Product Table Wet');

                    $tableWet->addRow();
                    $tableWet->addCell(1700, $cellStyleHWed)->addText('Артикул', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    $tableWet->addCell(8000, $cellStyleHWed)->addText('Наименование услуги', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    $tableWet->addCell(2000, $cellStyleHWed)->addText('Цена, руб. с НДС', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    //   $tableWet->addCell(2000, $cellStyleHWed)->addText('Срок выполнения', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);


                    foreach ($arProductsWed as $categoryName => $productsWed) {
                        if (!$categoryName) {
                            continue;
                        }

                        if ($categoryName != 'Пробоподготовка') {
                            $tableWet->addRow();
                            $tableWet->addCell(2500, $cellStyleGray)->addText($categoryName, ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                        }

                        foreach ($productsWed as $productSection => $arProduct) {

                            usort($arProduct, function ($a, $b) {
                                return strcasecmp($a['NAME'], $b['NAME']);
                            });

                            $tableWet->addRow();
                            $tableWet->addCell(2500, $cellStyleGray)->addText($productSection, ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);

                            foreach ($arProduct as $productKey => $product) {

                                // $days = intval($product['PROPERTY_83_VALUE']) ? $product['PROPERTY_83_VALUE'] . ' раб. дн.' : '-';
                                $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';

                                $tableWet->addRow();
                                $tableWet->addCell(1700, $cellStyle)->addText($product['PROPERTY_69_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                                $tableWet->addCell(8000, $cellStyle)->addText($product['NAME'], ['name' => 'Inter', 'size' => 11], ['align' => 'left']);
                                $tableWet->addCell(2000, $cellStyle)->addText($price, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                                //       $tableWet->addCell(2000, $cellStyle)->addText($days, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                            }
                        }
                    }
                }
//                if (in_array(812, $smartProcessElements['UF_CRM_6_LABORATORIES'])) {
//                    if (is_array($arClinicMicrobio)) {
//                        $section->addTextBreak(1);
//                        $section->addText('ЛАБОРАТОРИЯ КЛИНИЧЕСКОЙ МИКРОБИОЛОГИИ', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
//                        $section->addTextBreak(1);
//                        $phpWord->addTableStyle('Product Table Microbio', $styleTable);
//                        $tableWet = $section->addTable('Product Table Microbio');
//
//                        $tableWet->addRow();
//                        $tableWet->addCell(1700, $cellStyleHMicrobio)->addText('Артикул', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
//                        $tableWet->addCell(8000, $cellStyleHMicrobio)->addText('Наименование услуги', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
//                        $tableWet->addCell(2000, $cellStyleHMicrobio)->addText('Цена, руб. с НДС', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
//                        //   $tableWet->addCell(2000, $cellStyleHMicrobio)->addText('Срок выполнения', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
//
//
//                        foreach ($arClinicMicrobio as $categoryName => $arProductClinicMicrobio) {
//                            if (!$categoryName) {
//                                continue;
//                            }
//
//                            $tableWet->addRow();
//                            $tableWet->addCell(2500, $cellStyleGray)->addText($categoryName, ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
//
//
//                            foreach ($arProductClinicMicrobio as $productSection => $arProduct) {
//
//                                $tableWet->addRow();
//                                $tableWet->addCell(2500, $cellStyleGray)->addText($productSection, ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
//
//                                foreach ($arProduct as $productKey => $product) {
//
//                                    //    $days = intval($product['PROPERTY_83_VALUE']) ? $product['PROPERTY_83_VALUE'] . ' раб. дн.' : '-';
//                                    $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';
//
//                                    $tableWet->addRow();
//                                    $tableWet->addCell(1700, $cellStyle)->addText($product['PROPERTY_69_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
//                                    $tableWet->addCell(8000, $cellStyle)->addText($product['NAME'], ['name' => 'Inter', 'size' => 11], ['align' => 'left']);
//                                    $tableWet->addCell(2000, $cellStyle)->addText($price, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
//                                    //          $tableWet->addCell(2000, $cellStyle)->addText($days, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
//                                }
//                            }
//                        }
//                    }
//                }
                if (in_array(805, $smartProcessElements['UF_CRM_6_LABORATORIES'])) {
                    if (is_array($arProductsMbSoil)) {
                        $section->addTextBreak(1);
                        $section->addText('ЛАБОРАТОРИЯ МИКРОБИОЛОГИИ ПОЧВ', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                        $section->addTextBreak(1);
                        $phpWord->addTableStyle('Product Table Microbio Soli', $styleTable);
                        $tableMbSoil = $section->addTable('Product Table Microbio Soli');

                        $tableMbSoil->addRow();
                        $tableMbSoil->addCell(1700, $cellStyleHMBSoil)->addText('Артикул', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                        $tableMbSoil->addCell(8000, $cellStyleHMBSoil)->addText('Наименование услуги', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                        $tableMbSoil->addCell(2000, $cellStyleHMBSoil)->addText('Цена, руб. с НДС', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                        // $tableWet->addCell(2000, $cellStyleHMBSoil)->addText('Срок выполнения', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);


                        foreach ($arProductsMbSoil as $categoryName => $arProductMbSoil) {
                            if (empty($arProductMbSoil)) {
                                continue;
                            }

                            $tableMbSoil->addRow();
                            $tableMbSoil->addCell(2500, $cellStyleGray)->addText($categoryName, ['bold' => true, 'name' => 'Inter', 'size' => 14], ['align' => 'center']);

                            foreach ($arProductMbSoil as $productSection => $arProduct) {
                                if ($productSection) {
                                    $tableMbSoil->addRow();
                                    $tableMbSoil->addCell(2500, $cellStyleGray)->addText($productSection, ['bold' => true, 'name' => 'Inter', 'size' => 14], ['align' => 'center']);
                                }
                                foreach ($arProduct as $productKey => $product) {
                                    $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';

                                    $tableMbSoil->addRow();
                                    $tableMbSoil->addCell(1700, $cellStyle)->addText($product['PROPERTY_69_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                                    $unitedText = $tableMbSoil->addCell(8000, $cellStyle)->addTextRun(['name' => 'Inter', 'size' => 11], ['align' => 'left']);
                                    $previewText = safeDocxText($product['PREVIEW_TEXT']);
                                    $unitedText->addText($previewText, ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                                    // $unitedText->addText($product['PREVIEW_TEXT'], ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                                    if (!empty($product['DETAIL_TEXT']) && is_string($product['DETAIL_TEXT'])) {
                                        $unitedText->addTextBreak(); // Разрыв строки
                                        //    $unitedText->addTextBreak(); // Разрыв строки
                                        $unitedText->addText(trim(strip_tags($product['DETAIL_TEXT'])), ['bold' => false, 'name' => 'Inter', 'size' => 11]);
                                    }
                                    $tableMbSoil->addCell(2000, $cellStyle)->addText($price, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                                    //         $tableWet->addCell(2000, $cellStyle)->addText($days, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                                }
                            }
                        }
                    }
                }
            }
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save('/home/bitrix/www/upload/tanais.document/Приложение1_' . $docTitle . '.docx');

            $arAppProp = \CFile::makeFileArray('/home/bitrix/www/upload/tanais.document/Приложение1_' . $docTitle . '.docx');
            $arAppProp['del'] = 'Y';
            $arAppProp['MODULE_ID'] = 'tanais.document';
            $fileDocId = \CFile::SaveFile($arAppProp, 'tanais.document');
            $container = \Bitrix\Crm\Service\Container::getInstance()->getFileUploader();
            //$collectionFields = $factory->getFieldsCollection();
            //$customApp1Field = $collectionFields->getField('UF_CRM_13_APPLICATION_1');
            // $customApp2Field = $collectionFields->getField('UF_CRM_13_APPLICATION_2');
            // $customDocField = $collectionFields->getField('UF_CRM_13_CONTRACT');
            //$customDoc = $collectionFields->getField('UF_CRM_13_CONTRACT_FULL');
            // $customApp1FieldId = $container->saveFileTemporary($customApp1Field, \CFile::makeFileArray(\CFile::GetPath($fileDocId)));
            //$customApp2FieldId = $container->saveFileTemporary($customApp2Field, \CFile::makeFileArray('/home/bitrix/www' . $filePathDoxApp));
            // $customDocFieldId = $container->saveFileTemporary($customDocField, \CFile::makeFileArray('/home/bitrix/www' . $filePathDocText));

            $createFilePath = "/home/bitrix/www/upload/tanais.document/" . $docTitle . ".docx";
            $dm = new DocxMerge();
            $dm->merge([
                '/home/bitrix/www' . $filePathDocText,
                '/home/bitrix/www/upload/tanais.document/Приложение1_' . $docTitle . '.docx',
                '/home/bitrix/www' . $filePathDoxApp,
            ], $createFilePath);

            $arDocProp = \CFile::makeFileArray($createFilePath);
            $arDocProp['del'] = 'Y';
            $arDocProp['MODULE_ID'] = 'tanais.document';
            $fileDocFullId = \CFile::SaveFile($arDocProp, 'tanais.document');
            //$customDocId = $container->saveFileTemporary($customDoc, \CFile::makeFileArray(\CFile::GetPath($fileDocFullId)));


            if (!empty($documentAppDocId)) {
                $arAppTimeline = \Bitrix\Crm\Timeline\DocumentEntry::getListByDocumentId($documentAppDocId);
                $appTimelineId = $arAppTimeline[0]['ID'];
                \Bitrix\Crm\Timeline\TimelineEntry::delete($appTimelineId);
            }
            if (!empty($documentTextDocId)) {
                $arDocTimeline = \Bitrix\Crm\Timeline\DocumentEntry::getListByDocumentId($documentTextDocId);
                $docTimelineId = $arDocTimeline[0]['ID'];
                \Bitrix\Crm\Timeline\TimelineEntry::delete($docTimelineId);
            }

            if (!empty($idMainDocument)) {
                $arFilter = ["ID" => $idMainDocument];
                $dbDate = \Bitrix\DocumentGenerator\Model\DocumentTable::getList(array(
                    "select" => array("*"),
                    "filter" => $arFilter,
                ));
                if ($row = $dbDate->fetch()) {
                    $generatorFileId = $row['FILE_ID'];
                }
            }

            if (!empty($generatorFileId)) {
                $arFilter = ["ID" => $generatorFileId];
                $dbDate = \Bitrix\DocumentGenerator\Model\FileTable::getList(array(
                    "select" => array("*"),
                    "filter" => $arFilter,
                ));
                if ($row = $dbDate->fetch()) {
                    $diskObjectId = $row['STORAGE_WHERE'];
                }
            }

            if (!empty($diskObjectId)) {
                $arFilter = ["ID" => $diskObjectId];
                $dbDate = \Bitrix\Disk\Internals\ObjectTable::getList(array(
                    "select" => array("*"),
                    "filter" => $arFilter,
                ));
                if ($row = $dbDate->fetch()) {
                    if (!empty($row['ID'])) {
                        $test = $row['FILE_ID'];
                        //  \CFile::Delete($row['FILE_ID']);
                        $dbDate = \Bitrix\Disk\Internals\ObjectTable::update($diskObjectId, ['FILE_ID' => $fileDocFullId]);
                    }
                }
            }
        }
    }

    public static function getAllProducts(): array
    {
        $arAllProducts = [];
        $allProducts = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => 14,
                'ACTIVE' => 'Y',
            ],
            false,
            false,
            [
                'ID',
                'DETAIL_TEXT',
                'PREVIEW_TEXT',
                'NAME',
                'PROPERTY_78', //цена прайса
                'PROPERTY_69', //артикул
            ]);

        while ($product = $allProducts->fetch()) {
            $arAllProducts[$product['ID']] = $product;
        }
        return $arAllProducts;
    }

    public static function getMilkProducts($uploadPrice = 'Y'): array
    {
        $filter = [];
        if ($uploadPrice == 'Y') {
            $filter['!PROPERTY_79'] = 59;
        }
        $arMilkProducts = [];
        $milkProducts = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => 14,
                'ACTIVE' => 'Y',
                'IBLOCK_SECTION_ID' => 32, // секция
                //'!PROPERTY_79' => 59, //выгружать в прайс
                $filter
            ],
            false,
            false,
            [
                'ID',
                'DETAIL_TEXT',
                'PREVIEW_TEXT',
                'NAME',
                'PROPERTY_78', //цена прайса
                'PROPERTY_69', //артикул
                'PROPERTY_86' //дни выполнения
            ]);

        while ($milkProduct = $milkProducts->fetch()) {
            $arMilkProducts[$milkProduct['ID']] = $milkProduct;
        }

        uasort($arMilkProducts, function ($a, $b) {
            return strcasecmp($a['PROPERTY_69_VALUE'], $b['PROPERTY_69_VALUE']);
        });

        return $arMilkProducts;
    }

    public static function getGenProductsNoCategory($uploadPrice = 'Y'): array
    {
        $filter = [];
        if ($uploadPrice == 'Y') {
            $filter['!PROPERTY_79'] = 59;
        }

        $arGenProducts = [];
        $genProducts = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => 14,
                'ACTIVE' => 'Y',
                'IBLOCK_SECTION_ID' => 33, // секция
                //  '!PROPERTY_79' => 59, //выгружать в прайс
                $filter,
            ],
            false,
            false,
            [
                'ID',
                'DETAIL_TEXT',
                'PREVIEW_TEXT',
                'NAME',
                'PROPERTY_78', //цена прайса
                'PROPERTY_69', //артикул
                'PROPERTY_86' //дни выполнения
            ]);

        while ($genProduct = $genProducts->fetch()) {
            $arGenProducts[$genProduct['ID']] = $genProduct;
        }
        return $arGenProducts;
    }

    public static function getGenProducts(): array
    {

        $arCategory = [
            1 => 'КРС',
            2 => 'Свиньи',
            3 => 'Лошади',
            4 => 'Зубры',
        ];

        $arSection = [
            0 => 'Оценка племенной ценности',
            1 => 'Подтверждение происхождения',
            2 => 'Моногенные заболевания',
        ];

        $arGenProducts = [];
        $genProducts = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => 14,
                'ACTIVE' => 'Y',
                'IBLOCK_SECTION_ID' => 33, // секция
                '!PROPERTY_79' => 59, //выгружать в прайс
            ],
            false,
            false,
            [
                'ID',
                'DETAIL_TEXT',
                'PREVIEW_TEXT',
                'NAME',
                'PROPERTY_78', //цена прайса
                'PROPERTY_69', //артикул
                'PROPERTY_86' //дни выполнения
            ]);

        while ($genProduct = $genProducts->fetch()) {
            //$arGenProducts[$genProduct['ID']] = $genProduct;
            $category = mb_substr($genProduct['PROPERTY_69_VALUE'], 2, 1);
            $section = mb_substr($genProduct['PROPERTY_69_VALUE'], 3, 1);
            $arGenProducts[$arCategory[$category]][$arSection[$section]][] = $genProduct;
        }
        return $arGenProducts;
    }

//    public static function getClinicMicrobio(): array
//    {
//        $arMicrobioProducts = [];
//
//        $arCategory = [
//            1 => 'Сельскохозяйственные животные',
//            2 => 'Мелкие домашние животные',
//            3 => 'Птицы',
//            4 => 'Все виды животных',
//            5 => 'Дополнительная пробоподготовка',
//        ];
//
//        $arSection = array_fill_keys([11, 21, 31], 'Бактериалогические исследования');
//        $arSection += array_fill_keys([12, 22, 32], 'Микологические исследования');
//        $arSection += array_fill_keys([13, 23, 43], 'Комплексные исследования');
//
//        $microbioProducts = \CIBlockElement::GetList(
//            [],
//            [
//                'IBLOCK_ID' => 14,
//                'ACTIVE' => 'Y',
//                'IBLOCK_SECTION_ID' => 35, // секция
//                '!PROPERTY_79' => 59, //выгружать в прайс
//            ],
//            false,
//            false,
//            [
//                'ID',
//                'DETAIL_TEXT',
//                'PREVIEW_TEXT',
//                'NAME',
//                'PROPERTY_78', //цена прайса
//                'PROPERTY_69', //артикул
//                'PROPERTY_86' //дни выполнения
//            ]);
//
//        while ($product = $microbioProducts->fetch()) {
//            $category = mb_substr($product['PROPERTY_69_VALUE'], 2, 1);;
//            $section = mb_substr($product['PROPERTY_69_VALUE'], 2, 2);
//            $arMicrobioProducts[$arCategory[$category]][$arSection[$section]][] = $product;
//        }
//
//        return $arMicrobioProducts;
//    }

    public static function getPriceProducts(): array
    {
        $arPrice = [];
        $price = \Bitrix\Catalog\PriceTable::getList([
            'filter' => [],
            'select' => ['PRODUCT_ID', 'PRICE']
        ]);
        while ($product = $price->fetch()) {
            $arPrice[$product['PRODUCT_ID']] = $product['PRICE'];
        }
        return $arPrice;
    }

    public static function getProductsWed(): array
    {
        $arProductsWed = [];

        $arCategory = [
            1 => 'КРС',
            2 => 'Свиньи',
            3 => 'Птицы',
            4 => 'МРС',
            5 => 'Все виды животных',
            6 => 'Кормовая продукция',
            9 => 'Пробоподготовка',
        ];

        $arSection = array_fill_keys([110, 114, 120, 124, 130, 134, 140, 144, 150, 154], 'ПЦР (единичные исследования)');
        $arSection += array_fill_keys([115, 119, 145, 149, 155, 159], 'ПЦР (пакетные предложения)');
        $arSection += array_fill_keys([210, 214, 220, 229, 230, 239, 240, 249], 'ИФА (единичные исследования)');
        $arSection += array_fill_keys([219], 'ИФА (пакетные предложения)');
        $arSection += array_fill_keys([410, 419], 'Комплексные методы (пакетные предложения)');
        $arSection += array_fill_keys([650, 654], 'Биохимические исследования');
        $arSection += array_fill_keys([750, 754], 'Клинические исследования');

        $products = \CIBlockElement::GetList(
            ['PROPERTY_69' => 'ASC'],
            [
                'IBLOCK_ID' => 14,
                'ACTIVE' => 'Y',
                '!PROPERTY_79' => 59, //выгружать в прайс
                'IBLOCK_SECTION_ID' => 34,
            ],
            false,
            false,
            [
                'ID',
                'DETAIL_TEXT',
                'PREVIEW_TEXT',
                'NAME',
                'PROPERTY_78', //цена прайса
                'PROPERTY_69', //артикул
                'PROPERTY_86' //дни выполнения
            ]);

        while ($product = $products->fetch()) {
            $category = mb_substr($product['PROPERTY_69_VALUE'], 2, 1);
            if ($category == 6) {
                $arProductsWed[$arCategory[$category]]['ПЦР исследования корма'][] = $product;
            } elseif ($category == 9) {
                $arProductsWed[$arCategory[$category]]['Пробоподготовка'][] = $product;
            } else {
                $section = mb_substr($product['PROPERTY_69_VALUE'], 1, 3);
                if ($section >= 110 && $section <= 114 || $section >= 120 && $section <= 124 || $section >= 130 && $section <= 134 || $section >= 140 && $section <= 144) {
                    $arProductsWed[$arCategory[$category]]['ПЦР (единичные исследования)'][] = $product;
                }
                if ($section >= 150 && $section <= 154 || $section >= 155 && $section <= 159) {
                    $arProductsWed[$arCategory[$category]]['ПЦР'][] = $product;
                }
                if ($section >= 115 && $section <= 119 || $section >= 145 && $section <= 149) {
                    $arProductsWed[$arCategory[$category]]['ПЦР (пакетные предложения)'][] = $product;
                }
                if ($section >= 210 && $section <= 214 || $section >= 220 && $section <= 229 || $section >= 230 && $section <= 239 || $section >= 240 && $section <= 249) {
                    $arProductsWed[$arCategory[$category]]['ИФА (единичные исследования)'][] = $product;
                }
                if ($section >= 214 && $section <= 219) {
                    $arProductsWed[$arCategory[$category]]['ИФА (пакетные предложения)'][] = $product;
                }
                if ($section >= 410 && $section <= 419) {
                    $arProductsWed[$arCategory[$category]]['Комплексные методы (пакетные предложения)'][] = $product;
                }
                if ($section >= 650 && $section <= 654) {
                    $arProductsWed[$arCategory[$category]]['Биохимические исследования'][] = $product;
                }
                if ($section >= 850 && $section <= 854) {
                    $arProductsWed[$arCategory[$category]]['Макро и микро элементы, витамины'][] = $product;
                }
                if ($section >= 750 && $section <= 754) {
                    $arProductsWed[$arCategory[$category]]['Клинические исследования'][] = $product;
                }
            }
        }


        uasort($arProductsWed, function ($a, $b) {
            return strcasecmp($a['NAME'], $b['NAME']);
        });

        return $arProductsWed;
    }

    public static function getPackages(): array
    {
        $arSections = [
            10 => 'Почва (российские методики)',
            16 => 'Почва (зарубежные методики)',
            20 => 'Тепличный грунт',
            30 => 'Почвы, грунты (безопасность)',
            40 => 'Почвы, грунты, донные отложения',
            50 => 'Вода питьевая',
            53 => 'Вода поливная',
            56 => 'Вода сточная',
            60 => 'Образцы растительного происхождения (российские методики)',
            66 => 'Образцы растительного происхождения (зарубежные методики)',
            70 => 'Органические удобрения',
            80 => 'Биопрепараты',
        ];


        $products = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => 14,
                'ACTIVE' => 'Y',
                '!PROPERTY_79' => 59, //выгружать в прайс
                'PROPERTY_80' => 60, //пакеты
                'IBLOCK_SECTION_ID' => 38,
            ],
            false,
            false,
            [
                'ID',
                'DETAIL_TEXT',
                'PREVIEW_TEXT',
                'NAME',
                'PROPERTY_78', //цена прайса
                'PROPERTY_69', //артикул
                'PROPERTY_81', //пакеты
                'PROPERTY_82', // Количество исследований в услуге
                'PROPERTY_86' //дни выполнения
            ]);

        while ($product = $products->fetch()) {
            // d($product['ID']);
            $article = substr($product['PROPERTY_69_VALUE'], 2);
            $article = substr($article, 0, -1);
            //d($article);
            $product['CATEGORY_NAME'] = $arSections[$article];
            // d($product);
            $arProductsSoil[$article][] = $product;
        }
        // d($arProductsSoil);
        ksort($arProductsSoil);


        foreach ($arProductsSoil as &$items) {
            usort($items, function ($a, $b) {
                return (int)substr($a['PROPERTY_69_VALUE'], 1) <=> (int)substr($b['PROPERTY_69_VALUE'], 1);
            });
        }

        return $arProductsSoil;
    }

    public static function getProductsSoil(): array
    {
        $arProductsSoil = [];

        $arSections = [
            2 => 'Почвы, грунты',
            3 => 'Донные отложения',
            4 => 'Образцы растительного происхождения',
            5 => 'Природная и сточная вода',
            6 => 'Органические удобрения',
        ];

        $arProductsSoil['Почвы, грунты (российские методики)'] = [];
        $arProductsSoil['Почвы, грунты (зарубежные методики)'] = [];
        $arProductsSoil['Дополнительные услуги'] = [];
        $arProductsSoil['Параметры загрязнения и элементный анализ (донные отложения)'] = [];
        $arProductsSoil['Общие показатели (донные отложения)'] = [];
        $arProductsSoil['Радиологический анализ (донные отложения)'] = [];
        $arProductsSoil['Образцы растительного происхождения (российские методики)'] = [];
        $arProductsSoil['Образцы растительного происхождения (зарубежные методики)'] = [];
        $arProductsSoil['Радиологический анализ (образцы растительного происхождения)'] = [];
        $arProductsSoil['Обобщенные показатели (вода)'] = [];
        $arProductsSoil['Радиологический анализ (вода)'] = [];
        $arProductsSoil['Параметры загрязнения и элементный анализ (удобрения)'] = [];
        $arProductsSoil['Общие показатели (органические удобрения)'] = [];
        $arProductsSoil['Радиологический анализ (органические удобрения)'] = [];

        $categories = [
            'Почвы, грунты (российские методики)' => range(20, 26),
            'Почвы, грунты (зарубежные методики)' => range(27, 28),
            'Дополнительные услуги' => range(29, 29),
            'Параметры загрязнения и элементный анализ (донные отложения)' => range(30, 30),
            'Общие показатели (донные отложения)' => range(31, 31),
            'Радиологический анализ (донные отложения)' => range(32, 39),
            'Образцы растительного происхождения (российские методики)' => range(40, 44),
            'Образцы растительного происхождения (зарубежные методики)' => range(45, 48),
            'Радиологический анализ (образцы растительного происхождения)' => range(49, 49),
            'Обобщенные показатели (вода)' => range(50, 58),
            'Радиологический анализ (вода)' => range(58, 59),
            'Параметры загрязнения и элементный анализ (удобрения)' => range(60, 61),
            'Общие показатели (органические удобрения)' => range(62, 68),
            'Радиологический анализ (органические удобрения)' => range(69, 70),
        ];

        $arProductsSoil = array_map(function ($_) {
            return [];
        }, $categories);


        $products = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => 14,
                'ACTIVE' => 'Y',
                '!PROPERTY_79' => 59, //выгружать в прайс
                '!PROPERTY_80' => 60, //пакеты
                'IBLOCK_SECTION_ID' => 38,
            ],
            false,
            false,
            [
                'ID',
                'DETAIL_TEXT',
                'PREVIEW_TEXT',
                'NAME',
                'PROPERTY_78', //цена прайса
                'PROPERTY_69', //артикул
                'PROPERTY_86' //дни выполнения
            ]);

        while ($product = $products->fetch()) {
//            $section = substr($product['PROPERTY_69_VALUE'], 1);
//            d($section);
//            $section = substr($section, 0, -2);
//            d($section);
//            if ($section >= 20 && $section < 27) {
//                $arProductsSoil['Почвы, грунты (российские методики)'][] = $product;
//            }
//            if ($section >= 27 && $section < 29) {
//                $arProductsSoil['Почвы, грунты (зарубежные методики)'][] = $product;
//            }
//            if ($section >= 29 && $section < 30) {
//                $arProductsSoil['Дополнительные услуги'][] = $product;
//            }
//            if ($section >= 30 && $section < 40) {
//                $arProductsSoil['Донные отложения'][] = $product;
//            }
//            if ($section >= 40 && $section < 45) {
//                $arProductsSoil['Образцы растительного происхождения (российские методики)'][] = $product;
//            }
//            if ($section >= 45 && $section < 50) {
//                $arProductsSoil['Образцы растительного происхождения (зарубежные методики)'][] = $product;
//            }
//            if ($section >= 50 && $section < 60) {
//                $arProductsSoil['Природная и сточная вода'][] = $product;
//            }
//            if ($section >= 60) {
//                $arProductsSoil['Органические удобрения'][] = $product;
//            }
            $code = (int)substr($product['PROPERTY_69_VALUE'], 1, -2);
            foreach ($categories as $category => $range) {
                if (in_array($code, $range)) {
                    $arProductsSoil[$category][] = $product;
                    break;
                }
            }
        }

        foreach ($arProductsSoil as &$items) {
            usort($items, function ($a, $b) {
                return (int)substr($a['PROPERTY_69_VALUE'], 1) <=> (int)substr($b['PROPERTY_69_VALUE'], 1);
            });
        }

        return $arProductsSoil;
    }

    public static function getProductsMbSoil(): array
    {

        $arCategory = [
            0 => 'Комплексные исследования',
            1 => 'Сельскохозяйственные животные',
            2 => 'Мелкие домашние животные',
            3 => 'Птицы',
            //     4 => 'Все виды животных',
            4 => 'Микроскопия',
            5 => 'Дополнительная первичная подготовка материала животного происхождения',
            6 => 'Отдельные микробиологические показатели: клинический материал, корма, почва, вода, воздух, смывы с рабочих поверхностей',
            7 => 'Паразитология',
            8 => 'Дополнительная первичная подготовка материала объектов окружающей среды',
            9 => 'Расходные материалы для отбора проб',
        ];

        $arSections = [
            1 => 'Выявление бактериальных возбудителей',
            2 => 'Выявление мицелиальных, дрожжеподобных грибов, грибов – возбудителей дерматомикозов',
        ];

        $arProductsMbSoil = [];
        foreach ($arCategory as $catKey => $catName) {
            $arProductsMbSoil[$catName] = [];
        }

        $products = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => 14,
                'ACTIVE' => 'Y',
                '!PROPERTY_79' => 59, //выгружать в прайс
                //'PROPERTY_113' => false,
                'IBLOCK_SECTION_ID' => 37,
            ],
            false,
            false,
            [
                'ID',
                'DETAIL_TEXT',
                'PREVIEW_TEXT',
                'NAME',
                'PROPERTY_78', //цена прайса
                'PROPERTY_69', //артикул
                'PROPERTY_86' //дни выполнения
            ]);


        while ($product = $products->fetch()) {
            $category = mb_substr($product['PROPERTY_69_VALUE'], 2, 1);
            $section = mb_substr($product['PROPERTY_69_VALUE'], 3, 1);
            $arProductsMbSoil[$arCategory[$category]][$arSections[$section]][] = $product;
        }

//        uasort($arProductsMbSoil, function ($a, $b) {
//            return (int)$a['PROPERTY_69_VALUE'] - (int)$b['PROPERTY_69_VALUE'];
//        });

        foreach ($arProductsMbSoil as &$sections) {
            foreach ($sections as &$items) {
                uasort($items, function ($a, $b) {
                    return (int)filter_var($a['PROPERTY_69_VALUE'], FILTER_SANITIZE_NUMBER_INT)
                        - (int)filter_var($b['PROPERTY_69_VALUE'], FILTER_SANITIZE_NUMBER_INT);
                });
            }
        }
        unset($sections, $items);

        return $arProductsMbSoil;
    }

    public static function getProductsFeed(): array
    {
        $arProductsFeed = [];

        $arSections = [
            'K90' => '00_НИР',
            'K70' => '01_Микроскопия',
            'K01' => '02_Белок',
            'K02' => '03_Жир',
            'K03' => '04_Зола',
            'K04' => '05_Углеводы',
            'K05' => '06_Молоко',
            'K06' => '07_Витамины',
            'K07' => '08_Кислотность',
            'K08' => '09_Катионы и анионы',
            'K09' => '10_Ферменты',
            'K10' => '11_Микотоксины',
            'K11' => 'Лекарственные препараты',
            'K12' => 'Сывороточные белки',
        ];

        $products = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => 14,
                'ACTIVE' => 'Y',
                '!PROPERTY_79' => 59, //выгружать в прайс
                'IBLOCK_SECTION_ID' => 46,
            ],
            false,
            false,
            [
                'ID',
                'DETAIL_TEXT',
                'PREVIEW_TEXT',
                'NAME',
                'PROPERTY_78', //цена прайса
                'PROPERTY_69', //артикул
                'PROPERTY_86' //дни выполнения
            ]);

        while ($product = $products->fetch()) {
            $section = mb_substr($product['PROPERTY_69_VALUE'], 0, 3);
            $arProductsFeed[$arSections[$section]][] = $product;
        }

        uasort($arProductsFeed, function ($a, $b) {
            return (int)$a['PROPERTY_69_VALUE'] - (int)$b['PROPERTY_69_VALUE'];
        });

        ksort($arProductsFeed);

        return $arProductsFeed;
    }


    public static function createPriceList($sectionId = null)
    {

        $arPrice = self::getPriceProducts();
        $arMilkProducts = self::getMilkProducts();
        $arGenProducts = self::getGenProducts();
        $arPackages = self::getPackages();
        $arProductsFeed = self::getProductsFeed();
        $arProductsWed = self::getProductsWed();
        $arProductsSoil = self::getProductsSoil();
        //   $arClinicMicrobio = self::getClinicMicrobio();
        $arProductsMbSoil = self::getProductsMbSoil();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        $section = $phpWord->addSection(['marginTop' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1, 25),
            'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1, 25),
            'marginLeft' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1, 25),
            'marginRight' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(1, 25),]);


        $styleTable = [
            'borderSize' => 6,
            'cellMargin' => 50,
            'name' => 'Inter',
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, // Выравнивание таблицы по центру
        ];

        $cellStyleGray = [
            'valign' => 'center',
            'align' => 'center',
            'name' => 'Inter',
            'size' => 10,
            'bgColor' => 'E7E6E6',
            'gridSpan' => 4,
        ];

        $cellStyleH = [
            'valign' => 'center',
            'align' => 'center',
            'name' => 'Inter',
            'size' => 10,
            'bgColor' => 'FF7D7D',
        ];

        $cellStyleHFeed = [
            'valign' => 'center',
            'align' => 'center',
            'name' => 'Inter',
            'size' => 10,
            'bgColor' => 'C5E0B3',
        ];

        $cellStyleHWed = [
            'valign' => 'center',
            'align' => 'center',
            'name' => 'Inter',
            'size' => 10,
            'bgColor' => 'FFF2CC',
        ];

        $cellStyleHMicrobio = [
            'valign' => 'center',
            'align' => 'center',
            'name' => 'Inter',
            'size' => 10,
            'bgColor' => 'DEBDFF',
        ];

        $cellStyleHMBSoil = [
            'valign' => 'center',
            'align' => 'center',
            'name' => 'Inter',
            'size' => 10,
            'bgColor' => 'FF66CC',
        ];

        $cellStyleHGen = [
            'valign' => 'center',
            'align' => 'center',
            'name' => 'Inter',
            'size' => 10,
            'bgColor' => 'FF9933',
        ];

        $cellStyleMilk = [
            'valign' => 'center',
            'align' => 'center',
            'name' => 'Inter',
            'size' => 10,
            'bgColor' => '6699cc',
        ];

        $cellStyle = [
            'valign' => 'center',
            'align' => 'center',
            'name' => 'Inter',
            'size' => 4,
        ];


        $table = $section->addTable(['borderSize' => 0, 'borderColor' => 'ffffff',]);

        $row = $table->addRow();

        if ($sectionId == null || $sectionId == 46 || $sectionId == 37 || $sectionId == 35 || $sectionId == 38 || $sectionId == 34 || $sectionId == 33 || $sectionId == 32) {

            $cell1 = $row->addCell(6000);
            $cell1->addText("115409, г.Москва, Каширское шоссе, д. 49", ['name' => 'Inter', 'size' => 10], ['spaceAfter' => 0, 'spacing' => 0]);
            $cell1->addText("Телефон единого CALL-центра: +7 499 371-19-19", ['name' => 'Inter', 'size' => 10], ['spaceAfter' => 0, 'spacing' => 0]);
            $cell1->addText("Whatsapp: +7 995 888-57-21", ['name' => 'Inter', 'size' => 10], ['spaceAfter' => 0, 'spacing' => 0]);
            $cell1->addText("e-mail: info@agroplem.ru", ['name' => 'Inter', 'size' => 10], ['spaceAfter' => 0, 'spacing' => 0]);

            $cell2 = $row->addCell(6000);
            $cell2->addImage('/home/bitrix/www/upload/a.png', ['width' => 200, 'height' => 40, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            $cell2->addText(self::getCurrentDateRus(), ['bold' => true, 'name' => 'Inter', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);

            $section->addTextBreak(2);
            $section->addText('Прайс-лист на услуги', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $section->addTextBreak(1);

            if ($sectionId == null || $sectionId == 32) {
                $priceName = 'Прайс-лист ЛМ ';
                $section->addText('ЛАБОРАТОРИЯ МОЛОКА', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

                $phpWord->addTableStyle('Product Table Milk', $styleTable);
                $tableMilk = $section->addTable('Product Table Milk');

                $tableMilk->addRow();
                $tableMilk->addCell(1700, $cellStyleMilk)->addText('Артикул', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                $tableMilk->addCell(8000, $cellStyleMilk)->addText('Наименование услуг', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                $tableMilk->addCell(2000, $cellStyleMilk)->addText('Цена, руб.', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);

                $countProducts = 1;

                foreach ($arMilkProducts as $milkProduct) {

                    $price = intval($milkProduct['PROPERTY_78_VALUE']) ? number_format(intval($milkProduct['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';

                    $tableMilk->addRow();
                    $tableMilk->addCell(2000, $cellStyle)->addText($milkProduct['PROPERTY_69_VALUE'], ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    $unitedText = $tableMilk->addCell(7000, $cellStyle)->addTextRun(['bold' => false, 'name' => 'Inter', 'size' => 11], ['align' => 'left']);
                    //$unitedText->addText($milkProduct['PREVIEW_TEXT'], ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                    $previewText = safeDocxText($milkProduct['PREVIEW_TEXT']);
                    $unitedText->addText($previewText, ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                    if ($milkProduct['DETAIL_TEXT']) {
                        $unitedText->addTextBreak(); // Разрыв строки
                        // $unitedText->addTextBreak(); // Разрыв строки
                        $unitedText->addText($milkProduct['DETAIL_TEXT'], ['bold' => false, 'name' => 'Inter', 'size' => 11]);
                    }
                    $tableMilk->addCell(2000, $cellStyle)->addText($price, ['bold' => false, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);

                    $countProducts++;
                }
                $section->addTextBreak(1);
                $section->addText('Пожалуйста, соблюдайте рекомендации по забору биологического материала! От качества забора биологического материала во многом зависит достоверность результатов исследований. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                $section->addText('Срок выполнения работ считается от даты получения проб лабораторией до 17:00. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                $section->addText('Обязательным сопроводительным документом, предоставляемым в лабораторию, является подписанная Заявка на проведение испытаний установленной формы. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);

            }

            if ($sectionId == null || $sectionId == 33) {

                $priceName = 'Прайс-лист ЛГ ';
                $section->addText('ЛАБОРАТОРИЯ МОЛЕКУЛЯРНО-ГЕНЕТИЧЕСКОЙ ЭКСПЕРТИЗЫ', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

                $phpWord->addTableStyle('Product Table Gen', $styleTable);
                $tableGen = $section->addTable('Product Table Gen');

                $tableGen->addRow(1);
                $tableGen->addCell(1700, $cellStyleHGen)->addText('Артикул', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                $tableGen->addCell(8000, $cellStyleHGen)->addText('Наименование услуг', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                $tableGen->addCell(2000, $cellStyleHGen)->addText('Цена, руб.', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);

                $countProducts = 1;

                foreach ($arGenProducts as $categoryName => $genProduct) {

                    $tableGen->addRow();
                    $tableGen->addCell(2500, $cellStyleGray)->addText($categoryName, ['bold' => true, 'name' => 'Inter', 'size' => 14], ['align' => 'center']);


                    foreach ($genProduct as $productSection => $arProduct) {

                        if ($productSection) {
                            $tableGen->addRow();
                            $tableGen->addCell(2500, $cellStyleGray)->addText($productSection, ['bold' => true, 'name' => 'Inter', 'size' => 14,], ['align' => 'center']);
                        }
                        foreach ($arProduct as $product) {

                            //$days = $genProduct['PROPERTY_81_VALUE'] ? $genProduct['PROPERTY_81_VALUE'] . ' дней' : '-';
                            if (!$arPrice[$product['ID']] || $arPrice[$product['ID']] == '0.00 ') {
                                $price = 'По запросу';
                            } else {
                                $price = $arPrice[$product['ID']];
                            }
                            $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';

                            $tableGen->addRow();
                            $tableGen->addCell(1700, $cellStyle)->addText($product['PROPERTY_69_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                            $unitedText = $tableGen->addCell(8000, $cellStyle)->addTextRun(['bold' => false, 'name' => 'Inter', 'size' => 11], ['align' => 'left', 'preserveLineBreaks' => true]);
                            // $unitedText->addText($product['PREVIEW_TEXT'], ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                            $previewText = safeDocxText($product['PREVIEW_TEXT']);
                            $unitedText->addText($previewText, ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                            if ($product['DETAIL_TEXT']) {
                                $unitedText->addTextBreak(); // Разрыв строки
                                // $unitedText->addTextBreak(); // Разрыв строки
                                $unitedText->addText($product['DETAIL_TEXT'], ['bold' => false, 'name' => 'Inter', 'size' => 11]);
                            }
                            $tableGen->addCell(2000, $cellStyle)->addText($price, ['bold' => false, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);

                            $countProducts++;
                        }
                    }
                }
                $section->addTextBreak(1);
                $section->addText('Пожалуйста, соблюдайте рекомендации по забору биологического материала! От качества забора биологического материала во многом зависит достоверность результатов исследований. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                $section->addText('Срок выполнения работ считается от даты получения проб лабораторией до 17:00. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                $section->addText('Обязательным сопроводительным документом, предоставляемым в лабораторию, является подписанная Заявка на проведение испытаний установленной формы. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);

            }

            if ($sectionId == null || $sectionId == 38) {
                $priceName = 'Прайс-лист ЛП ';
                $section->addText('ЛАБОРАТОРИЯ ПОЧВ', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                $phpWord->addTableStyle('Product Table Soli', $styleTable);
                $tableSoil = $section->addTable('Product Table Soli');

                $tableSoil->addRow();
                $tableSoil->addCell(1700, $cellStyleH)->addText('Артикул', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                $tableSoil->addCell(8000, $cellStyleH)->addText('Наименование услуги', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                $tableSoil->addCell(2000, $cellStyleH)->addText('Цена, руб. с НДС', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);

                $countRows = 1;
                foreach ($arPackages as $categoryName => $arPackage) {
                    foreach ($arPackage as $packageKey => $product) {

                        if ($packageKey == 0 && !empty($product['CATEGORY_NAME'])) {
                            $tableSoil->addRow();
                            $tableSoil->addCell(2500, $cellStyleGray)->addText($product['CATEGORY_NAME'], ['size' => 14, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                        }

                        $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';

                        $tableSoil->addRow();
                        $tableSoil->addCell(1700, $cellStyle)->addText($product['PROPERTY_69_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                        $unitedText = $tableSoil->addCell(8000, $cellStyle)->addTextRun(['name' => 'Inter', 'size' => 11], ['align' => 'left']);
                        // $unitedText->addText($product['PREVIEW_TEXT'], ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                        $previewText = safeDocxText($product['PREVIEW_TEXT']);
                        $unitedText->addText($previewText, ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                        if (!empty($product['DETAIL_TEXT']) && is_string($product['DETAIL_TEXT'])) {
                            $unitedText->addTextBreak(); // Разрыв строки
                            //  $unitedText->addTextBreak(); // Разрыв строки
                            $unitedText->addText(trim(strip_tags($product['DETAIL_TEXT'])), ['bold' => false, 'name' => 'Inter', 'size' => 11]);
                        }
                        $tableSoil->addCell(2000, $cellStyle)->addText($price, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    }
                    $countRows++;
                }


                $countRows = 1;
                foreach ($arProductsSoil as $categoryName => $productsSoil) {
                    if (!$categoryName) {
                        continue;
                    }
                    foreach ($productsSoil as $productKey => $product) {

                        if ($productKey == 0) {
                            $tableSoil->addRow();
                            $tableSoil->addCell(2500, $cellStyleGray)->addText($categoryName, ['size' => 14, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                        }

                        // $days = intval($product['PROPERTY_83_VALUE']) ? $product['PROPERTY_83_VALUE'] . ' раб. дн.' : '-';
                        $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';

                        $tableSoil->addRow();
                        $tableSoil->addCell(1700, $cellStyle)->addText($product['PROPERTY_69_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                        $unitedText = $tableSoil->addCell(8000, $cellStyle)->addTextRun(['name' => 'Inter', 'size' => 11], ['align' => 'left']);
                        //$unitedText->addText($product['PREVIEW_TEXT'], ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                        $previewText = safeDocxText($product['PREVIEW_TEXT']);
                        $unitedText->addText($previewText, ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                        if (!empty($product['DETAIL_TEXT']) && is_string($product['DETAIL_TEXT'])) {
                            $unitedText->addTextBreak(); // Разрыв строки
                            //     $unitedText->addTextBreak(); // Разрыв строки
                            $unitedText->addText(trim(strip_tags($product['DETAIL_TEXT'])), ['bold' => false, 'name' => 'Inter', 'size' => 11]);
                        }
                        $tableSoil->addCell(2000, $cellStyle)->addText($price, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                        //   $tableSoil->addCell(2000, $cellStyle)->addText($days, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    }
                }
                $section->addTextBreak(1);
                $section->addText('Стоимость проведения анализов включает в себя пробоподготовку, выдачу протокола испытаний, отчета на пробу/поле, рекомендаций. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                $section->addText('Важная информация!', ['size' => 11, 'bold' => true, 'name' => 'Inter', 'color' => 'ED7D31',], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                $section->addText('Срок выполнения работ считается от даты получения проб в лабораторию до 13:00.', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                $section->addText('В стоимость анализа каждого показателя заложены регистрация, сушка и размол проб. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                $section->addText('Протокол, графические отчеты, рекомендации и иные формы отчетности по результатам испытаний, предоставляется заказчику в электронном виде.', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                $section->addText('Выдача отчетности в печатном виде производится по запросу заказчика.', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                $section->addTextBreak(1);
            }
            if ($sectionId == null || $sectionId == 46) {
                $priceName = 'Прайс-лист ЛК ';
                if ($arProductsFeed) {
                    $section->addText('ЛАБОРАТОРИЯ КОРМОВ', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $section->addText('Спектроскопический анализ (NIR – исследования ' . $arProductsFeed['НИР'][0]['PROPERTY_71_VALUE'] . ') ', ['size' => 14, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $phpWord->addTableStyle('Product Table Feed', $styleTable);
                    $tableFeed = $section->addTable('Product Table Feed');

                    $tableFeed->addRow();
                    $tableFeed->addCell(1700, $cellStyleHFeed)->addText('Артикул', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    $tableFeed->addCell(8000, $cellStyleHFeed)->addText('Наименование услуги', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    $tableFeed->addCell(2000, $cellStyleHFeed)->addText('Цена, руб. с НДС ', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    //$tableFeed->addCell(2000, $cellStyleHFeed)->addText('Срок выполнения', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);

                    foreach ($arProductsFeed as $categoryName => $productsFeed) {
                        if (!$categoryName) {
                            continue;
                        }

                        foreach ($productsFeed as $productKey => $product) {

                            if ($productKey == 0) {
                                $tableFeed->addRow();
                                $tableFeed->addCell(2500, $cellStyleGray);
                            }

                            //   $days = intval($product['PROPERTY_83_VALUE']) ? $product['PROPERTY_83_VALUE'] . ' раб. дн.' : '-';
                            $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';

                            $tableFeed->addRow();
                            $tableFeed->addCell(1700, $cellStyle)->addText($product['PROPERTY_69_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                            $unitedText = $tableFeed->addCell(8000, $cellStyle)->addTextRun(['name' => 'Inter', 'size' => 11], ['align' => 'left']);
                            $previewText = safeDocxText($product['PREVIEW_TEXT']);
                            $unitedText->addText($previewText, ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                            //$unitedText->addText($product['PREVIEW_TEXT'], ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                            if (!empty($product['DETAIL_TEXT']) && is_string($product['DETAIL_TEXT'])) {
                                $unitedText->addTextBreak(); // Разрыв строки
                                // $unitedText->addTextBreak(); // Разрыв строки
                                $pos = strpos($product['DETAIL_TEXT'], "Показатели");
                                if ($pos) {
                                    $rawTypes = trim(substr($product['DETAIL_TEXT'], 0, $pos));
                                    $indicators = trim(substr($product['DETAIL_TEXT'], $pos));
                                    $unitedText->addText(strip_tags($rawTypes), ['bold' => false, 'name' => 'Inter', 'size' => 11]);
                                    // $unitedText->addTextBreak();
                                    $unitedText->addTextBreak();
                                    $unitedText->addText(strip_tags($indicators), ['bold' => false, 'name' => 'Inter', 'size' => 11]);
                                } else {
                                    $unitedText->addText(trim(strip_tags($product['DETAIL_TEXT'])), ['bold' => false, 'name' => 'Inter', 'size' => 11]);
                                }
                            }
                            $tableFeed->addCell(2000, $cellStyle)->addText($price, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                            //   $tableFeed->addCell(2000, $cellStyle)->addText($days, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                        }
                    }
                    $section->addTextBreak(1);
                    $section->addText('Пожалуйста, соблюдайте рекомендации по отбору проб! От соблюдения методов выделения выборки, отбора точечных проб и подготовки пробы для анализа во многом зависят конечные результаты испытаний продукции проверяемой партии. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                    $section->addText('Срок выполнения работ считается от даты получения проб лабораторией до 17:00. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                    $section->addText('Стоимость проведения испытаний включает в себя пробоподготовку, выдачу протокола испытаний или отчета.  ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                    $section->addText('Обязательным сопроводительным документом, предоставляемым в лабораторию, является подписанная Заявка на проведение испытаний установленной формы. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);

                }
            }
            if ($sectionId == null || $sectionId == 34) {
                $priceName = 'Прайс-лист ЛВД ';
                $section->addText('ЛАБОРАТОРИЯ ВЕТЕРИНАРНОЙ ДИАГНОСТИКИ', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                $section->addTextBreak(1);
                $phpWord->addTableStyle('Product Table Wet', $styleTable);
                $tableWet = $section->addTable('Product Table Wet');

                $tableWet->addRow();
                $tableWet->addCell(1700, $cellStyleHWed)->addText('Артикул', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                $tableWet->addCell(8000, $cellStyleHWed)->addText('Наименование услуги', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                $tableWet->addCell(2000, $cellStyleHWed)->addText('Цена, руб. с НДС', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                //  $tableWet->addCell(2000, $cellStyleHWed)->addText('Срок выполнения', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);


                foreach ($arProductsWed as $categoryName => $productsWed) {
                    if (!$categoryName) {
                        continue;
                    }

                    if ($categoryName != 'Пробоподготовка') {
                        $tableWet->addRow();
                        $tableWet->addCell(2500, $cellStyleGray)->addText($categoryName, ['bold' => true, 'name' => 'Inter', 'size' => 14], ['align' => 'center']);
                    }

                    foreach ($productsWed as $productSection => $arProduct) {

                        usort($arProduct, function ($a, $b) {
                            return strcasecmp($a['PREVIEW_TEXT'], $b['PREVIEW_TEXT']);
                        });

                        $tableWet->addRow();
                        $tableWet->addCell(2500, $cellStyleGray)->addText($productSection, ['bold' => true, 'name' => 'Inter', 'size' => 14], ['align' => 'center']);

                        foreach ($arProduct as $productKey => $product) {

                            //  $days = intval($product['PROPERTY_83_VALUE']) ? $product['PROPERTY_83_VALUE'] . ' раб. дн.' : '-';
                            $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';

                            $tableWet->addRow();
                            $tableWet->addCell(1700, $cellStyle)->addText($product['PROPERTY_69_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                            $unitedText = $tableWet->addCell(8000, $cellStyle)->addTextRun(['name' => 'Inter', 'size' => 11], ['align' => 'left']);
                            //$unitedText->addText(strval($product['PREVIEW_TEXT']), ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                            $previewText = safeDocxText($product['PREVIEW_TEXT']);
                            $unitedText->addText($previewText, ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                            if (!empty($product['DETAIL_TEXT']) && is_string($product['DETAIL_TEXT'])) {
                                addFormattedDetailText($unitedText, $product['DETAIL_TEXT']);

//                                $posMaterial = strpos($product['DETAIL_TEXT'], "Материал:");
//                                $posMethod = strpos($product['DETAIL_TEXT'], "Метод:");
//                                $posMin = strpos($product['DETAIL_TEXT'], "Минимальная партия:");
//                                if ($posMaterial) {
//                                    $material = trim(substr($product['DETAIL_TEXT'], $posMaterial));
//                                    $materialParts = explode(":", $material, 2);
//                                    $materialContent = isset($materialParts[1]) ? trim($materialParts[1]) : "";
//                                    $result = trim(substr($product['DETAIL_TEXT'], 0, $posMaterial));
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addText($result, ['bold' => false, 'name' => 'Inter', 'size' => 11]);
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addText('Материал: ', ['bold' => true, 'name' => 'Inter', 'size' => 11]);
//                                    $unitedText->addText($materialContent, ['bold' => false, 'name' => 'Inter', 'size' => 11]);
//                                }
//                                if ($posMethod) {
//                                    $material = trim(substr($product['DETAIL_TEXT'], $posMethod));
//                                    $materialParts = explode(":", $material, 2);
//                                    $materialContent = isset($materialParts[1]) ? trim($materialParts[1]) : "";
//                                    $result = trim(substr($product['DETAIL_TEXT'], 0, $posMethod));
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addText($result, ['bold' => false, 'name' => 'Inter', 'size' => 11]);
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addText('Метод: ', ['bold' => true, 'name' => 'Inter', 'size' => 11]);
//                                    $unitedText->addText($materialContent, ['bold' => false, 'name' => 'Inter', 'size' => 11]);
//                                }
//                                if ($posMin) {
//                                    $material = trim(substr($product['DETAIL_TEXT'], $posMin));
//                                    $materialParts = explode(":", $material, 2);
//                                    $materialContent = isset($materialParts[1]) ? trim($materialParts[1]) : "";
//                                    $result = trim(substr($product['DETAIL_TEXT'], 0, $posMin));
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addText($result, ['bold' => false, 'name' => 'Inter', 'size' => 11]);
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addText('Минимальная партия: ', ['bold' => true, 'name' => 'Inter', 'size' => 11]);
//                                    $unitedText->addText($materialContent, ['bold' => false, 'name' => 'Inter', 'size' => 11]);
//                                }
//                                if(!$posMaterial && !$posMethod && !$posMin) {
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addText(trim(strip_tags($product['DETAIL_TEXT'])), ['bold' => false, 'name' => 'Inter', 'size' => 11]);
//                                }
                            }
                            $tableWet->addCell(2000, $cellStyle)->addText($price, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                            //       $tableWet->addCell(2000, $cellStyle)->addText($days, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                        }
                    }
                }

                $section->addTextBreak(1);
                $textRun = $section->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                $textRun->addText('Пожалуйста, соблюдайте рекомендации по отбору проб! Для взятия проб следует применять ', ['size' => 11, 'name' => 'Inter']);
                $textRun->addText('стерильные  ', ['size' => 11, 'bold' => true, 'name' => 'Inter']);
                $textRun->addText('инструменты, а для их транспортировки — ', ['size' => 11, 'name' => 'Inter']);
                $textRun->addText('стерильные  ', ['size' => 11, 'bold' => true, 'name' => 'Inter']);
                $section->addText('пробирки или контейнеры со строгим соблюдением температурного режима. ', ['size' => 11, 'name' => 'Inter']);
                $section->addText('Ошибки на преаналитическом этапе могут привести к искажению качества окончательных результатов лабораторных исследований. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
            }
//            if ($sectionId == null || $sectionId == 35) {
//                $priceName = 'Прайс-лист ЛКМ ';
//                if (!empty($arClinicMicrobio)) {
//                    $section->addText('ЛАБОРАТОРИЯ КЛИНИЧЕСКОЙ МИКРОБИОЛОГИИ', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
//                    $section->addTextBreak(1);
//                    $phpWord->addTableStyle('Product Table Microbio', $styleTable);
//                    $tableMicrobio = $section->addTable('Product Table Microbio');
//
//                    $tableMicrobio->addRow();
//                    $tableMicrobio->addCell(1700, $cellStyleHMicrobio)->addText('Артикул', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
//                    $tableMicrobio->addCell(8000, $cellStyleHMicrobio)->addText('Наименование услуги', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
//                    $tableMicrobio->addCell(2000, $cellStyleHMicrobio)->addText('Цена, руб. с НДС', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
//                    // $tableMicrobio->addCell(2000, $cellStyleHMicrobio)->addText('Срок выполнения', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
//
//
//                    foreach ($arClinicMicrobio as $categoryName => $arProductClinicMicrobio) {
//                        if (!$categoryName) {
//                            continue;
//                        }
//
//                        $tableMicrobio->addRow();
//                        $tableMicrobio->addCell(2500, $cellStyleGray)->addText($categoryName, ['bold' => true, 'name' => 'Inter', 'size' => 14], ['align' => 'center']);
//
//
//                        foreach ($arProductClinicMicrobio as $productSection => $arProduct) {
//
//                            if ($productSection) {
//                                $tableMicrobio->addRow();
//                                $tableMicrobio->addCell(2500, $cellStyleGray)->addText($productSection, ['bold' => true, 'name' => 'Inter', 'size' => 14], ['align' => 'center']);
//                            }
//
//                            foreach ($arProduct as $productKey => $product) {
//
//                                // $days = intval($product['PROPERTY_83_VALUE']) ? $product['PROPERTY_83_VALUE'] . ' раб. дн.' : '-';
//                                $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';
//
//                                $tableMicrobio->addRow();
//                                $tableMicrobio->addCell(1700, $cellStyle)->addText($product['PROPERTY_69_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
//                                $unitedText = $tableMicrobio->addCell(8000, $cellStyle)->addTextRun(['name' => 'Inter', 'size' => 11], ['align' => 'left']);
//                                $unitedText->addText($product['PREVIEW_TEXT'], ['bold' => true, 'name' => 'Inter', 'size' => 11]);
//                                if (!empty($product['DETAIL_TEXT']) && is_string($product['DETAIL_TEXT'])) {
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addTextBreak(); // Разрыв строки
//                                    $unitedText->addText(trim(strip_tags($product['DETAIL_TEXT'])), ['bold' => false, 'name' => 'Inter', 'size' => 11]);
//                                }
//                                $tableMicrobio->addCell(2000, $cellStyle)->addText($price, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
//                                //         $tableWet->addCell(2000, $cellStyle)->addText($days, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
//                            }
//                        }
//                    }
//                    $section->addTextBreak(1);
//                    $section->addText('Пожалуйста, соблюдайте рекомендации по отбору проб! Для получения достоверных результатов материал нужно забирать до назначения антибиотиков (допустимо однократное введение) и биопрепаратов или через 2 недели после их отмены. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
//                    $section->addText('При сохранении клинической картины на фоне антибиотикотерапии сдача материала возможна. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
//                    $textRun = $section->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
//                    $textRun->addText('Для взятия проб следует применять ', ['size' => 11, 'name' => 'Inter']);
//                    $textRun->addText('стерильные  ', ['size' => 11, 'bold' => true, 'name' => 'Inter']);
//                    $textRun->addText('инструменты, а для их транспортировки — ', ['size' => 11, 'name' => 'Inter']);
//                    $textRun->addText('стерильные  ', ['size' => 11, 'bold' => true, 'name' => 'Inter']);
//                    $section->addText('пробирки или контейнеры. ', ['size' => 11, 'name' => 'Inter']);
//                }
//            }
            if ($sectionId == null || $sectionId == 37) {
                $priceName = 'Прайс-лист ЛМБ ';
                if (!empty($arProductsMbSoil)) {
                    $section->addText('ЛАБОРАТОРИЯ МИКРОБИОЛОГИИ', ['size' => 16, 'bold' => true, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $section->addTextBreak(1);
                    $phpWord->addTableStyle('Product Table Microbio Soli', $styleTable);
                    $tableMbSoil = $section->addTable('Product Table Microbio Soli');

                    $tableMbSoil->addRow();
                    $tableMbSoil->addCell(1700, $cellStyleHMBSoil)->addText('Артикул', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    $tableMbSoil->addCell(8000, $cellStyleHMBSoil)->addText('Наименование услуги', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    $tableMbSoil->addCell(2000, $cellStyleHMBSoil)->addText('Цена, руб. с НДС', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);
                    // $tableWet->addCell(2000, $cellStyleHMBSoil)->addText('Срок выполнения', ['bold' => true, 'name' => 'Inter', 'size' => 11], ['align' => 'center']);


                    foreach ($arProductsMbSoil as $categoryName => $arProductMbSoil) {
                        if (empty($arProductMbSoil) || empty($categoryName)) {
                            continue;
                        }

                        $tableMbSoil->addRow();
                        $tableMbSoil->addCell(2500, $cellStyleGray)->addText($categoryName, ['bold' => true, 'name' => 'Inter', 'size' => 14], ['align' => 'center']);

                        foreach ($arProductMbSoil as $productSection => $arProduct) {
                            if ($productSection) {
                                $tableMbSoil->addRow();
                                if ($categoryName == 'Отдельные микробиологические показатели: клинический материал, корма, почва, вода, воздух, смывы с рабочих поверхностей') {
                                    $tableMbSoil->addCell(2500, $cellStyleGray)->addText('Фитопатология', ['bold' => true, 'name' => 'Inter', 'size' => 14], ['align' => 'center']);
                                } else {
                                    $tableMbSoil->addCell(2500, $cellStyleGray)->addText($productSection, ['bold' => true, 'name' => 'Inter', 'size' => 14], ['align' => 'center']);
                                }
                            }
                            foreach ($arProduct as $productKey => $product) {
                                $price = intval($product['PROPERTY_78_VALUE']) ? number_format(intval($product['PROPERTY_78_VALUE']), 0, '', ' ') : 'По запросу';

                                $tableMbSoil->addRow();
                                $tableMbSoil->addCell(1700, $cellStyle)->addText($product['PROPERTY_69_VALUE'], ['size' => 11, 'bold' => true, 'name' => 'Inter'], ['align' => 'center']);
                                $unitedText = $tableMbSoil->addCell(8000, $cellStyle)->addTextRun(['name' => 'Inter', 'size' => 11], ['align' => 'left']);
                                $previewText = safeDocxText($product['PREVIEW_TEXT']);
                                $unitedText->addText($previewText, ['bold' => true, 'name' => 'Inter', 'size' => 11]);
                                if (!empty($product['DETAIL_TEXT']) && is_string($product['DETAIL_TEXT'])) {
                                    $unitedText->addTextBreak(); // Разрыв строки
                                    // $unitedText->addTextBreak(); // Разрыв строки
                                    $unitedText->addText(trim(strip_tags($product['DETAIL_TEXT'])), ['bold' => false, 'name' => 'Inter', 'size' => 11]);
                                }
                                $tableMbSoil->addCell(2000, $cellStyle)->addText($price, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                                //         $tableWet->addCell(2000, $cellStyle)->addText($days, ['name' => 'Inter', 'size' => 11], ['align' => 'center']);
                            }
                        }
                    }
                    $section->addTextBreak(1);
                    $section->addText('Пожалуйста, соблюдайте рекомендации по отбору биоматериала.', ['size' => 11, 'name' => 'Inter', 'italic' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                    $section->addText('Основные требования: ', ['size' => 13, 'name' => 'Inter', 'bold' => true,], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                    $section->addText('Отбор материала необходимо проводить до начала антибиотикотерапии, в случае приема антимикробных препаратов – через 10 -14 дней после их отмены.', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                    $section->addText('В случае сохранения клинических симптомов на фоне приема антибактериальных препаратов, отбор проб на микробиологическое исследование допускается.', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                    $section->addText('При отборе материала необходимо соблюдать правила асептики/антисептики. Биоматериал отбирают в одноразовых перчатках, стерильными инструментами, в стерильную лабораторную тару.', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                    $section->addText('Настоятельно рекомендуем соблюдать требования лаборатории в процессе хранения и транспортировки биоматериала. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                    $section->addText('Срок выполнения исследований устанавливается от даты доставки проб в лабораторию до 17:00. ', ['size' => 11, 'name' => 'Inter'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
                }
            }
        }
        $section->addTextBreak(1);
        $section->addText('Мы готовы сделать Вам самое выгодное предложение!', ['size' => 11, 'bold' => true, 'name' => 'Inter', 'color' => 'ED7D31',], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $section->addText('По любым интересующим вопросам, вы можете связаться с нами:', ['size' => 11, 'bold' => true, 'name' => 'Inter',], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $section->addTextBreak(1);
        $section->addText("Телефон: +7 499 371-19-19", ['name' => 'Inter', 'size' => 11], ['spaceAfter' => 0, 'spacing' => 0, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $section->addText("Наш адрес: г. Москва, Каширское шоссе, д. 49", ['name' => 'Inter', 'size' => 11], ['spaceAfter' => 0, 'spacing' => 0, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $section->addText("e-mail: info@agroplem.ru", ['name' => 'Inter', 'size' => 11], ['spaceAfter' => 0, 'spacing' => 0, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $section->addText("www.agroplem.ru", ['name' => 'Inter', 'size' => 11], ['spaceAfter' => 0, 'spacing' => 0, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $section->addTextBreak(1);
        $section->addText('Мы готовы сделать Вам самое выгодное предложение!', ['size' => 11, 'bold' => true, 'name' => 'Inter', 'color' => 'ED7D31',], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);


        if ($phpWord) {
            if ($sectionId == null) {
                $priceName = 'Прайс-лист ';
            }
            $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $dateNow = new \DateTime('now');
            $exportFilename = $priceName . $dateNow->format('d.m.Y') . '.docx';
            header('Content-Disposition: attachment; filename="' . $exportFilename . '"');
            $objWriter->save('php://output');
        }

        return ['done'];
    }

    public static function getCurrentDateRus(): string
    {
        $months = [
            1 => 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
            'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
        ];

        $currentMonth = date('n'); // Текущий месяц (1-12)
        $currentYear = date('Y');  // Текущий год

        $monthName = $months[$currentMonth];

        return $monthName . ' ' . $currentYear;
    }
}

function safeDocxText(string $string): string
{
    if ($string === null || $string === '') {
        return '';
    }

    $string = preg_replace('~<\s*br\s*/?\s*>~iu', "\n", $string);
    $string = preg_replace('~</\s*p\s*>~iu', "\n\n", $string);
    $string = preg_replace('~</\s*li\s*>~iu', "\n", $string);

    $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $string = strip_tags($string);

    $string = preg_replace('/\x{00A0}|\x{202F}|\x{2007}/u', ' ', $string);

    $string = preg_replace('/[^\P{C}\t\n\r]/u', '', $string);

    $string = preg_replace('/[ \t]+/u', ' ', $string);
    $string = preg_replace("/\n{3,}/u", "\n\n", $string);

    return trim($string);
}

function addFormattedDetailText($unitedText, string $text): void
{
    $text = trim(strip_tags($text));
    if ($text === '') return;

    // Ловим и с ":" и без
    $labels = ['Материал', 'Метод', 'Минимальная партия'];

    // Разбиваем по словам-меткам, двоеточие не теряем (добавим сами)
    $pattern = '~(' . implode('|', array_map(fn($s) => preg_quote($s, '~'), $labels)) . ')\s*:?\s*~u';

    $fontNormal = ['bold' => false, 'name' => 'Inter', 'size' => 11];
    $fontBold = ['bold' => true, 'name' => 'Inter', 'size' => 11];

    // Если меток нет — всё равно "пробелы" (2 переноса) + текст
    if (!preg_match($pattern, $text)) {
        $unitedText->addTextBreak();
        // $unitedText->addTextBreak();
        $unitedText->addText($text, $fontNormal);
        return;
    }

    $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    foreach ($parts as $part) {
        $partTrim = trim($part);
        if ($partTrim === '') continue;

        if (in_array($partTrim, $labels, true)) {
            $unitedText->addTextBreak();
            // $unitedText->addTextBreak();
            if ($partTrim === 'Метод') {
                $unitedText->addText($partTrim . ' ', $fontBold);
            } else {
                $unitedText->addText($partTrim . ': ', $fontBold);
            }
        } else {
            $unitedText->addText($partTrim . ' ', $fontNormal);
        }
    }
}
