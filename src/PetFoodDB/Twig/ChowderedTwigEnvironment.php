<?php


namespace PetFoodDB\Twig;


use Twig_Environment;

class ChowderedTwigEnvironment extends Twig_Environment {

    protected function writeCacheFile($file, $content)
    {
        if (!is_dir(dirname($file))) {
            $old = umask(0002);
            mkdir(dirname($file),0777,true);
            umask($old);
        }

        parent::writeCacheFile($file, $content);
        chmod($file,0777);
    }

} 
