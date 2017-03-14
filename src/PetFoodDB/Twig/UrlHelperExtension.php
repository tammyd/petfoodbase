<?php


namespace PetFoodDB\Twig;



class UrlHelperExtension extends \Twig_Extension
{


    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('url', [$this, 'urlHelper'], ['is_safe'=>[true]]),
        ];
    }


    public function urlHelper($url, $text, $target="_self", $noFollow=false, $class="" ) {

        $template = "<a href='%s' target='%s' %s %s>%s</a>";
        $class = $class ? sprintf("class='%s'", $class) : "";
        $rel = $noFollow ? "rel='nofollow'" : "";

        return sprintf($template, $url, $target, $class, $rel, $text);

    }


}
