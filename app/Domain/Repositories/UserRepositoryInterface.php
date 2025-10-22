<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\User;

interface UserRepositoryInterface
{
    /**
     * Finds a user by their email address.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Creates a new user record in the storage.
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User;
}
