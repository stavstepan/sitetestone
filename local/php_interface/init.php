<?php
/**
 * Инициализация пользовательских настроек
 */

defined('B_PROLOG_INCLUDED') || die();

// Подключение модуля
\Bitrix\Main\Loader::includeModule('custom.shopapi');

// Подключение роутов для API
$routesFile = $_SERVER['DOCUMENT_ROOT'] . '/local/routes/api.php';
if (file_exists($routesFile)) {
    \Bitrix\Main\Routing\Router::addRoutes(include $routesFile);
}
