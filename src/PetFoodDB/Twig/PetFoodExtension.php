<?php


namespace PetFoodDB\Twig;


use PetFoodDB\Model\PetFood;
use PetFoodDB\Service\BrandAnalysis;
use PetFoodDB\Traits\StringHelperTrait;

class PetFoodExtension extends \Twig_Extension
{
    protected $path;
    protected $baseUrl;
    protected $dryFoodPlaceholderImg;
    protected $wetFoodPlaceholderImg;

    use StringHelperTrait;


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
        return "petfood";
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('petfood_image', [$this, 'imageUrl'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('petfood_image_max', [$this, 'imageUrlMax'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('petfood_url', [$this, 'petfoodUrl'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('brand_url', [$this, 'brandUrl'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('paws', [$this, 'paws2'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('petfood_amazon_url', [$this, 'amazonUrl'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('overall_rating', [$this, 'overallRating'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('brand_rating', [$this, 'brandRating'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('price_per_oz', [$this, 'priceOunceSummary'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('chewy_url_old', [$this, 'chewyAffiliateUrl'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('chewy_url', [$this, 'chewyRedirectUrl'], ['is_safe'=>[true]]),
        ];
    }

    public function petfoodUrl(PetFood $petfood = null)
    {
        if (!$petfood) {
            return "";
        }

        return sprintf($this->path, $petfood->getProductPath());
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

    public function chewyAffiliateUrl($chewyUrl, $source = null) {
        if (is_string($source)) {
            $template = "https://prf.hn/click/camref:1011l4bA9/pubref:%s/destination:%s";
        } else {
            $template = "https://prf.hn/click/camref:1011l4bA9/destination:%s";
        }


        $url = urlencode($chewyUrl);


        $result = is_string($source) ? sprintf($template, $source, $url) : sprintf($template, $url);

        return $result;
    }

    public function chewyRedirectUrl($chewyUrl, $source = null) {

        $chewyPath = str_replace("https://www.chewy.com/", "", $chewyUrl);
        $chewyPath = str_replace("https://chewy.com/", "", $chewyPath);
        $chewyPath = urlencode($chewyPath);

        $source = $this->slugify($this->cleanText($source));
        $punctuation = ['.', ',', '?', '.'];
        $chewyPath = str_replace($punctuation, "", $chewyPath);

        $source = $source ? $source : "none";
        $url = sprintf("/chewy/%s/%s", $source, $chewyPath);
        $url = str_replace('//', '/', $url);


        return $url;
    }

    public function amazonUrl(PetFood $petFood, $target = "_blank", $classes = "", $text = null, $eventType='link') {

        $text = $text ? $text : $petFood->getDisplayName();
        $eventLabel = $petFood->getDisplayName();
        if (!$petFood->getPurchaseUrl()) {
            return $text;
        } else {
            $url = $petFood->getPurchaseUrl();
        }

        $classes = $classes . " amazon-product";
        
        return sprintf(
            '<a class="amazon-product" data-type="%s" data-label="%s"
                rel="nofollow" 
                href="%s" target="%s" 
                class="%s">%s</a>'
            , $eventType, $eventLabel, $url, $target, $classes, $text);

    }


    public function imageUrl(PetFood $petFood, $size=null) {
        
        if ($petFood->getImageUrl()) {
            $url =  $size ? $petFood->getResizedImageUrl($size) : $petFood->getImageUrl();
        } else if ($petFood->getIsDryFood()) {
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

    public function imageUrlMax(PetFood $petFood, $size=null) {

        $img = $this->imageUrl($petFood, $size);

        //if SL[0-9]+_ update the number to 1200
        $img = preg_replace("/SL[0-9]+/", 'SL1600', $img);

        //if basename starts with 150_, drop it.
        $img = preg_replace("/\/150_/", "\/", $img);

        return $img;


    }

    public function brandUrl(PetFood $petFood) {

        $brand = strtolower($petFood->getBrand());
        return sprintf("/brand/%s", $brand);
    }
    

    public function paws($number, $class = null) {
        $html = "";
        for ($i=0; $i<$number; $i++) {
            $html .= "<i class='fa fa-paw $class' aria-hidden='true'></i>";
        }

        return $html;
    }

    public function paws2($number, $class = null) {
        $html = "";
        for ($i=0; $i<$number; $i++) {
            $html .= "<i class='fa fa-paw $class' aria-hidden='true'></i>";
        }
        for ($i = 0; $i<5-$number; $i++) {
            $html .= "<i class='fa fa-paw null-paws' aria-hidden='true'></i>";
        }

        return $html;
    }

    public function pinterestData(PetFood $petFood) {

        $media = $this->imageUrl($petFood);
        //some image hacks for the best image

        //get the original image we downloaded, not the resized one
        if (strpos($media, "150_") !== false ) {
            $media =  $this->baseUrl . preg_replace("/150_/", "", $media); //downloaded, resized media
        } elseif (strpos($media, "_SL160_") !== false) {
            $media = preg_replace("/_SL160_/", "_SL300_", $media); //amazon media
        }


        $data = [
            "data-pin-url" => $this->baseUrl . $this->petfoodUrl($petFood),
            "data-pin-media" => $media,
            "data-pin-description" => "Review: " . $petFood->getDisplayName()
        ];

        $tags = "";
        foreach ($data as $key=>$value) {
            $tags .= sprintf("%s=\"%s\" ", $key, $value);
        }


        return $tags;
    }

    public function overallRating(PetFood $petFood, $noun = "product") {

        $nutScore = $petFood->getExtraData('stats')['nutrition_rating'];
        $ingScore = $petFood->getExtraData('stats')['ingredients_rating'];
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
        //if using the constants from BrandAnalysis

        $text = "";
        $class = "";
        switch ($brandRating) {
            case BrandAnalysis::SIG_ABOVE_AVG:
                $text = "a significantly above average";
                $class = "text-saavg";
                break;
            case BrandAnalysis::ABOVE_AVG:
                $text = "an above average";
                $class = "text-aavg";
                break;
            case BrandAnalysis::AVG:
                $text = "an average";
                $class = "text-avg";
                break;
            case BrandAnalysis::BELOW_AVG:
                $text = "a below average";
                $class = "text-bavg";
                break;
            case BrandAnalysis::SIG_BELOW_AVG:
                $text =  "a significantly below average";
                $class = "text-sbavg";
                break;

        }

        $text = trim(sprintf("%s %s %s", $prefix, $text, $postfix));
        $rv = trim(sprintf("<span class='%s'>%s</span>", $class, $text));
        return $rv;

    }

}
