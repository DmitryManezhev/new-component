<?php
/**
 * Параметры компонента тарифов мониторинга
 * /local/components/custom/tariffs.native/.parameters.php
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();


$arIBlocks = [];
if (CModule::IncludeModule('iblock')) {
    $rsIBlock = CIBlock::GetList(['SORT' => 'ASC'], ['ACTIVE' => 'Y']);
    while ($arr = $rsIBlock->Fetch()) {
        $arIBlocks[$arr['ID']] = '[' . $arr['ID'] . '] ' . $arr['NAME'];    
    }
}


$arPriceTypes = [];
if (CModule::IncludeModule('catalog')) {
    $rsPrice = CCatalogGroup::GetList(['SORT' => 'ASC'], ['ACTIVE' => 'Y']);
    while ($arr = $rsPrice->Fetch()) {
        $arPriceTypes[$arr['NAME']] = '[' . $arr['NAME'] . '] ' . $arr['NAME_LANG'];
    }
}


$arProperties = [];
if (!empty($arCurrentValues['IBLOCK_ID']) && (int)$arCurrentValues['IBLOCK_ID'] > 0) {
    $rsProp = CIBlockProperty::GetList(
        ['SORT' => 'ASC'], 
        ['IBLOCK_ID' => (int)$arCurrentValues['IBLOCK_ID'], 'ACTIVE' => 'Y']
    );
    while ($arr = $rsProp->Fetch()) {
        $arProperties[$arr['CODE']] = '[' . $arr['CODE'] . '] ' . $arr['NAME'];
    }
}

$arComponentParameters = [
    'GROUPS' => [
        'SETTINGS' => ['NAME' => 'Основные настройки'],
        'PRICES' => ['NAME' => 'Цены'],
        'BASKET' => ['NAME' => 'Корзина'],
        'VISUAL' => ['NAME' => 'Внешний вид'],
        'SEO' => ['NAME' => 'SEO настройки'],
        'CACHE' => ['NAME' => 'Кеширование'],
    ],
    'PARAMETERS' => [
        // Основные настройки
        'IBLOCK_ID' => [
            'PARENT' => 'SETTINGS',
            'NAME' => 'Инфоблок',
            'TYPE' => 'LIST',
            'VALUES' => $arIBlocks,
            'DEFAULT' => '44',
            'REFRESH' => 'Y'
        ],
        'ELEMENT_COUNT' => [
            'PARENT' => 'SETTINGS',
            'NAME' => 'Количество элементов на странице',
            'TYPE' => 'STRING',
            'DEFAULT' => '20'
        ],
        'LINE_ELEMENT_COUNT' => [
            'PARENT' => 'SETTINGS',
            'NAME' => 'Количество элементов в строке',
            'TYPE' => 'LIST',
            'VALUES' => [
                '1' => '1', 
                '2' => '2', 
                '3' => '3', 
                '4' => '4', 
                '5' => '5'
            ],
            'DEFAULT' => '3'
        ],
        'PROPERTY_CODE' => [
            'PARENT' => 'SETTINGS',
            'NAME' => 'Свойства для отображения',
            'TYPE' => 'LIST',
            'MULTIPLE' => 'Y',
            'VALUES' => $arProperties,
            'COLS' => 25
        ],
        'SORT_BY1' => [
            'PARENT' => 'SETTINGS',
            'NAME' => 'Поле для сортировки',
            'TYPE' => 'LIST',
            'VALUES' => [
                'ID' => 'ID',
                'NAME' => 'Название',
                'SORT' => 'Индекс сортировки',
                'TIMESTAMP_X' => 'Дата изменения',
                'CREATED' => 'Дата создания'
            ],
            'DEFAULT' => 'SORT'
        ],
        'SORT_ORDER1' => [
            'PARENT' => 'SETTINGS',
            'NAME' => 'Направление сортировки',
            'TYPE' => 'LIST',
            'VALUES' => [
                'ASC' => 'По возрастанию',
                'DESC' => 'По убыванию'
            ],
            'DEFAULT' => 'ASC'
        ],
        
        // Цены
        'PRICE_CODE' => [
            'PARENT' => 'PRICES',
            'NAME' => 'Тип цены',
            'TYPE' => 'LIST',
            'MULTIPLE' => 'Y',
            'VALUES' => $arPriceTypes,
            'DEFAULT' => ['BASE']
        ],
        'CURRENCY_ID' => [
            'PARENT' => 'PRICES',
            'NAME' => 'Валюта',
            'TYPE' => 'STRING',
            'DEFAULT' => 'RUB'
        ],
        'SHOW_PRICE_COUNT' => [
            'PARENT' => 'PRICES',
            'NAME' => 'Количество для расчета цены',
            'TYPE' => 'STRING',
            'DEFAULT' => '1'
        ],
        
        // Корзина
        'BASKET_URL' => [
            'PARENT' => 'BASKET',
            'NAME' => 'URL страницы корзины',
            'TYPE' => 'STRING',
            'DEFAULT' => '/personal/basket.php'
        ],
        'ACTION_VARIABLE' => [
            'PARENT' => 'BASKET',
            'NAME' => 'Имя переменной действия',
            'TYPE' => 'STRING',
            'DEFAULT' => 'action'
        ],
        'PRODUCT_ID_VARIABLE' => [
            'PARENT' => 'BASKET',
            'NAME' => 'Имя переменной ID товара',
            'TYPE' => 'STRING',
            'DEFAULT' => 'id'
        ],
        'PRODUCT_QUANTITY_VARIABLE' => [
            'PARENT' => 'BASKET',
            'NAME' => 'Имя переменной количества',
            'TYPE' => 'STRING',
            'DEFAULT' => 'quantity'
        ],
        
        // Внешний вид
        'MESS_BTN_ADD_TO_BASKET' => [
            'PARENT' => 'VISUAL',
            'NAME' => 'Текст кнопки "В корзину"',
            'TYPE' => 'STRING',
            'DEFAULT' => 'В корзину'
        ],
        'MESS_BTN_DETAIL' => [
            'PARENT' => 'VISUAL',
            'NAME' => 'Текст кнопки "Подробнее"',
            'TYPE' => 'STRING',
            'DEFAULT' => 'Подробнее'
        ],
        'SHOW_PREVIEW_TEXT' => [
            'PARENT' => 'VISUAL',
            'NAME' => 'Показывать описание',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y'
        ],
        'SHOW_PICTURES' => [
            'PARENT' => 'VISUAL',
            'NAME' => 'Показывать изображения',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y'
        ],
        
        // SEO
        'SET_TITLE' => [
            'PARENT' => 'SEO',
            'NAME' => 'Устанавливать заголовок страницы',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y'
        ],
        'ADD_SECTIONS_CHAIN' => [
            'PARENT' => 'SEO',
            'NAME' => 'Включать в цепочку навигации',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y'
        ],
        'SET_META_KEYWORDS' => [
            'PARENT' => 'SEO',
            'NAME' => 'Устанавливать ключевые слова',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N'
        ],
        'SET_META_DESCRIPTION' => [
            'PARENT' => 'SEO',
            'NAME' => 'Устанавливать описание страницы',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N'
        ],
        
        // Кеширование
        'CACHE_TYPE' => [
            'PARENT' => 'CACHE',
            'NAME' => 'Тип кеширования',
            'TYPE' => 'LIST',
            'VALUES' => [
                'A' => 'Авто + Управляемое',
                'Y' => 'Кешировать',
                'N' => 'Не кешировать'
            ],
            'DEFAULT' => 'A'
        ],
        'CACHE_TIME' => [
            'PARENT' => 'CACHE',
            'NAME' => 'Время кеширования (сек.)',
            'TYPE' => 'STRING',
            'DEFAULT' => '3600'
        ],
        'CACHE_FILTER' => [
            'PARENT' => 'CACHE',
            'NAME' => 'Кешировать при установленном фильтре',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N'
        ],
    ]
];

