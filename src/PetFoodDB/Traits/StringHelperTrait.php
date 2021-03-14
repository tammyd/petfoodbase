<?php

namespace PetFoodDB\Traits;

trait StringHelperTrait
{
    /**
     * Checks whether $haystack string starts with $needle
     *
     * @param string $haystack String to test
     * @param string $needle   string to test against
     *
     * @return bool
     */
    public function startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    /**
     * Checks whether $haystack string ends with $needle
     *
     * @param string $haystack String to test
     * @param string $needle   string to test against
     *
     * @return bool
     */
    public function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

    public function contains($haystack, $needle, $caseSensitive=true)
    {
        if (!$caseSensitive) {
            $haystack = strtolower($haystack);
            $needle = strtolower($needle);
        }

        return strpos($haystack, $needle) !== false;
    }

    public function containsAny($haystack, array $needles, $caseSensitive=true)
    {
        foreach ($needles as $needle) {
            if ($this->contains($haystack, $needle, $caseSensitive)) {
                return true;
            }
        }

        return false;
    }

    public static function removeNonPrintable($string) {
       return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $string);
    }

    public static function removeMultipleSpaces($string) {
        return preg_replace('!\s+!', ' ', $string);
    }

    protected function stripUndefined($string) {
        return mb_ereg_replace('/[\x80-\x9F/', ' ', $string);
    }

    protected function cleanText($text) {
        $text =  html_entity_decode(trim(str_replace(array("\r", "\n"), ' ', $text)));
        $text = strip_tags($text);
        $text = $this->stripHtmlSuperscripts($text);
        $text = $this->stripTrademarks($text);
        $text = str_replace("‘", "'", $text); //screws up encoding
        $text = str_replace("–", "-", $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    protected function stripTrademarks($string, $replace = " ")
    {
        $search = ['™', '®'];
        $string = str_replace($search, $replace, $string);

        return $string;
    }

    protected function stripHtmlSuperscripts($html)
    {
        $re="/<sup>(?:.*?)<\/sup>/i";
        $html = preg_replace($re, " ", $html);

        return $html;
    }

    protected function stripNonUTF8($string)
    {
        return preg_replace('/[\x00-\x1F\x80-\xFF]/', ' ', $string);
    }

    protected function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }
    


}
