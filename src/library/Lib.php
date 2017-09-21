<?php

namespace thepurpleblob\collections\library;


use Exception;


/**
 * Class Admin
 * @package thepurpleblob\collections\library
 * @return array list of services
 */
class Lib {

    /**
     * Get the items data
     */
    public function getItems() {
        $items = \ORM::for_table('items')->find_many();

        return $items;
    }

    /**
     * Text search for data
     * @param string $search free search text
     */
    public function get_search($search) {
        $items = \ORM::for_table('items')
            ->where_raw('MATCH (title, description) AGAINST (? IN NATURAL LANGUAGE MODE)', array($search))
            ->find_many();

        return $items;
    }

    /**
     * Find single item
     * @param int $itemid
     */
    public function getItem($itemid) {
        $item = \ORM::for_table('items')->find_one($itemid);
        if (!$item) {
            throw new \Exception('Item not found');
        }

        return $item;
    }

}