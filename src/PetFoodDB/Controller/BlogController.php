<?php

namespace PetFoodDB\Controller;

use PetFoodDB\Blog\HypoAllergenic;
use PetFoodDB\Exceptions\ContentDoesNotExistException;
use PetFoodDB\Service\Blog;
use PetFoodDB\Controller\PageController;
use PetFoodDB\Traits\ArrayTrait;
use PetFoodDB\Controller\BaseController;

class BlogController extends PageController
{
    
    protected $blog;

    public function __construct(\Slim\Slim $app)
    {
        parent::__construct($app);
        $this->blog = $this->get('blog.service');
    }

    public function blogHomeAction() {
        $posts = $this->blog->getBlogPosts();
        $data = [
            'posts' => []
        ];
        foreach ($posts as $post) {
            $data['posts'][] = $this->buildPostData($post);
        }

        $this->render('blog.html.twig', $data);
    }



    public function postDraftAction($name) {


        if (!$this->getParameter('app.debug')) {
            $this->app->notFound();
        }
        
        $post = null;
        try {
            $post = $this->blog->getPost("/drafts/".$name);
        } catch (ContentDoesNotExistException $e) {
            $this->app->notFound();
        }

        $this->renderPost($post);
    }


    public function postAction($name) {

        $post = null;
        try {
            $post = $this->blog->getPost($name);
        } catch (ContentDoesNotExistException $e) {
            $this->app->notFound();
        }

        $this->renderPost($post);

    }

    public function renderPost($post) {
        $data = $this->buildPostData($post);
        $meta = $post->getYAML();

        $extraDataFunction = $this->getArrayValue($meta, 'serviceData', null);

        if ($extraDataFunction) {
            $service = $this->getContainer()->get($extraDataFunction);
            $service->setPost($post);
            $service->setPostMetaData($data);
            $callable = [$service, 'postData'];
            if (is_callable($callable)) {
                $data['extra'] = call_user_func($callable);
            } else {
                $this->getLogger()->error("$extraDataFunction was not callable!");
            }
        }

        $data = array_merge($data, $meta);

        $template = $this->getArrayValue($meta, 'template', 'post.html.twig');

//        dump($data); die();
        
        $this->render($template, $data);
    }

    public function buildPostData($post) {

        $meta = $post->getYAML();


        $helper = $this->getContainer()->get('catfood.url');
        $shareText = sprintf("%s %s", $meta['title'] , $this->getArrayValue($meta, 'hashtags', ""));

        $postData = [
            'title' => strip_tags($meta['title']),
            'slug' => $meta['slug'],
            'url' => "/blog/".$meta['slug'],
            'description' => $this->getArrayValue($meta, 'seo_description', ''),
            'image' => $helper->makeAbsoluteUrl($this->getArrayValue($meta, 'image')),
            'preview' => $helper->makeAbsoluteUrl($this->getArrayValue($meta, 'preview')),
            'date' => $this->getArrayValue($meta, 'date'),
            'meta' => $post->getYAML(),
            'html' => $post->getContent(),
            'isBestOf' => $meta['isBestOf']
        ];

        $data = [
            'post' => $postData,
            'title' => $postData['description'],
            'seo' => $this->seoService->getPostSEO($meta),
            'articleNavClass' => 'active',
            'shareText'  => urlencode($shareText),
            'shareImage' => urlencode($helper->makeAbsoluteUrl($this->getArrayValue($meta, 'image'))),
            'amazonQuery' => $this->getArrayValue($meta, 'amazonQuery', null)
        ];

        return $data;

    }


}
