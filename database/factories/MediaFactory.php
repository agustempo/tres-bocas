<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'path' => 'images/sample-' . fake()->numberBetween(1, 10) . '.jpg',
            'type' => 'image',
        ];
    }
}
