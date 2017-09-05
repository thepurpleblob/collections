<?php

namespace thepurpleblob\railtour\controller;

use thepurpleblob\core\coreController;

class AdminController extends coreController {


    // default (no route) page shows available services
    public function mainAction() {

        // Display the services
        $this->View('admin/main', array(
            'services' => $services,
            'anyservices' => !empty($services),
        ));
    }
}
