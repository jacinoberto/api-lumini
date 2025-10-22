<?php
namespace App\Domain\Repositories;
use App\Domain\Entities\ZipCode;
interface ZipCodeRepositoryInterface {
    public function findOrCreate(array $search, array $data): ZipCode;
}
