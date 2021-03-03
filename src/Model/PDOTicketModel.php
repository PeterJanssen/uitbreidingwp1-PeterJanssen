<?php


namespace App\Model;


use InvalidArgumentException;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PDOTicketModel implements TicketModel
{
    private $connection;
    private $pdo;
    private $assetModel;

    public function __construct(Connection $connection, AssetModel $model)
    {
        $this->connection = $connection;
        $this->assetModel = $model;
        $this->pdo = $this->connection->getPDO();
    }

    public function createTicketFromRow($ticketRow)
    {
        return [
            "id" => $ticketRow['id'],
            "assetId" => $ticketRow['assetId'],
            "numberOfVotes" => $ticketRow['numberOfVotes'],
            "description" => $ticketRow['description']
        ];
    }

    public function getTicketsByAssetName($assetName)
    {
        $this->validateAssetName($assetName);

        $tickets = [];

        $assetId = $this->assetModel->getAssetIdByAssetName($assetName);
        $query = "SELECT * FROM tickets WHERE assetId = :assetId;";
        $statement = $this->pdo->prepare($query);
        $statement->bindParam(":assetId", $assetId, PDO::PARAM_INT);

        $statement->execute();

        while ($ticketRow = $statement->fetch()) {
            $ticket = $this->createTicketFromRow($ticketRow);
            array_push($tickets, $ticket);
        }
        if (empty($tickets)) {
            throw new NotFoundHttpException("No tickets found for assetName = $assetName");
        }

        return $tickets;
    }

    public function getTicketById($id)
    {
        $query = "SELECT * FROM tickets WHERE id = :id;";
        $statement = $this->pdo->prepare($query);
        $statement->bindParam(":id", $id, PDO::PARAM_INT);

        $statement->execute();
        $ticketRow = $statement->fetch();

        if (empty($ticketRow)) {
            throw new NotFoundHttpException("No ticket found for id = $id");
        }

        return $this->createTicketFromRow($ticketRow);
    }

    public function createTicketByAssetName($assetName, $jsonData)
    {
        $this->validateAssetName($assetName);
        $this->validateTicketData($jsonData);

        $description = $jsonData["description"];
        $this->validateDescription($description);
        $assetId = $this->assetModel->getAssetIdByAssetName($assetName);


        $insertQuery = "INSERT INTO tickets(assetId, description) VALUES(:assetId, :description);";
        $statement = $this->pdo->prepare($insertQuery);
        $statement->bindParam(":assetId", $assetId);
        $statement->bindParam(":description", $description);
        $statement->execute();

        return $statement->rowCount();
    }

    public function incrementNumberOfVotes($id)
    {
        $this->validateId($id);

        $updateQuery = "UPDATE tickets SET numberOfVotes = numberOfVotes + 1 WHERE id = :id;";
        $statement = $this->pdo->prepare($updateQuery);
        $statement->bindParam(":id", $id, PDO::PARAM_INT);
        $statement->execute();
        if ($statement->rowCount() == 0) {
            throw new NotFoundHttpException("There is no ticket with id $id.");
        }
    }

    public function validateAssetName($assetName)
    {
        if (!($assetName && is_string($assetName) && strlen($assetName) <= 45)) {
            throw new InvalidArgumentException("The assetName must be a string, no longer than 45 characters.");
        }
    }

    public function validateTicketData($jsonData)
    {
        if (!(isset($jsonData["description"]))) {
            throw new InvalidArgumentException("The JSON data is not valid");
        }
    }

    public function validateDescription($description)
    {
        if (!(is_string($description) && strlen($description) <= 90 && strlen($description) >= 15)) {
            throw new InvalidArgumentException("The description must be a string, no longer than 90  and no less than 15.");
        }
    }

    public function validateId($id)
    {
        if (!preg_match("/^([1-9][0-9]*)$/", $id)) {
            throw new InvalidArgumentException("The id must be a natural number greater than 0.");
        }
    }
}
