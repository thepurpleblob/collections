<?php

namespace thepurpleblob\collections\library;


use Exception;


/**
 * Class Admin
 * @package thepurpleblob\collections\library
 * @return array list of services
 */
class Admin {

    /**
     * Process uploaded CSV file
     * @param string $csv csv data
     */
    public function load_csv($csv) {
        $parser = new \parseCSV($csv);

        // run through data
        foreach ($parser->data as $row) {
            $item = \ORM::for_table('items')
                ->where(array(
                    'institution_code' => $row['institution.code'],
                    'object_number' => $row['object_number']
                ))->find_one();

            if (!$item) {
                $item = \ORM::for_table('items')->create();
            }

            $item->institution_code = $row['institution.code'];
            $item->object_number = $row['object_number'];
            $item->title = $row['title'];
            $item->description = $row['description'];
            $item->reproduction_reference = $row['reproduction.reference'];
            $item->save();
        }
    }

    /**
     * Get the items data
     */
    public function getItems() {
        $items = \ORM::for_table('items')->find_many();

        return $items;
    }

    /**
     * Upload picture files
     * @param string $basename
     */
    public function load_pictures($basename) {
        global $CFG;

        // Check if anything submitted
        if (empty($_FILES[$basename]['name'])) {
            return false;
        }

        // Unzip
        $zip = new \ZipArchive();
        if ($zip->open($_FILES[$basename]['tmp_name'])) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $path =  $zip->statIndex($i);
                $name = $path['name'];
                $zip->extractTo($CFG->dataroot, $name);
            }
        }

        return false;
    }


}