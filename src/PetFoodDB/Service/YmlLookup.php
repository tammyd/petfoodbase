<?php

namespace PetFoodDB\Service;

use PetFoodDB\Traits\StringHelperTrait;
use Symfony\Component\Yaml\Parser;

class YmlLookup
{
    use StringHelperTrait;

    public function __construct($location, $keyField)
    {
        $this->yaml = new Parser();
        $this->data = $this->yaml->parse(file_get_contents($location));
        $this->key = $keyField;

    }

    public function lookup($keyValue, $field, $caseSensitive = true)
    {
        $entries = array_filter($this->data['manual_entries'], function ($entry) use ($keyValue, $caseSensitive) {
            if (isset($entry[$this->key])) {
                if ($caseSensitive) {
                    return $entry[$this->key] == $keyValue;
                } else {
                    return strtolower($entry[$this->key]) == strtolower($keyValue);
                }
            } else {
                return null;
            }
        });

        if (count($entries)) {
            $entry = array_pop($entries);
            if (array_key_exists($field, $entry)) {
                return $entry[$field];
            }
        }

        return false;

    }

}
