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
        $this->adminlib = $this->getLibrary('User');
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

        // Create form
        $form = new \stdClass();
        $form->csv = $this->form->filepicker('csv', 'CSV file');

        // Display the form
        $this->View('admin/upload', array(
            'form' => $form,
        ));
    }
}
