<?php

namespace thepurpleblob\collections\controller;

use thepurpleblob\core\coreController;

class SiteController extends coreController {

    protected $lib;

    /**
     * Constructor
     */
    public function __construct($exception = false)
    {
        parent::__construct($exception);

        // Library
        $this->lib = $this->getLibrary('Lib');
    }


    // default (no route) page shows site page
    public function indexAction() {


        // Display the main site page
        $this->View('site/index', array(

        ));
    }

    /**
     * Search for items
     *
     */
    public function SearchAction() {
        global $CFG;

        // anything submitted?
        if ($data = $this->getRequest()) {
            $search = $data['search'];
            $items = $this->lib->get_search($search);

            $count_plural = count($items) == 1 ? '' : 's';

            $this->View('site/results', array(
                'anyitems' => !empty($items),
                'count' => count($items),
                'count_plural' => $count_plural,
                'items' => $items,
                'image_url' => $CFG->www . '/data/',
            ));
        } else {
            $this->View('site/index', array());
        }
    }

    /**
     * Show single result
     * @param int $itemid
     */
    public function SingleAction($itemid) {
        global $CFG;

        $item = $this->lib->getItem($itemid);

        $this->View('site/single', array(
            'item' => $item,
            'image_url' => $CFG->www . '/data/',
        ));
    }

}
