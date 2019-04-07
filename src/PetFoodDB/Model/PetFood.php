<?php

namespace PetFoodDB\Model;

use PetFoodDB\Traits\StringHelperTrait;

class PetFood
{
    use StringHelperTrait;

    const WET_DRY_PERCENT = 20;

    protected $id;
    protected $brand;
    protected $flavor;

    protected $protein;
    protected $fat;
    protected $fibre;
    protected $moisture;
    protected $ash;

    protected $asin;
    protected $imageUrl;
    protected $source;
    protected $updated;
    protected $discontinued;

    public $parserClass;
    protected $ingredients;
    protected $amazonPurchaseTemplate = 'http://www.amazon.com/exec/obidos/ASIN/%s/catfood00b-20';

    protected $extra  = [];

    public function __construct($data = null)
    {
        $this->update($data);
    }


    public function update($data = null) {
        if (is_array($data)) {
            foreach ($data as $key=>$value) {
                $setter = 'set'.ucfirst($key);
                if (method_exists($this, $setter)) {
                    $this->$setter($value);
                } elseif (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    public function setPurchaseAsinTemplate($purchaseTemplate)
    {
        $this->amazonPurchaseTemplate = $purchaseTemplate;

        return $this;
    }

    public function getPurchaseUrl()
    {
        if ($this->amazonPurchaseTemplate && $this->asin) {
            return sprintf($this->amazonPurchaseTemplate, $this->asin);
        } else {
            return "";
        }
    }

    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    public function getResizedImageUrl($width) {
        $url = $this->getImageUrl();
        $regex = "/^(.*_SL)(\d+)(_.jpg$)/";
        $replace = '${1}' . $width . '${3}';
        $result = preg_replace($regex, $replace, $url);

        return strpos($result, (string)$width) ? $result : $url;

    }

    protected function getWetPercentages()
    {
        return [
            'fat' => (float) $this->fat,
            'protein' => (float) $this->protein,
            'moisture' => (float) $this->moisture,
            'fibre' => (float) $this->fibre,
            'other' => (float) $this->ash,
            'carbohydrates' => $this->calcCarbs()
        ];
    }

    public function getDryPercentages()
    {
        $wet = $this->getWetPercentages();
        $dryFactor = 100 / (100 - $wet['moisture']);

        $dry = array_map(function ($x) use ($dryFactor) { return $x * $dryFactor; }, $wet);
        $dry['moisture'] = 0.0;

        //for products with large initial moisture %s, the differences past 100% become significantly amplified
        //therefore, we'll normalize to to the initial % of totals.
        $wetSum = array_sum($wet);
        $drySum = array_sum($dry);
        $mult = $wetSum / $drySum;
        $dry = array_map(function ($val) use ($mult) { return $val * $mult;}, $dry);


        return $dry;
    }


    public function getPercentages()
    {
        return [
            'wet' => $this->getWetPercentages(),
            'dry' => $this->getDryPercentages()
        ];
    }

    public function getProteinCarbRatio() {
        $dry = $this->getDryPercentages();
        $total = $dry['protein'] + $dry['carbohydrates'];
        return $dry['protein'] / $total;
    }

    public function getCaloriesPer100g()
    {
        $fat = self::calcFatCalories(100, (float) $this->fat);
        $protein = self::calcProteinCalories(100, (float) $this->protein);
        $moisture = self::calcMoistureCalories(100, (float) $this->moisture);
        $fibre = self::calcFibreCalories(100, (float) $this->fibre);
        $ash = self::calcAshCalories(100, (float) $this->ash);
        $carbs = self::calcCarbCalories(100, $this->calcCarbs());
        $total = $fat+$protein+$moisture+$fibre+$ash+$carbs;

        return [
            'fat' => $fat,
            'protein' => $protein,
            'moisture' => $moisture,
            'fibre' => $fibre,
            'other' => $ash,
            'carbohydrates' => $carbs,
            'total' => $total
        ];
    }

    public function dbModel()
    {
        $model =  [
            'fat' => $this->fat,
            'protein' => $this->protein,
            'moisture' => $this->moisture,
            'fibre' => $this->fibre,
            'ash' => $this->ash,
            'source' => $this->getSource(),
            'asin' => $this->asin,
            'imageUrl' => $this->getImageUrl(),
            'brand' => $this->getBrand(),
            'flavor' => $this->getFlavor(),
            'ingredients' => $this->ingredients,
            'parserClass' => $this->parserClass,
            'discontinued' => $this->discontinued
        ];

        if ($this->getId()) {
            $model['id'] = $this->getId();
        }
        if ($this->getUpdated()) {
            $model['updated']  = $this->getUpdated();
        }
        
        return $model;
    }

    public function getDisplayName()
    {
        $name = "";
        if ($this->brand) {
            $name .= $this->brand;
        }
        if ($this->flavor) {
            $name .= empty($name) ? "" : " ";
            $name .= $this->flavor;
        }

        return ucwords($name);
    }

    public function getSlug() {

        return urlencode($this->getFlavor());
    }
    
    //All calories calculations: http://petfood.aafco.org/CalorieContent.aspx#caloriecontent
    public static function calcCarbPercentage($fat, $protein, $moisture, $fibre, $ash)
    {
        return max(0, 100.0 - $fat - $protein - $moisture - $fibre - $ash);
    }

    public static function calcFatCalories($grams, $fatPercentage)
    {
        return $fatPercentage * 8.5 * $grams/100;
    }
    public static function calcProteinCalories($grams, $proteinPercentage)
    {
        return $proteinPercentage * 3.5 * $grams/100;
    }
    public static function calcCarbCalories($grams, $carbPercentage)
    {
        return $carbPercentage * 3.5 * $grams/100;
    }
    public static function calcMoistureCalories($grams, $moisturePercentage)
    {
        return $moisturePercentage * 0 * $grams/100; //just giving the allusion of a calculation to keep the api consistent
    }
    public static function calcAshCalories($grams, $ashPercentage)
    {
        return $ashPercentage * 0 * $grams/100; //just giving the allusion of a calculation to keep the api consistent
    }
    public static function calcFibreCalories($grams, $fibrePercentage)
    {
        return $fibrePercentage * 0 * $grams/100; //just giving the allusion of a calculation to keep the api consistent
    }

    protected function calcCarbs()
    {
        return self::calcCarbPercentage(floatval($this->fat), floatval($this->protein),
            floatval($this->moisture), floatval($this->fibre), floatval($this->ash));
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param string|Date $updated
     *
     * @return $this
     */
    public function setUpdated($updated)
    {
        if (is_string($updated)) {
            $updated = new \DateTime($updated);
        }
        if ($updated instanceof \DateTime) {
            $this->updated = $updated;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getUpdated()
    {
        if ($this->updated) {
            return $this->updated->format("Y-m-d");
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getUpdatedInWords()
    {
        if ($this->updated) {
            return $this->updated->format("F j, Y");
        } else {
            return null;
        }
    }

    /**
     * @return string
     */
    public function getFlavor()
    {
        return $this->flavor;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * @return boolean
     */
    public function getDiscontinued()
    {
        return $this->discontinued;
    }

    /**
     * @param boolean $discontinued
     *
     * @return $this
     */
    public function setDiscontinued($discontinued)
    {
        $this->discontinued = (bool) $discontinued;

        return $this;
    }



    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getIngredients()
    {
        return $this->ingredients;
    }

    /**
     * @param mixed $parserClass
     *
     * @return $this
     */
    public function setParserClass($parserClass)
    {
        $this->parserClass = $parserClass;

        return $this;
    }

    public function getIsDryFood() {
        $wet = $this->getWetPercentages();
        return $wet['moisture'] <= self::WET_DRY_PERCENT;
    }

    public function getIsWetFood() {
        return !$this->getIsDryFood();
    }

    public function getProductPath() {
        $brand = strtolower($this->getBrand());
        $slug = $this->getSlug();
        
        return "$brand/$slug";
    }

    protected function round5($value)
    {
        return round($value*2,-1) / 2;
    }

    protected function hasIngredient($ingredient) {

        $ingredients = $this->getIngredients();
        return $this->contains($ingredients, $ingredient, false);

    }

    /**
     * @return double
     */
    public function getProtein()
    {
        return $this->protein;
    }

    /**
     * @return double
     */
    public function getFat()
    {
        return $this->fat;
    }

    /**
     * @return double
     */
    public function getFibre()
    {
        return $this->fibre;
    }

    /**
     * @return double
     */
    public function getMoisture()
    {
        return $this->moisture;
    }

    /**
     * @return double
     */
    public function getAsh()
    {
        return $this->ash;
    }

    /**
     * @return string
     */
    public function getAsin()
    {
        return $this->asin;
    }

    public function addExtraData($key, $data) {
        $this->extra[$key] = $data;
    }

    public function getExtraData($key) {
        if (isset($this->extra[$key])) {
            return $this->extra[$key];
        }
        return false;
    }

    public function getAllExtraData() {
        return $this->extra;
    }

    public function __get($name)
    {
        return $this->getExtraData($name);
    }

    public function __toString()
    {
        return $this->getDisplayName() . "(" . $this->getId() . ")";
    }
    

}
