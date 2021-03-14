<?php


namespace PetFoodDB\Service;


use PetFoodDB\Model\PetFood;
use PetFoodDB\Traits\LoggerTrait;
use PetFoodDB\Traits\MathTrait;
use PetFoodDB\Traits\StringHelperTrait;

class AnalyzeIngredients
{
    use LoggerTrait;
    use MathTrait;
    use StringHelperTrait;

    static function getProteinAdjectives() {
        return ['deboned', 'fresh deboned', 'organic', 'boneless', 'boneless/skinless', 'de-boned', 'whole', 'fresh', 'hydrolyzed', 'raw',
            'lamb', 'cutlets', 'flaked', 'shredded', 'flakes', 'freeze-dried', 'dried', 'baby', 'meat', 'dehydrated', 'fresh whole',
            'whole atlantic', 'dehydrated whole', 'fresh angus', 'fresh plains', 'fresh yorkshire', 'fresh whole pacific', 'grass-fed',
            'raw grass-fed', 'finely ground', 'king', 'ocean caught', 'humanely raised', 'pasture raised', 'wild caught', 'sustainably sourced',
            'humanely raised', 'wild pacific', 'wild atlantic'];
    }

    static function getProteinSpecifics() {
        return ['heart', 'thigh', 'liver', 'lung', 'liver', 'giblets', 'meal', 'white meat', 'filets', 'red meat', 'meat meal',
            'whole meat', 'cutlets', 'tripe', 'meat', '(boneless, skinless breast)', 'livers', 'gizzards', 'gizzard', 'hearts', 'necks',
            'giblets (liver, heart, kidney)', 'with ground bone', '(ground with bone)', 'kidney', 'lungs', 'trachea', 'skin',
            'with bone'];
    }

    static function getSeafoodProteins() {
        $seafood = ['salmon', 'tuna', 'trout', 'mussel', 'anchovy & sardine', 'Green Lipped Mussel', 'sea bass',
            'ocean whitefish', 'herring', 'flounder', 'clam', 'pollock', 'red snapper',
            'whitefish', 'cod', 'white fish', 'mackerel', 'pacific hake', 'tilapia', 'prawns',
            'yellowfin tuna', 'seabream', 'catfish', 'sea bream', 'menhaden fish', 'ahi tuna', 'sardine', 'arctic char',
            'sardines', 'basa', 'saba', 'mackerel', 'trevally', 'barramundi', 'shrimp', 'crab', 'polluck', 'bonito', 'hoki', 'krill', 'pilchard',
            'acadian redﬁsh', 'atlantic monkfish', 'silver hake', 'blue whiting', 'rockfish', 'big redeye', 'barramundi','shirasu', 'skipjack', 'skipjack tuna',
            'threadfin bream', 'red bigeye', 'unagi'
        ];

        return $seafood;
    }

    static function getByProductPhrases() {
        return ["by product", "by-product"];
    }

    static function getFillers() {
        return ["corn", "rice", "wheat", "starch", "flour", "soy", 'maize'];
    }

    static function getPreservatives() {

        return ['bha', 'bht', 'ethoxyquin', 'propyl gallate', 'tbhq'];

    }

    static function getQuestionableAdditives() {
        return ['carrageenan', 'guar gum'];
    }



    static function getSugars() {
        return ['sugar', 'corn syrup'];
    }

    static function getArtificialColors() {
        return ['red 40', 'yellow 5', 'blue 2', 'red #3', 'red 3'];
    }

    static function getMoistureSources() {
        $moisture = [
            'water sufficient for processing', 'water', 'sufficient water for processing', 'water for processing'
        ];
        

        $proteins = self::getAllProteinSources();
        foreach ($proteins as $protein) {
            $moisture[] = "$protein broth";
        }

        return $moisture;

    }


    static function getNonSeafoodProteins() {
        $nonSeafoodProteins = ['goat', 'chicken', 'chicken breast', 'turkey', 'beef', 'duck', 'lamb', 'bison', 'mutton', 'rabbit',
            'venison', 'wild boar', 'pork', 'guineafowl', 'quail', 'brushtail', 'buffalo', 'eel', 'pheasant', 'boar', 'kangaroo', 'alligator'];
        return $nonSeafoodProteins;
    }

    static function getAllProteinSources() {
        $seafood = self::getSeafoodProteins();
        $others = self::getNonSeafoodProteins();
        return array_merge($seafood, $others);
    }

    static function getCommonAllergens() {

        $allergens = [
            'beef' => ['beef'],
            'lamb' => ['lamb'],
            'seafood' => array_merge(self::getSeafoodProteins(), ['fish']),
            'corn' => ['corn', 'cornmeal', 'corn meal', 'corn starch'],
            'wheat gluten' => ['wheat gluten'],
            'soy' => ['soy'],
            'dairy' => ['dairy', 'milk', 'cheese'],
            'eggs' => ['eggs', 'egg'],
            'artifical colors' => self::getArtificialColors(),
            'meat by-products'=> ['meat by-products']

        ];

        return $allergens;
    }

    static function notAllergens() {

        return ['milk thistle'];

    }

    static function containsAllergens(PetFood $catFood, $specificType=false) {

        $allergens = self::getCommonAllergens();
        $notAllergens = self::notAllergens();

        if ($specificType && isset($allergens[$specificType])) {
            $allergens = [
                $specificType => $allergens[$specificType]
            ];
        }


        $ingredients = self::parseIngredients($catFood);

        $contains = [
            'all' => []
        ];
        foreach ($allergens as $type=>$allergenIngredients) {
            $contains[$type] = [];
            foreach ($ingredients as $ingredient) {
                foreach ($allergenIngredients as $allergen) {
                    if (strpos($ingredient, $allergen) !== false) {
                        if (!in_array($ingredient, $notAllergens)) {

                            $contains[$type][] = $ingredient;
                        }
                    }
                }
            }
            $contains[$type] = array_unique($contains[$type]);
            $contains['all'] = array_merge($contains['all'], $contains[$type]);

        }

        $contains['all'] = array_unique($contains['all']);

        return $contains;

    }

    static function getAllIngredientVariations(array $ingredients) {

        $allIngredients = [];
        $adjectives = self::getProteinAdjectives();
        $suffixes = self::getProteinSpecifics();
        foreach ($ingredients as $ingredient) {
            $allIngredients[] = $ingredient;
            foreach ($adjectives as $adjective) {
                $allIngredients[] = "$adjective $ingredient";
                foreach ($suffixes as $suffix) {
                    $allIngredients[] = "adjective $ingredient $suffix";
                }
            }
            foreach ($suffixes as $suffix) {
                $allIngredients[] = "$ingredient $suffix";
            }
        }
        return $allIngredients;

    }


    static function isByProduct(PetFood $catFood, $nth) {
        $ingredient = self::getNthIngredient($catFood, $nth);
        return (self::isIngredientByProduct($ingredient)) ? $ingredient : false;

    }

    static function getFirstIngredients(PetFood $catFood, $n) {
        $ingredients = [];
        for ($i = 1; $i<=$n; $i++) {
            $ingredients[] = self::getNthIngredient($catFood, $i);
        }

        return $ingredients;
    }

    static function isIngredientByProduct($ingredient) {
        $phrases = self::getByProductPhrases();
        foreach ($phrases as $phrase) {
            if (strpos(strtolower($ingredient), $phrase) !== false) {
                return true;
            }
        }

        return false;

    }

    static function isIngredientSugar($ingredient) {

        $phrases = self::getSugars();
        foreach ($phrases as $phrase) {
            if (strpos(strtolower($ingredient), $phrase) !== false) {
                return true;
            }
        }

        return false;

    }

    static function isIngredientUndesirablePreservative($ingredient) {

        $phrases = self::getPreservatives();
        foreach ($phrases as $phrase) {
            if (strpos(strtolower($ingredient), $phrase) !== false) {
                return true;
            }
        }

        return false;

    }

    static function isIngredientQuestionableAdditive($ingredient) {

        $phrases = self::getQuestionableAdditives();
        foreach ($phrases as $phrase) {
            if (strpos(strtolower($ingredient), $phrase) !== false) {
                return true;
            }
        }

        return false;

    }

    static function isFiller(PetFood $catFood, $nth) {
        $ingredient = self::getNthIngredient($catFood, $nth);
        return (self::isIngredientFiller($ingredient)) ? $ingredient : false;


    }

    static function isIngredientFiller($ingredient) {
        $phrases = self::getFillers();
        foreach ($phrases as $phrase) {
            if (strpos(strtolower($ingredient), $phrase) !== false) {
                return true;
            }
        }

        return false;
    }


    static function isProteinSource(PetFood $catFood, $nth) {
        $ingredient = self::getNthIngredient($catFood, $nth);
        return self::isIngredientProteinSource($ingredient) ? $ingredient : false;

    }


    static function isSeafoodSource(PetFood $catFood, $nth) {
        $ingredient = self::getNthIngredient($catFood, $nth);
        return self::isIngredientSeafoodProtein($ingredient) ? $ingredient : false;
    }

    static function isMoistureSource(PetFood $catFood, $nth) {
        $ingredient = self::getNthIngredient($catFood, $nth);
        return self::isIngredientMoistureSource($ingredient) ? $ingredient : false;
    }

    static function isIngredientMoistureSource($ingredient) {
        $moistures = self::getMoistureSources();
        foreach ($moistures as $moisture) {
            if ($moisture == $ingredient) {
                return $ingredient;
            }
        }
        return false;
    }

    static function stripBrackets($string) {
        $string = trim(preg_replace("/(^.+)(\(.*\))/", '$1', $string));

        return $string;
    }



    static function isIngredientASource($proteins, $ingredient) {
        $adjectives = self::getProteinAdjectives();
        $suffixes = self::getProteinSpecifics();

        $ingredient = self::stripBrackets($ingredient);
        $ingredient = self::removeNonPrintable($ingredient);

        foreach ($proteins as $protein) {
            if (strpos($ingredient, $protein) !== false) {
                $phrases = [$protein];
                foreach ($adjectives as $adjective) {
                    $phrases[] = "$adjective $protein";
                    foreach ($suffixes as $suffix) {
                        $phrases[] = "$adjective $protein $suffix";
                    }
                }
                foreach ($suffixes as $suffix) {
                    $phrases[] = "$protein $suffix";
                }
                foreach ($phrases as $phrase) {
                    if (strcmp($ingredient, $phrase) == 0) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    static function isIngredientSeafoodProtein($ingredient) {
        $proteins = self::getSeafoodProteins();
        return self::isIngredientASource($proteins, $ingredient);
    }
 
    static function isIngredientProteinSource($ingredient) {
        $proteins = self::getAllProteinSources();
        return self::isIngredientASource($proteins, $ingredient);
    }

    static function isUndesirerableIngredient($ingredient) {
        $ingredient =  self::stripBrackets($ingredient);
        if (self::isIngredientByProduct($ingredient)) {
            return true;
        }
        if (self::isIngredientFiller($ingredient)) {
            return true;
        }

        if (self::isIngredientUndesirablePreservative($ingredient)) {
            return true;
        }

        if (self::isIngredientQuestionableAdditive($ingredient)) {
            return true;
        }

        return false;
    }

    static function stripPeriod($ing) {
        $ing = trim($ing);
        $ing = preg_replace("/(.+)\.$/", '$1', $ing);
        return $ing;
    }

    static public function getNthIngredient(PetFood $catfood, $n) {

        $x = (int) $n -1;

        $ingredients = self::parseIngredients($catfood);

        if (isset($ingredients[$x])) {
            return strtolower($ingredients[$x]);
        } else {
            return null;
        }
    }


    static public function parseIngredients(PetFood $catfood) {
        $ing = $catfood->getIngredients();
        $regex = "/\s*\([^\)]*\)\s*/";
        $ing = preg_replace($regex, "", $ing);
        
        $ingredients = array_map('trim', explode(",", $ing));
        $ingredients = array_map('strtolower', $ingredients);
        $ingredients = array_map('self::stripPeriod', $ingredients);

        return $ingredients;
    }

    public static function isPotentialAllergen($ingredient) {

        $allergens = array_values(self::getCommonAllergens());
        $allergens = call_user_func_array('array_merge', $allergens);
        $allergens = array_map('strtolower', array_unique($allergens));
        $notAllergens = self::notAllergens();

        foreach ($allergens as $allergen) {
            if (strpos($ingredient, $allergen) !== false) {
                if (!in_array($ingredient, $notAllergens)) {
                    return true;
                }
            }
        }
        return false;


    }


    static function analyzeIngredients(PetFood $catFood) {
        $ingredients = self::parseIngredients($catFood);

        $quality = [];
        $unnecessary = [];
        $allergens = [];


        foreach ($ingredients as $i=>$ingredient) {
            if (self::isIngredientProteinSource($ingredient)) {
                $ingredient = self::stripBrackets($ingredient);
                $quality[$i] = $ingredient;
            }
            if (self::isUndesirerableIngredient($ingredient)) {
                $ingredient = self::stripBrackets($ingredient);
                $unnecessary[$i] = $ingredient;
            }
            if (self::isPotentialAllergen($ingredient)) {
                $ingredient = self::stripBrackets($ingredient);
                $allergens[$i] = $ingredient;
            }

        }

        return [
            'quality' => $quality,
            'questionable' => $unnecessary,
            'allergens' => $allergens
        ];


    }

    public static function getTopIngredientsByType(PetFood $catFood, &$proteins, &$byproducts, &$fillers) {

        $offset = 1;
//        if (self::isMoistureSource($catFood, $offset)) {
//            $ing = self::getNthIngredient($catFood, 1);
//            $offset += 1;
//        }


        for ($i = 0; $i < 5; $i++) {
            $proteins[$i] = self::isProteinSource($catFood, $i+$offset);
            $byproducts[$i] = self::isByProduct($catFood, $i+$offset);
            $fillers[$i]  = self::isFiller($catFood, $i+$offset);
        }

        return true;

    }

    public static function hasUndesierablePreservative(PetFood $catFood) {
        $ingredients = self::parseIngredients($catFood);
        $ick = [];
        foreach ($ingredients as $ingredient) {
            $ingredient = self::stripBrackets($ingredient);
            if (self::isIngredientUndesirablePreservative($ingredient)) {
                $ick[] = $ingredient;
            }
        }

        return $ick ? $ick : false;
    }

    public static function hasQuestionableAdditive(PetFood $catFood) {
        $ingredients = self::parseIngredients($catFood);
        $ick = [];
        foreach ($ingredients as $ingredient) {
            $ingredient = self::stripBrackets($ingredient);
            if (self::isIngredientQuestionableAdditive($ingredient)) {
                $ick[] = $ingredient;
            }
        }

        return $ick ? $ick : false;
    }


    public function calcIngredientScore(PetFood $catFood) {

        /*
        * Ingredients: Start at 2
        *              + 1 protein first
        *              + .5 each other protein 2-4
        *              - 1 if top by product or filler
        *              - .5 each other by product
        *              + 1 if no filler
        *              + 1 if no byproducts
        *              + 1.5 if < 15 ingredients (+1 if <20)
        *              -1 if any undeserable perservative
        *
        */

        $roundUp = true; //default, may change based on additives


        $proteins = [];
        $byproducts = [];
        $fillers = [];
        self::getTopIngredientsByType($catFood, $proteins, $byproducts, $fillers);

        $topProtien = $proteins[0];
        $topFiller = $fillers[0];

        $otherProteins = count(array_filter(array_slice($proteins, 1)));
        $topByProduct = $byproducts[0];
        $otherByProducts = count(array_filter(array_slice($byproducts, 1)));
        $hasFillers = count(array_filter(array_slice($fillers, 1)));

        $ingredientScore = 2;
        if ($topProtien) {
            $this->getLogger()->debug($catFood->getId() . ": Adding 1 to ingredient score b/c protein top protein");
            $ingredientScore++;
        }

        $ingredientScore += 0.5 * $otherProteins;
        $this->getLogger()->debug($catFood->getId() . ": Adding " . 0.5 * $otherProteins . " to ingredient score b/c $otherProteins other proteins");

        if ($topByProduct || $topFiller) {
            $ingredientScore--;
            $this->getLogger()->debug($catFood->getId() . ": Reducing 1 from ingredient score b/c byproduct or filler is top");
        }

        $ingredientScore -= 0.5 * $otherByProducts;
        $this->getLogger()->debug($catFood->getId() . ": Reducing " . 0.5 * $otherByProducts . " from ingredient score b/c $otherByProducts other by products");

        if ($hasFillers) {
            $this->getLogger()->debug($catFood->getId() . ": Reducing 0.5 from ingredient score b/c has fillers");
            $ingredientScore -= 0.5;
        }

        $numIngredients = count(self::parseIngredients($catFood));
        if ($numIngredients < 15) {
            $this->getLogger()->debug($catFood->getId() . ": Adding 1.5 to ingredient score b/c has $numIngredients ingredients (ie < 15)");
            $ingredientScore += 1.5;
        } elseif ($numIngredients < 20) {
            $this->getLogger()->debug($catFood->getId() . ": Adding 1 to ingredient score b/c has $numIngredients ingredients (ie < 20)");
            $ingredientScore += 1.0;
        }

        if (self::hasUndesierablePreservative($catFood)) {
            $ingredientScore -= 1.0;
            $this->getLogger()->debug($catFood->getId() . ": Reducing 1 from ingredient score b/c has preservative");
        }

        if (self::hasQuestionableAdditive($catFood)) {
            $ingredientScore -= 0.5;
            $this->getLogger()->debug($catFood->getId() . ": Reducing 0.5 from ingredient score b/c has questionable additve");
            $roundUp = false;
        }

        $this->getLogger()->debug($catFood->getId() . " Raw Score: $ingredientScore");


        $ingredientScore = max(1, $ingredientScore);


        if ($roundUp) {
            $this->getLogger()->debug($catFood->getId() . " Rounding ingredient score up!");
            $ingredientScore = ceil($ingredientScore);
        } else {
            $this->getLogger()->debug($catFood->getId() . " Rounding ingredient score down!");
            $ingredientScore = floor($ingredientScore);
        }
        $score = min(5, $ingredientScore);
        $this->getLogger()->debug($catFood->getId() . " Final Ingredient Score: $score");


        return $score;
    }

    public function getPrimaryProteins(PetFood $product, $maxIngredients = 6) {

        $baseProteins = self::getAllProteinSources();
        for ($i = 0; $i < $maxIngredients; $i++) {
            $proteins[$i] = self::isProteinSource($product, $i);
        }

        $proteins = array_filter($proteins);
        $primaryProteins = [];
        foreach ($proteins as $i=>$protein) {
            foreach ($baseProteins as $base) {
                if ($this->contains($protein, $base)) {
                    $primaryProteins[] = $base;
                }
            }
        }
        $primaryProteins = array_unique($primaryProteins);
        return $primaryProteins;

    }



}
