<?php


namespace PetFoodDB\Twig;


use Slim\Views\Twig;

class ChowderedTwig extends Twig {

    protected $parserInstance;

    /**
     * Creates new TwigEnvironment if it doesn't already exist, and returns it.
     *
     * @return \Twig_Environment
     */
    public function getInstance()
    {
        if (!$this->parserInstance) {
            /**
             * Check if Twig_Autoloader class exists
             * otherwise include it.
             */
            if (!class_exists('\Twig_Autoloader')) {
                require_once $this->parserDirectory . '/Autoloader.php';
            }

            \Twig_Autoloader::register();
            $loader = new \Twig_Loader_Filesystem($this->getTemplateDirs());
            $this->parserInstance = new ChowderedTwigEnvironment(
                $loader,
                $this->parserOptions
            );

            foreach ($this->parserExtensions as $ext) {
                $extension = is_object($ext) ? $ext : new $ext;
                $this->parserInstance->addExtension($extension);
            }


        }

        return $this->parserInstance;
    }
    /**
     * Chowdered by changing visibility
     *
     * DEPRECATION WARNING! This method will be removed in the next major point release
     *
     * Get a list of template directories
     *
     * Returns an array of templates defined by self::$twigTemplateDirs, falls
     * back to Slim\View's built-in getTemplatesDirectory method.
     *
     * @return array
     **/
    protected function getTemplateDirs()
    {
        if (empty($this->twigTemplateDirs)) {
            return array($this->getTemplatesDirectory());
        }
        return $this->twigTemplateDirs;
    }
} 
