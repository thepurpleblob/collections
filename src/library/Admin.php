<?php

namespace thepurpleblob\railtour\library;

// Lifetime of incomplete purchases in seconds
define('PURCHASE_LIFETIME', 3600);

use Exception;


/**
 * Class Admin
 * @package thepurpleblob\railtour\library
 * @return array list of services
 */
class Admin {

    private $stations = null;


    /**
     * Set up the stations json data
     * Need to call this first, if you want to use this data
     */
    public function initialiseStations() {
        global $CFG;

        $stationsjson = file_get_contents($CFG->dirroot . '/src/assets/json/stations.json');
        $locations = json_decode($stationsjson);
        $locations = $locations->locations;
        $crs = array();
        foreach ($locations as $location) {
            $crs[$location->crs] = $location;
        }
        $this->stations = $crs;
    }

    /**
     * Find station/location from crs
     * @param string $crs
     * @return location object
     */
    public function getCRSLocation($crs) {
        if (isset($this->stations[$crs])) {
            return $this->stations[$crs];
        } else {
            return null;
        }
    }

    /**
     * munge service for formatting
     * @param object $service
     * @return object
     */
    public function formatService($service) {
        $service->unixdate = strtotime($service->date);
        $service->formatteddate = date('d/m/Y', $service->unixdate);
        $service->formattedvisible = $service->visible ? 'Yes' : 'No';
        $service->formattedcommentbox = $service->commentbox ? 'Yes' : 'No';
        $service->formattedmealavisible = $service->mealavisible ? 'Yes' : 'No';
        $service->formattedmealbvisible = $service->mealbvisible ? 'Yes' : 'No';
        $service->formattedmealcvisible = $service->mealcvisible ? 'Yes' : 'No';
        $service->formattedmealdvisible = $service->mealdvisible ? 'Yes' : 'No';
        $service->formattedmealaname = $service->mealaname ? $service->mealaname : 'Meal A';
        $service->formattedmealbname = $service->mealbname ? $service->mealbname : 'Meal B';
        $service->formattedmealcname = $service->mealcname ? $service->mealcname : 'Meal C';
        $service->formattedmealdname = $service->mealdname ? $service->mealdname : 'Meal D';

        // ETicket selected
        if ($service->eticketenabled) {
            $etmode = $service->eticketforce ? 'Enabled: Forced' : 'Enabled: Optional';
        } else {
            $etmode = 'Disabled';
        }
        $service->formattedetmode = $etmode;

        return $service;
    }

    /**
     * munge services for display
     * @param array $services
     * @return array
     */
    public function formatServices($services) {
        foreach ($services as $service) {
            $this->formatService($service);
        }

        return $services;
    }

    /**
     * Get all services
     * @return array
     */
    public function getServices() {
        $allservices = \ORM::forTable('service')->order_by_asc('date')->findMany();

        return $allservices;
    }

    /**
     * Create new Service
     * @return object
     */
    public function createService() {
        $service = \ORM::for_table('service')->create();
        $service->code = '';
        $service->name = '';
        $service->description = '';
        $service->visible = true;
        $service->date = date('Y-m-d', time());
        $service->mealaname = 'Breakfast';
        $service->mealbname = 'Lunch';
        $service->mealcname = 'Dinner';
        $service->mealdname = 'Not used';
        $service->mealaprice = 0;
        $service->mealbprice = 0;
        $service->mealcprice = 0;
        $service->mealdprice = 0;
        $service->mealavisible = 0;
        $service->mealbvisible = 0;
        $service->mealcvisible = 0;
        $service->mealdvisible = 0;
        $service->singlesupplement = 10.00;
        $service->maxparty = 16;
        $service->commentbox = 0;
        $service->eticketenabled = 0;
        $service->eticketforce = 0;

        return $service;
    }

    /**
     * Get a single service
     * @param int serviceid (0 = new one)
     * @return object
     */
    public function getService($id = 0) {
        $service = \ORM::forTable('service')->findOne($id);

        if ($service === 0) {
            $service = $this->createService();
            return $service;
        }

        if (!$service) {
            throw new Exception('Unable to find Service record for id = ' . $id);
        }

        return $service;
    }

    /**
     * Delete service
     * @param int $serviceid
     */
    public function deleteService($serviceid) {
        $service = $this->adminlib->getService($serviceid);

        // If there are purchases, we're out of here
        if (\ORM::forTable('purchase')->where('serviceid', $serviceid)->count()) {
            $haspurchases = true;
        } else {
            $haspurchases = false;

            // anything submitted?
            if ($data = $this->getRequest()) {

                // Delete?
                if (!empty($data['delete'])) {
                    $booking->deleteService($service);
                }
                $this->redirect('service/index');
            }
        }
    }

    /**
     * Get pricebands ordered by destinations (create any new missing ones)
     * @param int $serviceid
     * @param int $pricebandgroupid
     * @param boolean $save
     * @return array
     */
    public function getPricebands($serviceid, $pricebandgroupid, $save=true) {
        $destinations = \ORM::forTable('destination')->where('serviceid', $serviceid)->order_by_asc('destination.name')->findMany();
        if (!$destinations) {
            throw new Exception('No destinations found for serviceid = ' . $serviceid);
        }
        $pricebands = array();
        foreach ($destinations as $destination) {
            $priceband = \ORM::forTable('priceband')->where(array(
                'pricebandgroupid' => $pricebandgroupid,
                'destinationid' => $destination->id,
            ))->findOne();
            if (!$priceband) {
                $priceband = \ORM::forTable('priceband')->create();
                $priceband->serviceid = $serviceid;
                $priceband->pricebandgroupid = $pricebandgroupid;
                $priceband->destinationid = $destination->id;
                $priceband->first = 0;
                $priceband->standard = 0;
                $priceband->child = 0;

                // In some cases we don't want to create it (yet)
                if ($save) {
                    $priceband->save();
                }
            }

            // Add the destination name as a spurious field
            $priceband->name = $destination->name;
            $pricebands[] = $priceband;
        }

        return $pricebands;
    }

    /**
     * Is the priceband group assigned
     * in any joining station
     * @param object $pricebandgroup
     */
    public function isPricebandUsed($pricebandgroup) {

        // find joining stations that specify this group
        $joinings = \ORM::forTable('joining')->where('pricebandgroupid', $pricebandgroup->id)->findMany();

        // if there are any then it is used
        if ($joinings) {
            return true;
        }

        return false;
    }

    /**
     * Get destinations
     * @param int $serviceid
     * @return array
     */
    public function getDestinations($serviceid) {
        $destinations = \ORM::forTable('destination')->where('serviceid', $serviceid)->findMany();

        return $destinations;
    }

    /**
     * Get single destination
     * @param int $destinationid
     * @return object
     */
    public function getDestination($destinationid) {
        $destination = \ORM::forTable('destination')->findOne($destinationid);
        if (!$destination) {
            throw new Exception('Destination was not found id=' . $destinationid);
        }

        return $destination;
    }

    /**
     * Create new Destination
     * @param int $serviceid
     * @return object
     */
    public function createDestination($serviceid) {
        $destination = \ORM::forTable('destination')->create();
        $destination->serviceid = $serviceid;
        $destination->name = '';
        $destination->crs = '';
        $destination->description = '';
        $destination->bookinglimit = 0;

        return $destination;
    }

    /**
     * Delete a destination
     * Note, this will also delete associated priceband data
     * @param int $destinationid
     * @return $serviceid
     */
    public function deleteDestination($destinationid) {
        $destination = $this->getDestination($destinationid);
        $serviceid = $destination->serviceid;
        if (!$this->isDestinationUsed($destination)) {

            // delete pricebands associated with this
            \ORM::for_table('Priceband')->where('destinationid', $destinationid)->delete_many();

            // delete the destination
            $destination->delete();
        }

        return $serviceid;
    }

    /**
     * Is destination used?
     * Checks if destination can be deleted
     * @param object $destination
     * @return boolean true if used
     */
    public function isDestinationUsed($destination) {

        // find pricebands that specify this destination
        $pricebands = \ORM::forTable('priceband')->where('destinationid', $destination->id)->findMany();

        // if there are non then not used
        if (!$pricebands) {
            return false;
        }

        // otherwise, all prices MUST be 0
        foreach ($pricebands as $priceband) {
            if (($priceband->first > 0) || ($priceband->standard > 0) && ($priceband->child > 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * munge priceband group
     * @param object $pricebandgroup
     * @return object
     */
    private function mungePricebandgroup($pricebandgroup) {
        $pricebandgroupid = $pricebandgroup->id;
        $serviceid = $pricebandgroup->serviceid;
        $bandtable = $this->getPricebands($serviceid, $pricebandgroupid);
        $pricebandgroup->bandtable = $bandtable;

        return $pricebandgroup;
    }

    /**
     * @param array $pricebandgroups
     * @param return array
     */
    public function mungePricebandgroups($pricebandgroups) {
        foreach ($pricebandgroups as $pricebandgroup) {
            $this->mungePricebandgroup($pricebandgroup);
        }

        return $pricebandgroups;
    }

    /**
     * Get pricebandgroups
     * @param int $serviceid
     * @return array
     */
    public function getPricebandgroups($serviceid) {
        $pricebandgroups = \ORM::forTable('pricebandgroup')->where('serviceid', $serviceid)->findMany();

        return $pricebandgroups;
    }

    /**
     * Get priceband group
     * @param int $pricebandgroupid
     * @return object
     */
    public function getPricebandgroup($pricebandgroupid) {
        $pricebandgroup = \ORM::forTable('pricebandgroup')->findOne($pricebandgroupid);

        if (!$pricebandgroup) {
            throw new Exception('Unable to find Pricebandgroup record for id = ' . $pricebandgroupid);
        }

        //$this->mungePricebandgroup($pricebandgroup);

        return $pricebandgroup;
    }

    /**
     * Create new pricebandgroup
     * @param int $serviceid
     * @return object pricebandgroup
     */
    public function createPricebandgroup($serviceid) {
        $pricebandgroup = \ORM::forTable('pricebandgroup')->create();
        $pricebandgroup->serviceid = $serviceid;
        $pricebandgroup->name = '';

        return $pricebandgroup;
    }


    /**
     * Delete priceband group
     * @param int pricebandgroupid
     * @return int serviceid
     */
    public function deletePricebandgroup($pricebandgroupid) {
        $pricebandgroup = $this->getPricebandgroup($pricebandgroupid);
        $serviceid = $pricebandgroup->serviceid;
        if (!$this->isPricebandUsed($pricebandgroup)) {

            // Remove pricebands associated with this group
            \ORM::forTable('priceband')->where('pricebandgroupid', $pricebandgroupid)->deleteMany();

            $pricebandgroup->delete();
        }

        return $serviceid;
    }

    /**
     * Create options list for pricebandgroup select dropdown(s)
     * @param array $pricebandgroups
     * @return associative array
     */
    public function pricebandgroupOptions($pricebandgroups) {
        $options = array();
        foreach ($pricebandgroups as $pricebandgroup) {
            $options[$pricebandgroup->id] = $pricebandgroup->name;
        }

        return $options;
    }

    /**
     * Get
     */

    /**
     * Munge joining
     * @param object $joining
     * @return object
     */
    private function mungeJoining($joining) {
        $pricebandgroup = $this->getPricebandgroup($joining->pricebandgroupid);
        $joining->pricebandname = $pricebandgroup->name;

        return $joining;
    }

    /**
     * Munge joinings
     * @param array $joinings
     * @return array
     */
    public function mungeJoinings($joinings) {
        foreach ($joinings as $joining) {
            $this->mungeJoining($joining);
        }

        return $joinings;
    }

    /**
     * Get joining stations
     * @param int $serviceid
     * @return array
     */
    public function getJoinings($serviceid) {
        $joinings = \ORM::forTable('joining')->where('serviceid', $serviceid)->findMany();

        foreach ($joinings as $joining) {
            $this->mungeJoining($joining);
        }

        return $joinings;
    }

    /**
     * Get joining station
     * $param int $joiningid
     * @return object
     */
    public function getJoining($joiningid) {
        $joining = \ORM::forTable('joining')->findOne($joiningid);
        if (!$joining) {
            throw new \Exception('Unable to find joining, id = ' . $joiningid);
        }

        return $joining;
    }

    /**
     * Create new joining thing
     * @param $serviceid int
     * @param $pricebandgroups array
     * @return object new (empty) joining object
     */
    public function createJoining($serviceid, $pricebandgroups) {
        $joining = \ORM::forTable('joining')->create();
        $joining->serviceid = $serviceid;
        $joining->station = '';
        $joining->crs = '';
        $joining->meala = 0;
        $joining->mealb = 0;
        $joining->mealc = 0;
        $joining->meald = 0;

        // find and set to the first pricebandgoup
        $pricebandgroup = array_shift($pricebandgroups);
        $joining->pricebandgroupid = $pricebandgroup->id;

        return $joining;
    }

    /**
     * Delete joining station
     * @param int $joiningid
     * @return serviceid
     */
    public function deleteJoining($joiningid) {
        $joining = $this->getJoining($joiningid);
        $serviceid = $joining->serviceid;
        $joining->delete();

        return $serviceid;
    }

    /**
     * Get limits
     * @param int $serviceid
     * @return array
     */
    public function getLimits($serviceid) {
        $limits = \ORM::forTable('limits')->where('serviceid', $serviceid)->findOne();

        return $limits;
    }

    /**
     * Clear incomplete purchases that are time expired
     */
    public function deleteOldPurchases() {
        $oldtime = time() - PURCHASE_LIFETIME;
        \ORM::forTable('purchase')
            ->where('completed', 0)
            ->where_lt('timestamp', $oldtime)
            ->delete_many();

        // IF we've deleted the current purchase then we have
        // an interesting problem!

        // See if the current purchase still exists
        if (isset($_SESSION['purchaseid'])) {
            $purchaseid = $_SESSION['purchaseid'];
            $purchase = \ORM::forTable('purchase')->findOne($purchaseid);
            if (!$purchase) {
                unset($_SESSION['key']);
                unset($_SESSION['purchaseid']);

                // Redirect out of here
                $this->controller->View('booking/timeout.html.twig');
            }
        }
    }

    /**
     * Clear the current session data and delete any expired purchases
     */
    public function cleanPurchases() {

        // TODO (fix) remove the key and the purchaseid
        unset($_SESSION['key']);
        unset($_SESSION['purchaseid']);

        // get incomplete purchases
        $this->deleteOldPurchases();
    }

    /**
     * Format purchase for UI
     * @param object $purchase
     * @return object
     */
    public function formatPurchase($purchase) {
        $purchase->unixdate = strtotime($purchase->date);
        $purchase->formatteddate = date('d/m/Y', $purchase->unixdate);
        $purchase->statusclass = '';
        if (!$purchase->status) {
            $purchase->statusclass = 'warning';
        } else if ($purchase->status != 'OK') {
            $purchase->statusclass = 'danger';
        }
        $purchase->formattedeticket = $purchase->eticket ? 'Yes' : 'No';
        $purchase->formattedeinfo = $purchase->einfo ? 'Yes' : 'No';
        $purchase->formattedseatsupplement = $purchase->setsupplement ? 'Yes' : 'No';
        $purchase->formattedclass = $purchase->class == 'F' ? 'First' : 'Standard';

        return $purchase;
    }

    /**
     * Format list of purchases for UI
     * @param array $purchases
     * @return array
     */
    public function formatPurchases($purchases) {
        foreach ($purchases as $purchase) {
            $this->formatPurchase($purchase);
        }

        return $purchases;
    }

    /**
     * Get purchases for service
     * @param int service id
     * @parm bool $complete, only complete purchases
     * @return array
     */
    public function getPurchases($serviceid, $completed = true) {
        $dbcompleted = $completed ? 1 : 0;

        $purchases = \ORM::forTable('purchase')
            ->where(array(
                'serviceid' => $serviceid,
                'completed' => $dbcompleted,
            ))
            ->order_by_asc('timestamp')
            ->findMany();

        return $purchases;
    }

    /**
     * Get single purchase
     * @param int $purchaseid
     * @return object
     */
    public function getPurchase($purchaseid) {
        $purchase = \ORM::forTable('purchase')->findOne($purchaseid);

        if (!$purchase) {
            throw new \Exception('Purchase record not found, id=' . $purchaseid);
        }

        return $purchase;
    }

    /**
     * Do pricebands exist for service
     * @param int $serviceid
     * @return boolean
     */
    public function isPricebandsConfigured($serviceid) {

        // presumably we need at least one pricebandgroup
        $pricebandgroup_count = \ORM::forTable('pricebandgroup')->where('serviceid', $serviceid)->count();
        if (!$pricebandgroup_count) {
            return false;
        }

        // ...and there must be some pricebands too
        $priceband_count = \ORM::forTable('priceband')->where('serviceid', $serviceid)->count();
        if (!$priceband_count) {
            return false;
        }

        return true;
    }

}