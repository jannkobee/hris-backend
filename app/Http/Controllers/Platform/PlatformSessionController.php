<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Utils\ResponseServiceInterface;

class PlatformSessionController extends Controller
{
    private ResponseServiceInterface $response;

    public function __construct(ResponseServiceInterface $response)
    {
        $this->response = $response;
    }

    public function show()
    {
        return $this->response->resolveResponse('Platform access verified.', ['authenticated' => true]);
    }
}
