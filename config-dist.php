<?php

unset($CFG);
$CFG = new stdClass;

// Database stuff
$CFG->dsn = "mysql:host=127.0.0.1;dbname=collections";
$CFG->dbuser = '..user..';
$CFG->dbpass = '..password..';

// Project name
// (Needs to match composer autoloader \\thepurpleblob\\projectname\\)
$CFG->projectname = 'collections';

// Routes (default if none specified)
$CFG->defaultroute = 'site/index';

// paths
$CFG->www = 'http://localhost/collections';

$CFG->dirroot = '/var/www/collections';

$CFG->dataroot = '/var/www/collections/data';

// Email stuff
$CFG->smtpd_host = '';
$CFG->backup_email = '';

// Contact number
$CFG->help_number = '01506 825855';

