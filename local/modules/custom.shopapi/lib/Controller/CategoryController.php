<?php

namespace Custom\ShopApi\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Custom\ShopApi\Service\CategoryService;

/**
 * Контроллер для работы с категориями каталога
 */
class CategoryController extends Controller
{
    private CategoryService $categoryService;

    public function __construct($request = null)
    {
        parent::__construct($request);
        $this->categoryService = new CategoryService();
    }

    /**
     * Получить список категорий
     *
     * @return array
     */
    public function listAction(): array
    {
        try {
            $categories = $this->categoryService->getCategories();

            return [
                'success' => true,
                'data' => $categories,
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
     * Получить категорию по ID
     *
     * @param int $id ID категории
     * @return array
     */
    public function getAction(int $id): array
    {
        try {
            $category = $this->categoryService->getCategoryById($id);

            if (!$category) {
                $this->addError(new Error('Категория не найдена'));
                return [
                    'success' => false,
                    'error' => 'Категория не найдена',
                ];
            }

            return [
                'success' => true,
                'data' => $category,
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
