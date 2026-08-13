<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicApiCacheTest extends TestCase
{
    public function test_country_options_are_cached_between_requests(): void
    {
        Cache::flush();
        Http::fake([
            'countriesnow.space/api/v0.1/countries/iso' => Http::response([
                'data' => [
                    ['name' => 'Philippines', 'Iso2' => 'PH'],
                ],
            ]),
        ]);

        $this->getJson(route('public-apis.countries'))->assertOk();
        $this->getJson(route('public-apis.countries'))->assertOk();

        Http::assertSentCount(1);
    }
}
