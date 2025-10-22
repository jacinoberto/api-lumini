<?php

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // 1. Importe o Trait

abstract class Controller
{
    use AuthorizesRequests; // 2. Use o Trait
}
