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
     * Get categories
     * Map view name to db category
     */
    public function getCategories() {
        return [
            'vehicles' => '',
            'tools' => 'TOOLS & EQUIPMENT',
            'archive' => 'ARCHIVE',
            'signs' => 'SIGNS',
            'art' => 'ART / PUBLICITY / COMMEMORATIVE',
            'costume' => 'COSTUME',
            'maps' => 'MAPS & PLANS',
            'signalling' => 'SIGNALLING EQUIPMENT',
            'vehicle' => '',
            'station' => '',
            'people' => '',
            'miscellaneous' => 'IMAGES',
        ];
    }

    /**
     * Get headings
     * Map view name to db category
     */
    public function getHeadings() {
        return [
            'vehicles' => 'Vehicles',
            'tools' => 'Tools & Equipment',
            'archive' => 'Archive',
            'signs' => 'Signs',
            'art' => 'Art, Publicity & Commemorative',
            'costume' => 'Costume',
            'maps' => 'Maps & Pans',
            'signalling' => 'Signalling',
            'vehicle' => 'Vehicle',
            'station' => 'Stations',
            'people' => 'People',
            'miscellaneous' => 'Miscellaneous',
        ];
    }

    /**
     * Get heading
     * @param string $category
     * @return string
     */
    public function getHeading($category) {
        $headings = $this->getHeadings();
        if (!empty($headings[$category])) {
            return $headings[$category];
        } else {
            return '';
        }
    }

    /**
     * Get the items data
     * @param string $category
     */
    public function getItems($category = '') {
        if (!$category) {
            $items = \ORM::for_table('items')->find_many();
        } else {
            $categories = $this->getCategories();
            if (!empty($categories[$category])) {
                $dbcategory = $categories[$category];
                $items = \ORM::for_table('items')->where(['object_category' => $dbcategory])->find_many();
            } else {
                $items = [];
            }
        }

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
