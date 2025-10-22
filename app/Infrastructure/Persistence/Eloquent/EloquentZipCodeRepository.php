<?php
namespace App\Infrastructure\Persistence\Eloquent;
use App\Domain\Entities\ZipCode;
use App\Domain\Repositories\ZipCodeRepositoryInterface;
class EloquentZipCodeRepository implements ZipCodeRepositoryInterface {
    public function findOrCreate(array $search, array $data): ZipCode {
        return ZipCode::firstOrCreate($search, $data);
    }
}
