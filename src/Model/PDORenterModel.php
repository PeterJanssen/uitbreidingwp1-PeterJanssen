<?php

namespace App\Model;

use DateTime;
use InvalidArgumentException;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PDORenterModel implements RenterModel
{
    private $connection;
    private $pdo;
    private $roomModel;

    public function __construct(Connection $connection, RoomModel $model)
    {
        $this->connection = $connection;
        $this->roomModel = $model;
        $this->pdo = $this->connection->getPDO();
    }

    public function createRenterFromRow($renterRow)
    {
        return [
            'id' => $renterRow['id'],
            'firstName' => $renterRow['firstName'],
            'lastName' => $renterRow['lastName'],
            'fromDate' => $renterRow['fromDate'],
            'toDate' => $renterRow['toDate'],
            'roomId' => $renterRow['roomId']
        ];
    }

    public function getRenterById($id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM renters WHERE id = :id;');
        $statement->bindParam(":id", $id);

        $statement->execute();

        $renterRow = $statement->fetch();

        if (empty($renterRow)) {
            throw new NotFoundHttpException("No renter found for id = $id");
        }

        return $this->createRenterFromRow($renterRow);
    }

    public function getRenters()
    {
        $statement = $this->pdo->prepare('SELECT * from renters;');

        $statement->execute();
        $renters = [];

        while ($renterRow = $statement->fetch()) {
            $renter = $this->createRenterFromRow($renterRow);
            array_push($renters, $renter);
        }

        return $renters;
    }

    public function getRentersBetweenTwoDates($fromDate, $toDate)
    {
        $this->validateDate($fromDate);
        $this->validateDate($toDate);
        $this->checkIfDatesAreBeforeNow($fromDate, $toDate);
        $this->checkIfToDateIsBeforeFromDate($fromDate, $toDate);

        $statement = $this->pdo->prepare('SELECT * from renters WHERE fromDate >= :fromDate AND toDate <= :toDate;');
        $statement->bindParam(":fromDate", $fromDate);
        $statement->bindParam(":toDate", $toDate);

        $statement->execute();
        $renters = [];

        while ($renterRow = $statement->fetch()) {
            $renter = $this->createRenterFromRow($renterRow);
            array_push($renters, $renter);
        }

        return $renters;
    }

    public function createRenter($jsonData)
    {
        $this->validateRenterData($jsonData);

        $firstName = $jsonData["firstName"];
        $lastName = $jsonData["lastName"];

        $insertQuery = "INSERT INTO renters(firstName, lastName) VALUES(:firstName, :lastName);";
        $statement = $this->pdo->prepare($insertQuery);
        $statement->bindParam(":firstName", $firstName);
        $statement->bindParam(":lastName", $lastName);
        $statement->execute();

        return $statement->rowCount();
    }

    public function updateRenterRentedRoom($id, $jsonData)
    {
        $fromDate = $jsonData['fromDate'];
        $toDate = $jsonData['toDate'];
        $roomId = $jsonData['roomId'];

        $this->validateDate($fromDate);
        $this->validateDate($toDate);
        $this->checkIfDatesAreBeforeNow($fromDate, $toDate);
        $this->checkIfToDateIsBeforeFromDate($fromDate, $toDate);

        $amountOfRenters = $this->checkIfRoomIsAvailable($fromDate, $toDate, $roomId);

        if ($amountOfRenters > 0) {
            throw new InvalidArgumentException("Room $roomId not available to rent between $fromDate and $toDate");
        } else {
            $updateQuery = "UPDATE renters SET fromDate = :fromDate, toDate = :toDate, roomId = :roomId WHERE id = :id;";

            $statement = $this->pdo->prepare($updateQuery);
            $statement->bindParam(":fromDate", $fromDate, PDO::PARAM_STR);
            $statement->bindParam(":toDate", $toDate, PDO::PARAM_STR);
            $statement->bindParam(":roomId", $roomId, PDO::PARAM_INT);
            $statement->bindParam(":id", $id, PDO::PARAM_INT);
            $statement->execute();

            if ($statement->rowCount() == 0) {
                throw new NotFoundHttpException("There is no renter with id $id.");
            }
        }
    }

    public function validateDate($date)
    {
        if (!(bool)strtotime($date)) {
            throw new InvalidArgumentException("invalid date");
        }
    }

    public function checkIfToDateIsBeforeFromDate($fromDate, $toDate)
    {
        if (strtotime($toDate) < strtotime($fromDate)) {
            throw new InvalidArgumentException("End date: $toDate is before start date: $fromDate");
        }
    }

    public function checkIfDatesAreBeforeNow($fromDate, $toDate)
    {
        if (strtotime($fromDate) < strtotime('now') || strtotime($toDate) < strtotime('now')) {
            throw new InvalidArgumentException("Dates cannot be before today");
        }
    }

    public function validateRenterData($jsonData)
    {
        if (!(isset($jsonData["firstName"], $jsonData['lastName']))) {
            throw new InvalidArgumentException("The JSON data is not valid");
        }

        if (strlen($jsonData["firstName"]) > 45) {
            throw new InvalidArgumentException("firstName is too long");
        }

        if (strlen($jsonData['lastName']) > 45) {
            throw new InvalidArgumentException("lastName is too long");
        }
    }

    public function checkIfRoomIsAvailable($fromDate, $toDate, $roomId)
    {
        $statement = $this->pdo->prepare('SELECT * from renters WHERE (roomId = :roomId) AND (fromDate <= :toDate AND toDate >= :fromDate);');
        $statement->bindParam(":fromDate", $fromDate);
        $statement->bindParam(":toDate", $toDate);
        $statement->bindParam(":roomId", $roomId);
        $statement->execute();
        return $statement->rowCount();
    }
}