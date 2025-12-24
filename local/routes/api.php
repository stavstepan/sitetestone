<?php
/**
 * Настройка роутинга для REST API
 *
 * Этот файл должен быть подключен в local/php_interface/init.php:
 * require_once $_SERVER['DOCUMENT_ROOT'] . '/local/routes/api.php';
 */

use Bitrix\Main\Routing\RoutingConfigurator;
use Bitrix\Main\Routing\Controllers\PublicPageController;

return function (RoutingConfigurator $routes) {
    // Группа маршрутов для API
    $routes->prefix('api/v1')->group(function (RoutingConfigurator $routes) {

        // Маршруты для категорий
        $routes->prefix('categories')->group(function (RoutingConfigurator $routes) {
            // GET /api/v1/categories - список категорий
            $routes->get('/', [
                'controller' => \Custom\ShopApi\Controller\CategoryController::class,
                'action' => 'list'
            ])->name('api.categories.list');

            // GET /api/v1/categories/{id} - детальная информация о категории
            $routes->get('/{id}', [
                'controller' => \Custom\ShopApi\Controller\CategoryController::class,
                'action' => 'get'
            ])->where('id', '\d+')->name('api.categories.get');
        });

        // Маршруты для товаров
        $routes->prefix('products')->group(function (RoutingConfigurator $routes) {
            // GET /api/v1/products/category/{categoryId} - список товаров в категории
            $routes->get('/category/{categoryId}', [
                'controller' => \Custom\ShopApi\Controller\ProductController::class,
                'action' => 'listByCategory'
            ])->where('categoryId', '\d+')->name('api.products.listByCategory');

            // GET /api/v1/products/{id} - детальная информация о товаре
            $routes->get('/{id}', [
                'controller' => \Custom\ShopApi\Controller\ProductController::class,
                'action' => 'get'
            ])->where('id', '\d+')->name('api.products.get');
        });
    });
};
