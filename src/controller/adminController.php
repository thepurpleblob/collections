<?php

namespace thepurpleblob\collections\controller;

use thepurpleblob\core\coreController;

class AdminController extends coreController {

    protected $adminlib;

    /**
     * Constructor
     */
    public function __construct($exception = false)
    {
        parent::__construct($exception);

        // Library
        $this->adminlib = $this->getLibrary('Admin');
    }


    // default (no route) page shows admin menu
    public function indexAction() {
        $this->require_login('ROLE_ADMIN', 'admin/index');


        // Display the menu
        $this->View('admin/index', array(

        ));
    }

    /**
     * action for uploading data files
     */
    public function uploadAction() {
        $this->require_login('ROLE_ADMIN', 'admin/upload');

        // anything submitted?
        if ($data = $this->getRequest()) {

            // Try for csv data
            if ($csv =$this->form->file_get_contents('csv')) {
                $this->adminlib->load_csv($csv);
            }

        }

        // Create form
        $form = new \stdClass();
        $form->csv = $this->form->filepicker('csv', 'CSV file');
        $form->pictures = $this->form->filepicker('pictures', 'Pictures zip file');

        // Display the form
        $this->View('admin/upload', array(
            'form' => $form,
        ));
    }

    /**
     * display data
     */
    public function displayAction() {
        $this->require_login('ROLE_ADMIN', 'admin/upload');

        // Get data (may be more sophisticated in time)
        $items = $this->adminlib->getItems();

        $this->View('admin/display', array(
            'items' => $items,
        ));
    }
}
