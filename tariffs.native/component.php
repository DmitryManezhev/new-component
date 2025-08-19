<?php
/**
 * Компонент тарифов с исправленной логикой корзины
 * /local/components/custom/tariffs.native/component.php
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// подключаем модули
if (!CModule::IncludeModule("iblock")) {
    ShowError("Модуль информационных блоков не установлен");
    return;
}
if (!CModule::IncludeModule("catalog") || !CModule::IncludeModule("sale")) {
    ShowError("Модули интернет-магазина не установлены");
    return;
}

// параметры
$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
$arParams["ELEMENT_COUNT"] = intval($arParams["ELEMENT_COUNT"]) ?: 20;
$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]) ?: 3600;

// корзина
$arParams["ACTION_VARIABLE"] = trim($arParams["ACTION_VARIABLE"]) ?: "action";
$arParams["PRODUCT_ID_VARIABLE"] = trim($arParams["PRODUCT_ID_VARIABLE"]) ?: "id";
$arParams["PRODUCT_QUANTITY_VARIABLE"] = trim($arParams["PRODUCT_QUANTITY_VARIABLE"]) ?: "quantity";

if ($arParams["IBLOCK_ID"] <= 0) {
    ShowError("Не указан инфоблок");
    return;
}

// действия с корзиной
$actionVar = $arParams["ACTION_VARIABLE"];
$productIdVar = $arParams["PRODUCT_ID_VARIABLE"];
$quantityVar = $arParams["PRODUCT_QUANTITY_VARIABLE"];

if (!empty($_REQUEST[$actionVar]) && in_array($_REQUEST[$actionVar], ["ADD_TO_BASKET", "UPDATE_BASKET", "UPDATE_QUANTITY", "REMOVE_FROM_BASKET"])) {
    $productID = intval($_REQUEST[$productIdVar]);
    $quantity = intval($_REQUEST[$quantityVar]) ?: 1;
    $action = $_REQUEST[$actionVar];

    if ($action === 'UPDATE_QUANTITY') {
        $quantity = intval($_REQUEST[$quantityVar]);
    }

    if ($productID > 0) {
        $rsElement = CIBlockElement::GetByID($productID);
        if ($arElement = $rsElement->GetNext()) {
            try {
                $siteId = SITE_ID;
                $fuser = Bitrix\Sale\Fuser::getId();
                $basket = Bitrix\Sale\Basket::loadItemsForFUser($fuser, $siteId);
                $item = $basket->getExistsItem('catalog', $productID);

                switch ($action) {
                    case "ADD_TO_BASKET":
                        if ($item) {
                            $newQuantity = $item->getQuantity() + $quantity;
                            $item->setField('QUANTITY', $newQuantity);
                        } else {
                            $item = $basket->createItem('catalog', $productID);
                            $item->setFields([
                                'QUANTITY' => $quantity,
                                'CURRENCY' => 'RUB',
                                'LID' => $siteId,
                                'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
                            ]);
                        }
                        break;
                    case "UPDATE_BASKET":
                        if ($item) $item->setField('QUANTITY', $quantity);
                        break;
                    case "UPDATE_QUANTITY":
                        if ($item) {
                            $newQuantity = $item->getQuantity() + $quantity;
                            if ($newQuantity <= 0) $item->delete();
                            else $item->setField('QUANTITY', $newQuantity);
                        }
                        break;
                    case "REMOVE_FROM_BASKET":
                        if ($item) $item->delete();
                        break;
                }

                $basket->save();
                if ($_REQUEST["ajax"] == "Y") {
                    echo CUtil::PhpToJSObject(["status" => "success"]);
                    die();
                } else {
                    LocalRedirect($APPLICATION->GetCurPageParam("success=Y", ["action", "id", "quantity"]));
                }

            } catch (Exception $e) {
                if ($_REQUEST["ajax"] == "Y") {
                    echo CUtil::PhpToJSObject(["status" => "error", "message" => $e->getMessage()]);
                    die();
                } else {
                    ShowError($e->getMessage());
                }
            }
        }
    }
}

// определяем страницу компонента
$arResult["COMPONENT_PAGE"] = "list";
$elementCode = $_REQUEST["ELEMENT_CODE"] ? trim($_REQUEST["ELEMENT_CODE"]) : "";
$elementId = intval($_REQUEST["ELEMENT_ID"]);
if (!empty($elementCode) || $elementId > 0) $arResult["COMPONENT_PAGE"] = "detail";

// корзина для проверки состояния
$arResult["BASKET_ITEMS"] = [];
try {
    $siteId = SITE_ID;
    $fuser = Bitrix\Sale\Fuser::getId();
    $basket = Bitrix\Sale\Basket::loadItemsForFUser($fuser, $siteId);
    foreach ($basket->getBasketItems() as $basketItem) {
        if ($basketItem->getQuantity() > 0) {
            $productId = $basketItem->getProductId();
            $arResult["BASKET_ITEMS"][$productId] = [
                'PRODUCT_ID' => $productId,
                'QUANTITY' => $basketItem->getQuantity(),
                'PRICE' => $basketItem->getPrice(),
                'SUM' => $basketItem->getFinalPrice(),
                'NAME' => $basketItem->getField('NAME')
            ];
        }
    }
} catch (Exception $e) {}

// получаем список товаров
if ($arResult["COMPONENT_PAGE"] == "list") {
    $arResult["ITEMS"] = [];
    $arSelect = ["ID","NAME","CODE","PREVIEW_TEXT","PREVIEW_PICTURE","DETAIL_PAGE_URL"];
    $arFilter = ["IBLOCK_ID"=>$arParams["IBLOCK_ID"],"ACTIVE"=>"Y"];

    $rsElements = CIBlockElement::GetList(
        ["SORT"=>"ASC","NAME"=>"ASC"],
        $arFilter,
        false,
        ["nTopCount"=>$arParams["ELEMENT_COUNT"]],
        $arSelect
    );

    while ($arElement = $rsElements->GetNext()) {
        // свойства
        $arElement["PROPERTIES"] = [];
        $rsProps = CIBlockElement::GetProperty($arParams["IBLOCK_ID"], $arElement["ID"]);
        while ($arProp = $rsProps->GetNext()) {
            if (!empty($arProp["VALUE"]) && in_array($arProp["CODE"], $arParams["PROPERTY_CODE"])) {
                if ($arProp["PROPERTY_TYPE"] == "L" && isset($arProp["VALUE_ENUM"])) {
                    $arProp["DISPLAY_VALUE"] = $arProp["VALUE_ENUM"];
                } elseif ($arProp["PROPERTY_TYPE"] == "E" && intval($arProp["VALUE"]) > 0) {
                    $linked = CIBlockElement::GetByID($arProp["VALUE"])->GetNext();
                    $arProp["DISPLAY_VALUE"] = $linked ? $linked["NAME"] : $arProp["VALUE"];
                } else {
                    $arProp["DISPLAY_VALUE"] = $arProp["VALUE"];
                }
                $arElement["PROPERTIES"][$arProp["CODE"]] = $arProp;
            }
        }

        // картинки
        $arElement["PREVIEW_PIC"] = "";
        if ($arElement["PREVIEW_PICTURE"] > 0) {
            $arFile = CFile::GetFileArray($arElement["PREVIEW_PICTURE"]);
            if ($arFile) $arElement["PREVIEW_PIC"] = $arFile["SRC"];
        }

        // цена
        $arElement["PRICE"] = 0;
        $arElement["PRICE_FORMATTED"] = "";
        $arPrice = CPrice::GetList([], ["PRODUCT_ID"=>$arElement["ID"],"CATALOG_GROUP_CODE"=>"BASE"])->Fetch();
        if ($arPrice) {
            $arElement["PRICE"] = floatval($arPrice["PRICE"]);
            $arElement["PRICE_FORMATTED"] = number_format($arElement["PRICE"],0,"."," ") . " ₽";
        }

        $arProduct = CCatalogProduct::GetByID($arElement["ID"]);
        $arElement["CATALOG_QUANTITY"] = $arProduct ? intval($arProduct["QUANTITY"]) : 0;
        $arElement["CAN_BUY"] = $arProduct && $arElement["PRICE"] > 0;

        // корзина
        if (isset($arResult["BASKET_ITEMS"][$arElement["ID"]])) {
            $arElement["IN_BASKET"] = true;
            $arElement["BASKET_QUANTITY"] = $arResult["BASKET_ITEMS"][$arElement["ID"]]["QUANTITY"];
        } else {
            $arElement["IN_BASKET"] = false;
            $arElement["BASKET_QUANTITY"] = 0;
        }

        $arElement["DETAIL_PAGE_URL"] = $APPLICATION->GetCurPage() . "?ELEMENT_CODE=" . $arElement["CODE"];
        $arResult["ITEMS"][] = $arElement;
    }
}

// детальная страница
if ($arResult["COMPONENT_PAGE"] == "detail") {
    $arFilter = ["IBLOCK_ID"=>$arParams["IBLOCK_ID"],"ACTIVE"=>"Y"];
    if (!empty($elementCode)) $arFilter["CODE"] = $elementCode;
    elseif ($elementId > 0) $arFilter["ID"] = $elementId;

    $arSelect = ["ID","NAME","CODE","PREVIEW_TEXT","DETAIL_TEXT","PREVIEW_PICTURE","DETAIL_PICTURE"];
    $rsElement = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
    if ($arElement = $rsElement->GetNext()) {
        $arElement["PROPERTIES"] = [];
        $rsProps = CIBlockElement::GetProperty($arParams["IBLOCK_ID"], $arElement["ID"]);
        while ($arProp = $rsProps->GetNext()) {
            if (!empty($arProp["VALUE"]) && in_array($arProp["CODE"], $arParams["PROPERTY_CODE"])) {
                if ($arProp["PROPERTY_TYPE"] == "L" && isset($arProp["VALUE_ENUM"])) $arProp["DISPLAY_VALUE"] = $arProp["VALUE_ENUM"];
                elseif ($arProp["PROPERTY_TYPE"] == "E" && intval($arProp["VALUE"]) > 0) {
                    $linked = CIBlockElement::GetByID($arProp["VALUE"])->GetNext();
                    $arProp["DISPLAY_VALUE"] = $linked ? $linked["NAME"] : $arProp["VALUE"];
                } else $arProp["DISPLAY_VALUE"] = $arProp["VALUE"];
                $arElement["PROPERTIES"][$arProp["CODE"]] = $arProp;
            }
        }

        // картинки
        $arElement["PREVIEW_PIC"] = $arElement["DETAIL_PIC"] = "";
        if ($arElement["PREVIEW_PICTURE"] > 0) {
            $arFile = CFile::GetFileArray($arElement["PREVIEW_PICTURE"]);
            if ($arFile) $arElement["PREVIEW_PIC"] = $arFile["SRC"];
        }
        if ($arElement["DETAIL_PICTURE"] > 0) {
            $arFile = CFile::GetFileArray($arElement["DETAIL_PICTURE"]);
            if ($arFile) $arElement["DETAIL_PIC"] = $arFile["SRC"];
        }

        // цена и количество
        $arElement["PRICE"] = 0;
        $arElement["PRICE_FORMATTED"] = "";
        $arPrice = CPrice::GetList([], ["PRODUCT_ID"=>$arElement["ID"],"CATALOG_GROUP_CODE"=>"BASE"])->Fetch();
        if ($arPrice) {
            $arElement["PRICE"] = floatval($arPrice["PRICE"]);
            $arElement["PRICE_FORMATTED"] = number_format($arElement["PRICE"],0,"."," ") . " ₽";
        }
        $arProduct = CCatalogProduct::GetByID($arElement["ID"]);
        $arElement["CATALOG_QUANTITY"] = $arProduct ? intval($arProduct["QUANTITY"]) : 0;
        $arElement["CAN_BUY"] = $arProduct && $arElement["PRICE"] > 0;

        if (isset($arResult["BASKET_ITEMS"][$arElement["ID"]])) {
            $arElement["IN_BASKET"] = true;
            $arElement["BASKET_QUANTITY"] = $arResult["BASKET_ITEMS"][$arElement["ID"]]["QUANTITY"];
        } else {
            $arElement["IN_BASKET"] = false;
            $arElement["BASKET_QUANTITY"] = 0;
        }

        $arResult["ITEM"] = $arElement;

        if ($arParams["SET_TITLE"] == "Y") $APPLICATION->SetTitle($arElement["NAME"]);
        if ($arParams["ADD_SECTIONS_CHAIN"] == "Y") {
            $APPLICATION->AddChainItem("Тарифы", $APPLICATION->GetCurPageParam("", ["ELEMENT_CODE"]));
            $APPLICATION->AddChainItem($arElement["NAME"]);
        }
    } else {
        CHTTP::SetStatus("404 Not Found");
        @define("ERROR_404", "Y");
        return;
    }
}

$this->IncludeComponentTemplate($arResult["COMPONENT_PAGE"]);
?>
