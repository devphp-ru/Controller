<?php
declare(strict_types=1);

namespace app\controllers\admin;

use app\helpers\Helper;
use app\helpers\Html;
use app\models\brand\BrandService;
use cms\App;

class BrandController extends AppController
{
    private string $template = '/admin/include/brand_li';
    private string $repositoryName = 'PDOBrandRepository';

    public function actionIndex()
    {
        $brandRepository = $this->repositoryFactory->getObject($this->repositoryName);
        $brands = BrandService::createBrandService($brandRepository)->findAll();

        $title = 'Категории каталога марки';
        $this->render(compact(
            'title', 'brands'
        ));
    }

    /**
     * добавление нового бренда (категории)
     */
    public function actionAdd()
    {
        if ($this->request->isAjax() && $this->request->method('POST')) {
            $this->session->setFlash([
                'error' => true,
                'message' => 'Ошибка сохранения.',
            ]);
            $brandRepository = $this->repositoryFactory->getObject($this->repositoryName);
            $brandService = BrandService::createBrandService($brandRepository);
            if ($brandService->createBrand()) {
                $brands = $brandService->findAll();
                $this->session->setFlash([
                    'error' => false,
                    'message' => 'Модель добавлена в каталог.',
                    'html' => Html::getTpl($this->template, compact('brands')),
                ]);
            }
            $this->response->getStatusCode();
            die (json_encode($this->session->getFlash(), JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * находит марку для редактирования
     */
    public function actionFind()
    {
        if ($this->request->isAjax() && $this->request->method('POST')) {
            $this->session->setFlash([
                'error' => true,
                'message' => 'Ошибка запроса.',
            ]);
            $brandRepository = $this->repositoryFactory->getObject($this->repositoryName);
            $brandService = BrandService::createBrandService($brandRepository);
            if ($row = $brandService->findOne()) {
                $this->session->setFlash([
                    'error' => false,
                    'message' => 'Редактирование марки.',
                    'brand' => Helper::checkMatches($row, [
                        'id', 'name', 'image', 'm_title', 'm_keys', 'm_desc',
                    ]),
                ]);
            }
            $this->response->getStatusCode();
            die (json_encode($this->session->getFlash(), JSON_UNESCAPED_UNICODE));
        }
    }

    public function actionUpdate()
    {
        if ($this->request->isAjax() && $this->request->method('POST')) {
            $this->session->setFlash([
                'error' => true,
                'message' => 'Ошибка сохранения.',
            ]);
            $storageRepository = $this->repositoryFactory->getObject($this->repositoryName);
            $brandService = BrandService::createBrandService($storageRepository);
            if ($brandService->updateBrand()) {
                $brands = $brandService->findAll();
                $this->session->setFlash([
                    'error' => false,
                    'message' => 'Изменения сохранены.',
                    'html' => Html::getTpl($this->template, compact('brands')),
                ]);
            }
            $this->response->getStatusCode();
            die (json_encode($this->session->getFlash(), JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * удаление марки, если есть изображение оно тоже удаляется
     */
    public function actionDelete()
    {
        if ($this->request->isAjax() && $this->request->method('POST')) {
            $this->session->setFlash([
                'error' => true,
                'message' => 'Ошибка удаления.',
            ]);
            $storageRepository = $this->repositoryFactory->getObject($this->repositoryName);
            $brandService = BrandService::createBrandService($storageRepository);
            $image = $brandService->findOne();
            if ($brandService->deleteBrand()) {
                if (!empty($image['image'])) {
                    App::$DI->uploaded->removeImage($image['image']);
                }
                $this->session->setFlash([
                    'error' => false,
                    'message' => 'Марка удалена.',
                ]);
            }
            $this->response->getStatusCode();
            die (json_encode($this->session->getFlash(), JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * добавления изображения к бренду
     */
    public function actionAddImage()
    {
        if ($this->request->get('upload')) {
            $name = $this->request->post('name');
            $filePath = App::$DI->uploaded->saveImageMark($name);
            $return = ['image' => $filePath];
            $this->response->getStatusCode();
            die(json_encode($return, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * удаление изображения у бернда, как при добалвении, так и при редактировании
     */
    public function actionDeleteImage()
    {
        if ($this->request->isAjax() && $this->request->method('POST')) {
            $this->session->setFlash([
                'error' => true,
                'message' => 'Ошибка удаления.',
            ]);

            if (App::$DI->uploaded->deleteImage()) {
                if (!empty($this->request->post('id'))) {
                    $brandRepository = $this->repositoryFactory->getObject($this->repositoryName);
                    $brandService = BrandService::createBrandService($brandRepository);
                    $brandService->updateImageBrand();
                }
                $this->session->setFlash([
                    'error' => false,
                    'message' => 'Файл удален.',
                ]);
            }
            $this->response->getStatusCode();
            die (json_encode($this->session->getFlash(), JSON_UNESCAPED_UNICODE));
        }
    }
}
