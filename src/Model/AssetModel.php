<?php


namespace App\Model;


interface AssetModel
{
    public function getAssetsByRoomId($roomId);

    public function getAssetIdByAssetName($name);

    public function validateName($name);
}
