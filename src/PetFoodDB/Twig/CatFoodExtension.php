<?php


namespace PetFoodDB\Twig;


use PetFoodDB\Model\PetFood;

class CatFoodExtension extends \Twig_Extension
{
    protected $path;
    protected $baseUrl;
    protected $dryFoodPlaceholderImg;
    protected $wetFoodPlaceholderImg;


    public function setBaseUrl($baseUrl) {
        $this->baseUrl = $baseUrl;
        return $this;
    }


    public function setProductPath($path) {
        $this->path = $path;
        return $this;
    }

    public function setDryFoodPlaceholderImg($img) {
        $this->dryFoodPlaceholderImg = $img;
        return $this;
    }

    public function setWetFoodPlaceholderImg($img) {
        $this->wetFoodPlaceholderImg = $img;
        return $this;
    }


    public function getName()
    {
        return "catfood";
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('catfood_image', [$this, 'imageUrl'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('catfood_image_max', [$this, 'imageUrlMax'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('catfood_url', [$this, 'catfoodUrl'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('brand_url', [$this, 'brandUrl'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('paws', [$this, 'paws'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('catfood_amazon_url', [$this, 'amazonUrl'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('pinterest', [$this, 'pinterestData'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('overall_rating', [$this, 'overallRating'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('brand_rating', [$this, 'brandRating'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('price_per_oz', [$this, 'priceOunceSummary'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('chewy_url', [$this, 'chewyAffiliateUrl'], ['is_safe'=>[true]]),
        ];
    }

    public function catfoodUrl(PetFood $catfood = null)
    {
        if (!$catfood) {
            return "";
        }

        return sprintf($this->path, $catfood->getProductPath());
    }

    public function priceOunceSummary(PetFood $product, $pricePerOz) {
        $char = "$";
        $score = 0;

        if (!$pricePerOz) {
            return "n/a";
        }

        if ($product->getIsWetFood()) {

            if ($pricePerOz < 0.15) {
                $score = 1;
            } elseif ($pricePerOz < 0.25) {
                $score = 2;
            } else if ($pricePerOz < 0.35) {
                $score = 3;
            } else if ($pricePerOz < 0.50) {
                $score = 4;
            } else {
                $score = 5;
            }
        } else {
            if ($pricePerOz < 0.1) {
                $score = 1;
            } elseif ($pricePerOz < 0.20) {
                $score = 2;
            } else if ($pricePerOz < 0.30) {
                $score = 3;
            } else if ($pricePerOz < 0.40) {
                $score = 4;
            } else {
                $score = 5;
            }

        }

        return str_repeat($char, $score);
    }

    public function chewyAffiliateUrl($chewyUrl) {
        $template = "http://tracking.chewy.com/aff_c?offer_id=4&aff_id=4635&url=%s";
        $url = urlencode($chewyUrl);
        return sprintf($template, $url);
    }

    public function amazonUrl(PetFood $catfood, $target = "_blank", $classes = "", $text = null, $eventType='link') {

        $text = $text ? $text : $catfood->getDisplayName();
        $eventLabel = $catfood->getDisplayName();
        if (!$catfood->getPurchaseUrl()) {
            return $text;
        } else {
            $url = $catfood->getPurchaseUrl();
        }

        $classes = $classes . " amazon-product";
        
        return sprintf(
            '<a class="amazon-product" data-type="%s" data-label="%s"
                rel="nofollow" 
                href="%s" target="%s" 
                class="%s">%s</a>'
            , $eventType, $eventLabel, $url, $target, $classes, $text);

    }


    public function imageUrl(PetFood $catfood, $size=null) {
        
        if ($catfood->getImageUrl()) {
            $url =  $size ? $catfood->getResizedImageUrl($size) : $catfood->getImageUrl();
        } else if ($catfood->getIsDryFood()) {
            $url =  $this->dryFoodPlaceholderImg;
        } else {
            $url =  $this->wetFoodPlaceholderImg;
        }

        $url = $this->makeAbsoluteUrl($url);
        
        return $url;
    }

    public function makeAbsoluteUrl($url) {

        $isRelative = strpos($url, 'http') === false;
        if ($isRelative) {
            $url = sprintf("%s%s", $this->baseUrl, $url);
            $url = str_replace('///', '//', $url);
        }

        return $url;
    }

    public function imageUrlMax(PetFood $catfood, $size=null) {

        $img = $this->imageUrl($catfood, $size);

        //if SL[0-9]+_ update the number to 1200
        $img = preg_replace("/SL[0-9]+/", 'SL1600', $img);

        //if basename starts with 150_, drop it.
        $img = preg_replace("/\/150_/", "\/", $img);

        return $img;


    }

    public function brandUrl(PetFood $catfood) {

        $brand = strtolower($catfood->getBrand());
        return sprintf("/brand/%s", $brand);
    }
    

    public function paws($number, $class = null) {
        $html = "";
        for ($i=0; $i<$number; $i++) {
            $html .= "<i class='fa fa-paw $class' aria-hidden='true'></i>";
        }

        return $html;
    }

    public function pinterestData(PetFood $catFood) {

        $media = $this->imageUrl($catFood);
        //some image hacks for the best image

        //get the original image we downloaded, not the resized one
        if (strpos($media, "150_") !== false ) {
            $media =  $this->baseUrl . preg_replace("/150_/", "", $media); //downloaded, resized media
        } elseif (strpos($media, "_SL160_") !== false) {
            $media = preg_replace("/_SL160_/", "_SL300_", $media); //amazon media
        }


        $data = [
            "data-pin-url" => $this->baseUrl . $this->catfoodUrl($catFood),
            "data-pin-media" => $media,
            "data-pin-description" => "CatFoodDB Review: " . $catFood->getDisplayName()
        ];

        $tags = "";
        foreach ($data as $key=>$value) {
            $tags .= sprintf("%s=\"%s\" ", $key, $value);
        }


        return $tags;
    }

    public function overallRating(PetFood $catfood, $noun = "product") {

        $nutScore = $catfood->getExtraData('stats')['nutrition_rating'];
        $ingScore = $catfood->getExtraData('stats')['ingredients_rating'];
        $score = $nutScore + $ingScore;

        $class = "";
        $text = "";

        switch ($score) {
            case 1:case 2: case 3: case 4:
                $text =  "a significantly below average";
                $class = "text-danger";
                break;
            case 5:
                $text = "a below average";
                $class = "text-warning";
                break;
            case 6:
                $text = "an average";
                $class = "text-muted";
                break;
            case 7:
                $text =  "an above average";
                $class = "text-primary";
                break;
            case 8:case 9:case 10:
                $text = "a significantly above average";
                $class = "text-orig-success";
                break;
        }


        return sprintf("<span class='%s'>%s %s</span>", $class, $text, $noun);

    }

    public function brandRating($brandRating, $prefix="", $postfix="") {
        if ($brandRating < 5) {
            $text =  "a significantly below average";
            $class = "text-sbavg";
        } elseif ($brandRating < 5.75) {
            $text = "a below average";
            $class = "text-bavg";
        } elseif ($brandRating < 7.25) {
            $text = "an average";
            $class = "text-avg";
        } elseif ($brandRating < 8) {
            $text = "an above average";
            $class = "text-aavg";
        } elseif ($brandRating <=10) {
            $text = "a significantly above average";
            $class = "text-saavg";
        }

        $text = trim(sprintf("%s %s %s", $prefix, $text, $postfix));
        $rv = trim(sprintf("<span class='%s'>%s</span>", $class, $text));
        return $rv;

    }


}
