<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_are_listed_alphabetically_by_label(): void
    {
        $response = $this->getJson('/api/categories')->assertOk();

        $labels = collect($response->json('data'))->pluck('label');

        $this->assertSame($labels->sort()->values()->all(), $labels->all());
        $this->assertContains('Film', $labels);
    }
}
