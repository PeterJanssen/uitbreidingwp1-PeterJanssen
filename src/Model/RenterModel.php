<?php


namespace App\Model;


interface RenterModel
{
    public function getRenterById($id);

    public function getRenters();

    public function getRentersBetweenTwoDates($fromDate, $toDate);

    public function updateRenterRentedRoom($id, $jsonData);

    public function createRenter($jsonData);
}