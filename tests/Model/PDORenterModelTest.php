<?php

namespace App\Tests\Model;

use App\Model\Connection;
use App\Model\PDORenterModel;
use App\Model\PDORoomModel;
use PDO;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PDORenterModelTest extends TestCase
{
    private $connection;
    private $pdo;
    private $renterModel;
    private $roomModel;

    public function setUp()
    {
        $sql = file_get_contents(__DIR__ . "/../../asset-management-sqlite.sql");
        $this->connection = new Connection("sqlite::memory:");
        $this->pdo = $this->connection->getPDO();
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec($sql);

        $this->roomModel = new PDORoomModel($this->connection);
        $this->renterModel = new PDORenterModel($this->connection, $this->roomModel);
    }

    public function tearDown()
    {
        $this->pdo = null;
        $this->connection = null;
        $this->roomModel = null;
        $this->renterModel = null;
    }

    public function providerRenters()
    {
        return [
            ['id' => 1, 'firstName' => 'Peter', 'lastName' => "Janssen", 'fromDate' => '2021-07-20', 'toDate' => '2021-08-20', 'roomId' => 443],
            ['id' => 2, 'firstName' => 'Ben', 'lastName' => "Merken", 'fromDate' => '2021-02-21', 'toDate' => '2021-02-24', 'roomId' => 444]
        ];
    }

    public function providerNewRenter()
    {
        return [
            [['firstName' => 'Test', 'lastName' => 'Test']]
        ];
    }

    public function providerInvalidNewRenters()
    {
        return [
            ['firstName' => str_repeat('k', 46), 'lastName' => str_repeat('k', 46)]
        ];
    }

    public function providerRenterToUpdate()
    {
        return [
            [
                'id' => 1,
                [
                    'fromDate' => '2021-07-20',
                    'toDate' => '2021-08-20',
                    'roomId' => 443
                ],
                [
                    'id' => 1,
                    'firstName' => 'Peter',
                    'lastName' => "Janssen",
                    'fromDate' => '2021-07-20',
                    'toDate' => '2021-08-20',
                    'roomId' => 443
                ]
            ]
        ];
    }

    public function providerDatesAndExpectedRenter()
    {
        return [
            ['fromDate' => '2021-07-20', 'toDate' => '2021-08-20',
                [[
                    'id' => 1,
                    'firstName' => 'Peter',
                    'lastName' => "Janssen",
                    'fromDate' => '2021-07-20',
                    'toDate' => '2021-08-20',
                    'roomId' => 443
                ]]
            ],
            ['fromDate' => '2021-02-21', 'toDate' => '2021-02-24',
                [[
                    'id' => 2,
                    'firstName' => 'Ben',
                    'lastName' => "Merken",
                    'fromDate' => '2021-02-21',
                    'toDate' => '2021-02-24',
                    'roomId' => 444
                ]]
            ]
        ];
    }

    public function providerValidRenterIdAndExpectedRenter()
    {
        return [
            [
                'id' => 1,
                [
                    'id' => 1,
                    'firstName' => 'Peter',
                    'lastName' => "Janssen",
                    'fromDate' => '2021-07-20',
                    'toDate' => '2021-08-20',
                    'roomId' => 443
                ]
            ]
        ];
    }

    public function providerNonExistentId()
    {
        return [
            [0],
        ];
    }

    public function testGetRentersReturnsAllRenters_true()
    {
        $renterModel = new PDORenterModel($this->connection, $this->roomModel);
        $expectedRenters = $this->providerRenters();
        $actualRenters = $renterModel->getRenters();
        $this->assertEquals('array', gettype($actualRenters));
        $this->assertEquals($expectedRenters, $actualRenters);
    }

    /**
     * @dataProvider providerValidRenterIdAndExpectedRenter
     * @param $id
     * @param $expectedRenter
     */
    public function testGetRenterById_validId_true($id, $expectedRenter)
    {
        $actualRenter = $this->renterModel->getRenterById($id);

        $this->assertEquals($expectedRenter, $actualRenter);
    }

    /**
     * @dataProvider providerNonExistentId
     * @param $id
     */
    public function testGetTicketsById_nonExistentId_throwsNotFoundHttpException($id)
    {
        $this->expectException(NotFoundHttpException::class);
        $this->renterModel->getRenterById($id);
    }

    /**
     * @dataProvider providerNewRenter
     * @param $jsonData
     */
    public function testCreateRenter_validJsonData_RenterCreated($jsonData)
    {
        $rowCount = $this->renterModel->createRenter($jsonData);

        $this->assertEquals(1, $rowCount);
    }

    /**
     * @dataProvider providerDatesAndExpectedRenter
     * @param $fromDate
     * @param $toDate
     * @param $expectedRenters
     */
    public function testGetRentersBetweenTwoDates_validDates_true($fromDate, $toDate, $expectedRenters)
    {
        $actualRenters = $this->renterModel->getRentersBetweenTwoDates($fromDate, $toDate);

        $this->assertEquals($expectedRenters, $actualRenters);
    }

    /**
     * @dataProvider providerRenterToUpdate()
     * @param $id
     * @param $jsonData
     * @param $expectedRenter
     */
    public function testUpdateRenter_existingRoom_true($id, $jsonData, $expectedRenter)
    {
        $this->renterModel->updateRenterRentedRoom($id, $jsonData);
        $actualRenter = $this->renterModel->getRenterById($id);
        $this->assertEquals($expectedRenter, $actualRenter);
    }

    public function providerInvalidDate()
    {
        return [['date' => 'fddfsdssd']];
    }

    public function providerToDateIsBeforeFromDate()
    {
        return [['todate' => '2021-07-20',
            'fromDate' => '2021-08-20']];
    }

    public function providerToDateIsBeforeNow()
    {
        return [['todate' => '1999-08-20', 'fromDate' => '1999-08-20']];
    }

    /**
     * @dataProvider providerInvalidDate
     * @param $date
     */
    public function testValidateDate_invalidDate_thrownInvalidArgumentException($date)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->renterModel->validateDate($date);
    }

    /**
     * @dataProvider providerToDateIsBeforeFromDate
     * @param $toDate
     * @param $fromDate
     */
    public function testCheckIfToDateIsBeforeFromDate_thrownInvalidArgumentException($toDate, $fromDate)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->renterModel->checkIfToDateIsBeforeFromDate($fromDate, $toDate);
    }

    /**
     * @dataProvider providerToDateIsBeforeNow
     * @param $fromDate
     * @param $todate
     */
    public function testCheckIfToDateIsBeforeNow_thrownInvalidArgumentException($fromDate, $todate)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->renterModel->checkIfDatesAreBeforeNow($fromDate, $todate);
    }

    public function providerInvalidRenterData()
    {
        return [
            [[]],
            [[
                'firstName' => str_repeat("a", 46),
                'lastName' => "test"
            ]],
            [[
                'firstName' => "test",
                'lastName' => str_repeat("a", 46)
            ]]
        ];
    }

    /**
     * @dataProvider providerInvalidRenterData
     * @param $renterData
     */
    public function testValidateRenterData_thrownInvalidArgumentException($renterData)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->renterModel->validateRenterData($renterData);
    }

    public function providerInvalidRenterIdForUpdate()
    {
        return [
            [
                'id' => 0,
                [
                    'fromDate' => '2021-07-20',
                    'toDate' => '2021-08-20',
                    'roomId' => 443
                ]
            ]
        ];
    }

    /**
     * @dataProvider providerInvalidRenterIdForUpdate
     * @param $id
     * @param $jsonData
     */
    public function testUpdateRenterRentedRoom_InvalidId_thrownNotFoundHttpExceptionException($id, $jsonData)
    {
        $this->expectException(NotFoundHttpException::class);

        $this->renterModel->updateRenterRentedRoom($id, $jsonData);
    }
}
