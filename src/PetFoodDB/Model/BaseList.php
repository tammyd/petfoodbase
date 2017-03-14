<?php

namespace PetFoodDB\Model;

use Traversable;

/**
 * Class BaseList - generic list implementation for holding collection of objects, with iterator
 *
 * @author  Tammy Delahaye <tammyd@ea.com>
 * @package Pulse\UT\ServiceBundle\Model
 */
class BaseList implements \Countable, \IteratorAggregate
{
    /**
     * @type array $items Holds items in list
     */
    protected $items;

    /**
     * Constructor
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Adds an item to the end of the list
     *
     * @param mixed $item Item to add
     *
     * @return $this
     */
    public function append($item)
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * Deletes all items from list;
     *
     * @return $this
     */
    public function clear()
    {
        $this->items = [];

        return $this;
    }

    /**
     * Returns the number of items
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Alias of count
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count();
    }

    /**
     * Retrieve an external iterator
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     *                     <b>Traversable</b>
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Returns the type of list (useful with extended classes)
     *
     * @return string
     */
    public function getType()
    {
        $ref = new \ReflectionClass($this);

        return $ref->getShortName();
    }

    /**
     * Set the list of items. Overwrites existing content.
     *
     * @param array $items Bunch of items to add
     *
     * @return $this;
     */
    public function setItems(array $items)
    {
        $this->items = [];

        foreach ($items as $item) {
            $this->append($item);
        }

        return $this;
    }

    /**
     * Get an array of all the items in the list. Same as all().
     *
     * @return array
     */
    public function getItems()
    {
        return $this->all();
    }

    /**
     * Get an array of all the items in the list. Same as getItems(). getItems is useful for json get/set serialization.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Gets a single list item by index
     *
     * @param int $index Index
     *
     * @return mixed
     */
    public function get($index)
    {
        return $this->items[$index];
    }

    /**
     * Appends multiple items to the end of the list
     *
     * @param array $items items to add
     *
     * @return $this
     */
    public function appendMany(array $items)
    {
        foreach ($items as $item) {
            $this->append($item);
        }

        return $this;
    }

    /**
     * Lookup objects in list, based on value of a custom function
     *
     * @param string $getter getter function used to retrieve key value in list
     * @param mixed  $value  value to compare against
     *
     * @return mixed|null
     */
    public function lookup($getter, $value)
    {
        foreach ($this->all() as $item) {
            if (method_exists($item, $getter)) {
                $test = $item->$getter();
                if ($test == $value) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Determines whether an object exists in the list
     *
     * @param mixed $object Object to search for
     *
     * @return bool
     */
    public function exists($object)
    {
        return in_array($object, $this->items);
    }

    /**
     * Removes a single item from the list if it exist.
     *
     * @param mixed $object object to remove
     *
     * @return $this
     */
    public function remove($object)
    {
        foreach ($this->items as $i => $item) {
            if ($item == $object) {
                unset($this->items[$i]);
                $this->items = array_values($this->items);

                break;
            }
        }

        return $this;
    }
}
