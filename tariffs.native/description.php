<?php
/**
 * Описание компонента тарифов мониторинга
 * /local/components/custom/tariffs.native/.description.php
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    "NAME" => Loc::getMessage("TARIFFS_COMPONENT_NAME"),
    "DESCRIPTION" => Loc::getMessage("TARIFFS_COMPONENT_DESCRIPTION"),
    "ICON" => "/images/icon.gif",
    "SORT" => 10,
    "PATH" => [
        "ID" => "custom",
        "NAME" => Loc::getMessage("TARIFFS_COMPONENT_PATH"),
        "CHILD" => [
            "ID" => "catalog",
            "NAME" => Loc::getMessage("TARIFFS_COMPONENT_CHILD_PATH"),
        ]
    ],
    "CACHE_PATH" => "Y",
    "COMPLEX" => "N",
];