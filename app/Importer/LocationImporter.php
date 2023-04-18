<?php

namespace App\Importer;

use App\Models\Location;

/**
 * When we are importing users via an Asset/etc import, we use createOrFetchUser() in
 * App\Importer.php. [ALG]
 *
 * Class LocationImporter
 */
class LocationImporter extends ItemImporter
{
    protected $locations;

    public function __construct($filename)
    {
        parent::__construct($filename);
    }

    protected function handle($row)
    {
        parent::handle($row);
        $this->createLocationIfNotExists($row);
    }

    /**
     * Create a location if a duplicate does not exist.
     * @todo Investigate how this should interact with Importer::createLocationIfNotExists
     *
     * @author A. Gianotto
     * @since 6.1.0
     * @param array $row
     */
    public function createLocationIfNotExists(array $row)
    {

        // Pull the records from the CSV to determine their values
        $this->item['name'] = $this->findCsvMatch($row, 'name');
        $this->item['address'] = $this->findCsvMatch($row, 'address');
        $this->item['address2'] = $this->findCsvMatch($row, 'address2');
        $this->item['city'] = $this->findCsvMatch($row, 'city');
        $this->item['state'] = $this->findCsvMatch($row, 'state');
        $this->item['country'] = $this->findCsvMatch($row, 'country');
        $this->item['zip'] = $this->findCsvMatch($row, 'zip');
        $this->item['currency'] = $this->findCsvMatch($row, 'currency');
        $this->item['ldap_ou'] = $this->findCsvMatch($row, 'ldap_ou');
        $this->item['manager'] = $this->findCsvMatch($row, 'manager');
        $this->item['manager_username'] = $this->findCsvMatch($row, 'manager_username');
        $this->item['user_id'] = \Auth::user()->id;

        if ($this->findCsvMatch($row, 'parent_location')) {
            $this->item['parent_id'] = $this->createOrFetchLocation($this->findCsvMatch($row, 'parent_location'));
        }


        if ($manager = $this->createOrFetchUser($row, 'manager')) {
            $this->item['manager_id'] = $manager->id;
        }



        \Log::debug(print_r($this->item, true));
        $location = Location::where('name', '=', $this->findCsvMatch($row, 'name'))->first();


        // Location exists
        if (($location) && ($location->count() > 0)) {
            \Log::debug('A matching Location '.$this->item['name'].' already exists.');
            $this->log('Updating Location from CSV import');
            $location->update($this->sanitizeItemForStoring($location));
            // Location does not exist
        } else {
            $this->log('No matching location ('.$this->item['name'].'), creating one');
            $location = new Location();
            $location->fill($this->sanitizeItemForStoring($location));
        }

        if ($location->save()) {
            $this->log('Location '.$location->name.' created from CSV import');
            return $location;

        } else {
            \Log::debug($location->getErrors());
            return $location->errors;
        }


    }
}