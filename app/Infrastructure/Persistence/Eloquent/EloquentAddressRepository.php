<?php
namespace App\Infrastructure\Persistence\Eloquent;
use App\Domain\Entities\Address;
use App\Domain\Repositories\AddressRepositoryInterface;
class EloquentAddressRepository implements AddressRepositoryInterface {
    public function createOrUpdate(?string $addressId, array $data): Address {
        return Address::updateOrCreate(['id' => $addressId], $data);
    }
}
