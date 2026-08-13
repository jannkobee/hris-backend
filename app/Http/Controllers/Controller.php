<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function requireResourcePermissions(string $resource): void
    {
        $this->middleware("permission:view-{$resource}")->only(['index', 'show']);
        $this->middleware("permission:manage-{$resource}")->only(['store', 'update', 'destroy']);
    }
}
