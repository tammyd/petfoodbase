<?php


namespace PetFoodDB\Twig;

use PetFoodDB\Service\NewAnalysisService;
use Twig\TwigFunction;

class StatsExtension extends \Twig_Extension
{

    const FIRST_ING_QUALITY_PROTEIN = "First ingredient is a quality protein.";
    const NO_QUESTIONABLE_ING = "Contains no questionable ingredients or fillers.";
    const SIG_HIGH_PROTEIN = "Contains significantly more protein than average.";
    const SIG_LESS_CARBS = "Contains significantly fewer carbs than average.";



    /**
     * @var \Slim\Slim
     */
    protected $app;

    public function __construct(\Slim\Slim $app)
    {
        $this->app = $app;
    }

    public function getName()
    {
        return "statsExtensions";
    }


    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('carb_display', [$this, 'carbDisplay'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('protein_display', [$this, 'proteinDisplay'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('calorie_explaination', [$this, 'caloriesDisplay'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('moisture_explaination', [$this, 'moistureDisplay'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('stat_display', [$this, 'statDisplay'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('inferior', [$this, 'wrapInferior'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('allergen', [$this, 'wrapAllergen'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('quality', [$this, 'wrapQuality'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('review_highlights', [$this, 'reviewHighlights'], ['is_safe'=>[true]]),
            new \Twig_SimpleFilter('dump', [$this, 'dump'], ['is_safe'=>[true]]),
        ];
    }

    public function getFunctions()
    {
        $functions = parent::getFunctions();
        $functions[] = new \Twig\TwigFunction('has_highlights', [$this, 'hasHighlights']);

        return $functions;
    }

    public function hasHighlights($productStats, $analysis) {
        $highlights = $this->getHighlights($productStats, $analysis);
        return count($highlights) > 0;
    }


    public function dump($var) {
        ob_start();
        dump($var);
        $d = ob_get_clean();
        return "<span>$d</span>";
    }

    static public function getHighlights($productStats, $analysis) {
        $highlights = [];

        if (isset($analysis['quality'][0])) {
            $highlights[] = self::FIRST_ING_QUALITY_PROTEIN; //  "First ingredient is a quality protein.";
        }

        if (empty($analysis['questionable'])) {
            $highlights[] = self::NO_QUESTIONABLE_ING; //"Contains no questionable ingredients or fillers.";
        }

        if (self::isSignificantlyAboveAverage($productStats['protein'])) {
            $highlights[] = self::SIG_HIGH_PROTEIN; //"Contains significantly more protein than average.";
        }
        if (self::isSignificantlyBelowAverage($productStats['carbohydrates'])) {
            $highlights[] = self::SIG_LESS_CARBS; //"Contains significantly fewer carbs than average.";
        }

        return $highlights;
    }

    public function reviewHighlights($productStats, $analysis) {
        $highlights = $this->getHighlights($productStats, $analysis);

        if (count($highlights) < 1) {
            return "";
        }

        $html = "<ul>";
        foreach ($highlights as $point) {
            $html .= "<li><h5 class='unbold' style='margin-bottom: 0px; margin-top: 0px;'>$point</h5></li>";
        }
        $html .= "</ul>";
        return $html;

    }

    protected function isAverage($diff) {
        return  abs($diff) <= NewAnalysisService::STAT_ABOVE_AVERAGE_SD;
    }

    protected function isAboveAverageButNotSignificantly($diff) {
        $abs = abs($diff);
        if ($abs > NewAnalysisService::STAT_ABOVE_AVERAGE_SD) {
            if ($abs <= NewAnalysisService::STAT_SIG_ABOVE_SD) {
                return $diff > 0;
            }
        }

        return false;
    }

    protected function isBelowAverageButNotSignificantly($diff) {
        $abs = abs($diff);
        if ($abs > NewAnalysisService::STAT_ABOVE_AVERAGE_SD) {
            if ($abs <= NewAnalysisService::STAT_SIG_ABOVE_SD) {
                return $diff <= 0;
            }
        }

        return false;
    }

    static function isSignificantlyAboveAverage($diff) {
        $abs = abs($diff);
        if ($abs > NewAnalysisService::STAT_SIG_ABOVE_SD) {
            return $diff > 0;
        }

        return false;
    }


    static function isSignificantlyBelowAverage($diff) {
        $abs = abs($diff);
        if ($abs > NewAnalysisService::STAT_SIG_ABOVE_SD) {
            return $diff <= 0;
        }

        return false;
    }

    protected function displayChoser($diff, $phrases) {
        $div1 = NewAnalysisService::STAT_ABOVE_AVERAGE_SD;
        $div2 = NewAnalysisService::STAT_SIG_ABOVE_SD;

        $average = $phrases[0];
        $slightlyAbove = $phrases[1];
        $slightlyBelow = $phrases[-1];
        $significantlyAbove = $phrases[2];
        $significantlyBelow = $phrases[-2];

        $abs = abs($diff);

        if ($abs <= $div1) {
            $rating = $average;
        } elseif ($abs <= $div2) {
            $rating = $diff > 0 ? $slightlyAbove : $slightlyBelow;
        } else {
            $rating = $diff > 0 ? $significantlyAbove : $significantlyBelow;
        }


        return $rating;
    }

    public function carbDisplay($value) {


        $phrases = [
            -2 => $this->wrapWithClass("significantly fewer carbohydrates than average", "rating bold text-orig-success"),
            -1 => $this->wrapWithClass("fewer carbohydrates than average", "rating text-orig-success"),
            0 => $this->wrapWithClass("an average amount of carbohydrates", "rating"),
            1 => $this->wrapWithClass("more carbohydrates than average", "rating text-danger"),
            2 => $this->wrapWithClass("significantly more carbohydrates than average", "rating bold text-danger")
        ];

        return $this->displayChoser($value, $phrases);
    }

    public function proteinDisplay($value) {

        $phrases = [
            -2 => $this->wrapWithClass("significantly less protein than average", "rating bold text-danger"),
            -1 => $this->wrapWithClass("less protein than average", "rating text-danger"),
            0 => $this->wrapWithClass("an average amount of protein", "rating"),
            1 => $this->wrapWithClass("more protein than average", "rating text-orig-success"),
            2 => $this->wrapWithClass("significantly more protein than average", "rating bold text-orig-success")
        ];

        return $this->displayChoser($value, $phrases);
    }

    public function moistureDisplay($value) {

        $phrases = [
            -2 => $this->wrapWithClass("less moisture than average", "rating text-warning"),
            -1 => $this->wrapWithClass("less moisture than average", "rating text-warning"),
            0 => $this->wrapWithClass("an average amount of moisture", "rating"),
            1 => $this->wrapWithClass("more moisture than average", "rating"),
            2 => $this->wrapWithClass("more moisture than average", "rating")
        ];

        return $this->displayChoser($value, $phrases);
    }

    public function statDisplay($value, $stat) {

        $phrases = [
            -2 => $this->wrapWithClass("significantly less $stat than average", "rating"),
            -1 => $this->wrapWithClass("less $stat than average", "rating"),
            0 => $this->wrapWithClass("an average amount of $stat", "rating"),
            1 => $this->wrapWithClass("more $stat than average", "rating"),
            2 => $this->wrapWithClass("significantly more $stat than average", "rating")
        ];

        return $this->displayChoser($value, $phrases);
    }

    protected function wrapWithClass($text, $class) {
        return sprintf("<span class='%s'>%s</span>", $class, $text);
    }


    public function caloriesDisplay($value) {

        $phrases = [
            -2 => $this->wrapWithClass("considerably fewer calories", "rating"),
            -1 => $this->wrapWithClass("fewer calories", "rating"),
            0 => $this->wrapWithClass("an average amount of calories", "rating"),
            1 => $this->wrapWithClass("a few more calories", "rating"),
            2 => $this->wrapWithClass("considerably more calories", "rating")
        ];

        return $this->displayChoser($value, $phrases);
    }

    public function wrapQuality($text) {
        return sprintf("<span class='text-orig-success'>%s</span>", $text);
    }

    public function wrapInferior($text) {
        return sprintf("<span class='text-danger'>%s</span>", $text);
    }

    public function wrapAllergen($text) {
        return sprintf("<span class='text-warning'>%s</span>", $text);
    }
    









}
