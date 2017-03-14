<?php


namespace PetFoodDB\Service;


use PetFoodDB\Exceptions\ContentDoesNotExistException;
use Mni\FrontYAML\Document;
use Mni\FrontYAML\Parser;
use Symfony\Component\Filesystem\Filesystem;

class Blog
{
    protected $baseFilePath;
    protected $parser;
    protected $fs;

    public function __construct($filePath, Parser $parser, Filesystem $filesystem)
    {
        $this->baseFilePath = $filePath;
        $this->parser = $parser;
        $this->fs = $filesystem;
    }

    public function getFilename($path) {
        $path = $this->baseFilePath . "/$path";

        return str_replace('//','/' ,$path );
    }

    public function getPost($slug) {
        $path = $this->getPostDirectoryPath($slug);
        return $this->parse($path);
    }

    public function postExists($slug) {
        $path = $this->getPostDirectoryPath($slug);
        return $this->contentExists($path);
    }

    public function pageExists($slug) {
        $path = $this->getPagePath($slug);
        return $this->contentExists($path);
    }

    protected function getPostDirectory() {
        return "/posts";
    }

    protected function getPostDirectoryPath($slug) {
        return sprintf("%s/%s", $this->getPostDirectory(), $slug);
    }

    public function getPostUrl($slug) {
        return "/blog/$slug";
    }


    public function getBlogSlugs() {
        $cwd = getcwd();
        $blogDir = "../blog"; //@todo - make this configurable
        $postLocation = sprintf("%s%s", $blogDir, $this->getPostDirectory());
        chdir($postLocation);
        $files = glob("*.md");
        $slugs = [];
        foreach ($files as $file) {
            $slugs[] = substr($file, 0, -3);
        }
        chdir($cwd);
        return $slugs;
    }

    public function getBlogPosts() {
        $posts = [];
        $slugs = $this->getBlogSlugs();
        foreach ($slugs as $slug) {
            $posts[] = $this->getPost($slug);
        }

        $posts = $this->sortPostsByDateDesc($posts);

        return $posts;

    }

    protected function postDateSortDesc(Document $postA, Document $postB) {
        $a = $postA->getYAML()['date'];
        $b = $postB->getYAML()['date'];

        if ($a == $b) {
            return 0;
        }
        return ($a < $b) ? 1 : -1;
    }

    public function sortPostsByDateDesc(array $posts) {

        usort($posts, [$this, 'postDateSortDesc']);
        return $posts;

    }


    public function parse($path) {
        if (!$this->contentExists($path)) {
            $path = "$path.md";
            if (!$this->contentExists($path)) {
                throw new ContentDoesNotExistException($path);
            }
        }
        $filename = $this->getFilename($path);
        $contents = file_get_contents($filename);
        $document = $this->parser->parse($contents);
        return $document;
    }

    protected function contentExists($path) {
        $filename = $this->getFilename($path);
        return $this->fs->exists($filename);
    }


}
