<?php

namespace Tests\Unit;

use App\Services\Utils\ResponseService;
use Tests\TestCase;

class ResponseServiceTest extends TestCase
{
    public function test_reject_response_uses_custom_status_code(): void
    {
        $service = new ResponseService();

        $response = $service->rejectResponse('Validation failed', ['field' => 'email'], 422);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Validation failed', $response->getData(true)['message']);
    }
}
