<?php

namespace PetFoodDB\Controller;

use PetFoodDB\Traits\LoggerTrait;
use Symfony\Component\Filesystem\Filesystem;

class BaseController
{
    use LoggerTrait;

    protected $app;
    protected $seoService;

    public function __construct(\Slim\Slim &$app)
    {
        $this->app = $app;
        $this->seoService = $this->get('seo.service');

    }

    protected function getContainer()
    {
        return $this->app->container;
    }

    protected function getRequest()
    {
        return $this->app->request;
    }

    protected function getResponse()
    {
        return $this->app->response;
    }

    protected function get($service)
    {
        return $this->app->container[$service];
    }

    protected function getParameter($id)
    {
        return $this->app->config[$id];
    }

    protected function is404($msg = null)
    {
        if (!$msg) {
            $msg = "Not found";
        }
        $this->app->response->setStatus(404);
        $response = [
            'statusCode'=>404,
            'error'=>$msg
        ];

        $this->app->response->setBody(json_encode($response));
    }

    protected function getBaseSEO()
    {

        return $this->seoService->getBaseSEO();

    }

    protected function render($template, $data = []) {
        $defaultData = [
            'seo' => $this->getBaseSEO()
        ];
        $pageData = array_merge($defaultData, $data);
        $this->app->render($template, $pageData);
    }

    protected function isAdmin() {
        $admin = $this->app->request->headers->get($this->app->config['admin.header']);
        $adminValue = $this->app->config['admin.header.value'];

        if ($this->isDevEnv() &&  $admin && $admin == $adminValue) {
            return true;
        }
        return false;
    }

    protected function isDevEnv() {
        $env = getenv('APP_ENV');
        return strcmp($env, 'dev') == 0;
    }

    protected function templateExists($template) {
        $dir = $this->app->view->getTemplatesDirectory();

        $filename = "$dir/$template";
        $fs = new Filesystem();
        return $fs->exists($filename);
    }



}
