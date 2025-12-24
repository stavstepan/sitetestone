<?php
/**
 * Файл подключения модуля custom.shopapi
 */

defined('B_PROLOG_INCLUDED') || die();

\Bitrix\Main\Loader::registerAutoLoadClasses(
    'custom.shopapi',
    [
        'Custom\ShopApi\Controller\CategoryController' => 'lib/Controller/CategoryController.php',
        'Custom\ShopApi\Controller\ProductController' => 'lib/Controller/ProductController.php',
        'Custom\ShopApi\Service\CategoryService' => 'lib/Service/CategoryService.php',
        'Custom\ShopApi\Service\ProductService' => 'lib/Service/ProductService.php',
        'Custom\ShopApi\Service\ExportService' => 'lib/Service/ExportService.php',
    ]
);
