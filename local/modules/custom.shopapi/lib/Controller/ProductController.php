<?php

namespace Custom\ShopApi\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Custom\ShopApi\Service\ProductService;

/**
 * Контроллер для работы с товарами каталога
 */
class ProductController extends Controller
{
    private ProductService $productService;

    public function __construct($request = null)
    {
        parent::__construct($request);
        $this->productService = new ProductService();
    }

    /**
     * Получить список товаров в категории
     *
     * @param int $categoryId ID категории
     * @return array
     */
    public function listByCategoryAction(int $categoryId): array
    {
        try {
            $products = $this->productService->getProductsByCategory($categoryId);

            return [
                'success' => true,
                'data' => $products,
            ];
        } catch (\Exception $e) {
            $this->addError(new Error($e->getMessage()));
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить детальную информацию о товаре
     *
     * @param int $id ID товара
     * @return array
     */
    public function getAction(int $id): array
    {
        try {
            $product = $this->productService->getProductById($id);

            if (!$product) {
                $this->addError(new Error('Товар не найден'));
                return [
                    'success' => false,
                    'error' => 'Товар не найден',
                ];
            }

            return [
                'success' => true,
                'data' => $product,
            ];
        } catch (\Exception $e) {
            $this->addError(new Error($e->getMessage()));
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Настройка CORS
     */
    protected function processBeforeAction(\Bitrix\Main\Engine\Action $action)
    {
        header('Content-Type: application/json');
        return parent::processBeforeAction($action);
    }
}
