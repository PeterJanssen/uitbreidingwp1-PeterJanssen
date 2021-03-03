<?php


namespace App\Model;


use InvalidArgumentException;
use PDO;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PDOAssetModel implements AssetModel
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function createAssetFromRow($assetRow)
    {
        return [
            'id' => $assetRow['id'],
            'roomId' => $assetRow['roomId'],
            'name' => $assetRow['name']
        ];
    }

    public function getAssetsByRoomId($roomId)
    {
        $pdo = $this->connection->getPDO();
        $query = "SELECT * FROM assets WHERE roomId = :roomId;";
        $statement = $pdo->prepare($query);
        $statement->bindParam(":roomId", $roomId, PDO::PARAM_INT);
        $statement->execute();

        $assets = [];

        while ($assetRow = $statement->fetch()) {
            $asset = $this->createAssetFromRow($assetRow);
            array_push($assets, $asset);
        }

        return $assets;
    }

    public function getAssetIdByAssetName($name)
    {
        $this->validateName($name);

        $pdo = $this->connection->getPDO();
        $query = "SELECT id FROM assets WHERE name = :name;";
        $statement = $pdo->prepare($query);
        $statement->bindParam(":name", $name, PDO::PARAM_STR);
        $statement->bindColumn(1, $id, PDO::PARAM_INT);
        $statement->execute();
        $statement->fetch(PDO::FETCH_BOUND);
        if (!$id) {
            throw new NotFoundHttpException("No asset found for name = $name.");
        }

        return $id;
    }

    public function validateName($name)
    {
        if (!(is_string($name) && strlen($name) <= 45 && strlen($name) >= 5)) {
            throw new InvalidArgumentException("The name must be a string no longer than 45 characters and no less than 5");
        }
    }
}
