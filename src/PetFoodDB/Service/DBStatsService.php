<?php


namespace PetFoodDB\Service;


use PetFoodDB\Model\PetFood;
use PetFoodDB\Traits\LoggerTrait;
use PetFoodDB\Traits\MathTrait;
use Doctrine\Common\Cache\CacheProvider;

class DBStatsService
{

    use LoggerTrait;
    use MathTrait;

    protected $cache;
    protected $catfoodService;

    public function __construct(CatFoodService $catfoodService, CacheProvider $cache)
    {
        $this->cache = $cache;
        $this->catfoodService = $catfoodService;

    }

    public function getStat($type, $stat, $key = null) { ////wet, median, fat
        $stats = $this->getStatSummary();

        if ($key) {
            if (isset($stats[$type][$stat][$key])) {
                return $stats[$type][$stat][$key];
            }
        } else {
            if (isset($stats[$type][$stat])) {
                return $stats[$type][$stat];
            }
        }

        return null;
    }

    public function getStatSummary() {
        $cachedStats = $this->getCachedStats();
        if ($cachedStats && !$this->needsRecalc()) {
            return $cachedStats;
        }

        $stats =  $this->rawCalculateStats();

        return $stats;
    }


    protected function rawCalculateStats() {
        $allItems = $this->catfoodService->getAll();
        $wetCatFood = [];
        $dryCatFood = [];
        $stats = [];

        /** @var PetFood $catFood */
        foreach ($allItems as $catFood) {
            if ($catFood->getIsDryFood()) {
                $dryCatFood[] = $catFood;
            } else if ($catFood->getIsWetFood()) {
                $wetCatFood[] = $catFood;
            }
        }


        $stats['dry'] = $this->getStatsForCatfood($dryCatFood);
        $stats['wet'] = $this->getStatsForCatfood($wetCatFood);


        $this->cacheStats($stats);

        return $stats;

    }

    public function getStatsForCatfood(array $catfoodItems) {

        $stats = [
            'mean' => [],
            'median' => [],
            'max' => [],
            'min' => [],
            'stddev' => []
        ];
        $data = [
            'carbohydrates' => [],
            'fat' => [],
            'protein' => [],
            'fibre' => [],
            'calories' => [],
            'other' => [],
            'moisture' => []
        ];

        $categories = [
            'dry' => ['carbohydrates', 'fibre', 'fat', 'protein', 'other'],
            'wet' => ['moisture']
        ];
        /** @var PetFood $catFood */
        foreach ($catfoodItems as $i=>$catFood) {
            $percentages = $catFood->getPercentages();

            foreach ($categories as $matter=>$fields) {
                foreach ($fields as $field) {
                    $data[$field][$catFood->getId()] = $percentages[$matter][$field];
                }

                $data['calories'][$catFood->getId()] = $catFood->getCaloriesPer100g()['total'];
            }

        }

        foreach ($data as $key=>$values) {
            $count = count($values);

            $stats['mean'][$key] = $count ? $this::mean($values) : 0;
            $stats['median'][$key] = $count ? $this::median($values) : 0;
            $stats['min'][$key] = $count ? min($values) : 0;
            $stats['max'][$key] = $count ? max($values) : 0;
            $stats['stddev'][$key] = $count ? $this::standard_deviation($values) : 0;
        }

        $stats['count'] = count($catfoodItems);

        return $stats;

    }

    protected function needsRecalc() {
        $lastUpdate = $this->catfoodService->getLastDBUpdate();
        $updatedDate = new \DateTime();
        $updatedDate->setTimestamp(strtotime($lastUpdate));
        $updatedDate->add(new \DateInterval("P1D"));
        $todaysDate = new \DateTime();
        if ($todaysDate < $updatedDate) { //db was updated sometime today
            $cachedHash = $this->cache->fetch('DB_HASH');
            $this->getLogger()->debug("DB was updated today, using hash");
            if (!$cachedHash) {
                return true;
            }
            $currHash = $this->calcDBHash();
            if ($cachedHash != $currHash) {
                $this->getLogger()->debug("DB hashes do not match, need recalc");
                return true;
            } else {
                $this->getLogger()->debug("DB hashes DO match, DO NOT need recalc");
                return false;
            }
        } else {
            $this->getLogger()->debug("DB was updated in the past, don't need recalc");
            return false;
        }

    }

    protected function cacheStats(array $stats) {
        $dbHash = $this->calcDBHash();

        $this->cache->save("DB_STATS", $stats);
        $this->cache->save("DB_HASH", $dbHash);

        return $this;
    }

    protected function getCachedStats() {
        return $this->cache->fetch("DB_STATS");
    }

    protected function calcDBHash() {
        $allItems = $this->catfoodService->getAll();
        $string = "";
        foreach ($allItems as $item) {
            $dbHash = $item->dbModel();
            $string .= http_build_query($dbHash);
        }
        $md5 = md5($string);
        return $md5;
    }

    protected function getDateLastCalculated() {
        $dateString = $this->cache->fetch("DB_LAST_CALCULATED");
        if ($dateString) {
            return strtotime($dateString);
        } else {
            return null;
        }
    }



    public function percentageDifference($type, $stat, $key, $value) { //wet, median, fat
        $statValue = $this->getStat($type, $stat, $key);

        $diff = $this->calcDifference($statValue, $value);
        //$this->getLogger()->debug("Calc Difference: for ($type, $stat, $key) of $value. Stat value is $statValue, diff is $diff");
        return $diff;
    }

    public function calcDifference($val1, $val2) {
        if ($val1 == $val2) {
            $diff = 0;
        } else if ($val1 == 0) {
            $diff = 0; //undefined
        } else {
            $diff = ($val2 - $val1) / $val1;
        }

        return $diff;
    }



}
