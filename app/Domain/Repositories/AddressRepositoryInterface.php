<?php
namespace App\Domain\Repositories;
use App\Domain\Entities\Address;
interface AddressRepositoryInterface {
    public function createOrUpdate(?string $addressId, array $data): Address;
}
