<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Media;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        // Regular users
        $users = User::factory(5)->create();

        // Categories
        $categories = collect([
            ['name' => 'Food', 'slug' => 'food'],
            ['name' => 'Accommodation', 'slug' => 'accommodation'],
            ['name' => 'Activities', 'slug' => 'activities'],
            ['name' => 'Transport', 'slug' => 'transport'],
            ['name' => 'Services', 'slug' => 'services'],
        ])->map(fn ($data) => Category::create($data));

        // Listings
        $listingsData = [
            [
                'title' => 'La Casita del Mar',
                'description' => 'Cozy beachside guesthouse with stunning ocean views and home-cooked breakfast.',
                'contact' => 'casita@example.com',
                'category' => 'Accommodation',
            ],
            [
                'title' => 'El Rincón Típico',
                'description' => 'Traditional local food with fresh ingredients sourced daily from nearby farms.',
                'contact' => 'rincon@example.com',
                'category' => 'Food',
            ],
            [
                'title' => 'Surf & Adventures',
                'description' => 'Surfing lessons and guided outdoor adventures for all skill levels.',
                'contact' => 'surf@example.com',
                'category' => 'Activities',
            ],
            [
                'title' => 'Taxi Seguro',
                'description' => 'Reliable local taxi service available 24/7 for airport transfers and tours.',
                'contact' => 'taxi@example.com',
                'category' => 'Transport',
            ],
            [
                'title' => 'Lavandería Express',
                'description' => 'Quick and affordable laundry service with same-day pickup and delivery.',
                'contact' => 'lavanderia@example.com',
                'category' => 'Services',
            ],
            [
                'title' => 'Café Mirador',
                'description' => 'Artisan coffee and light meals with panoramic mountain views.',
                'contact' => 'cafe@example.com',
                'category' => 'Food',
            ],
            [
                'title' => 'Hostal Las Palmas',
                'description' => 'Budget-friendly hostal in the heart of town, perfect for backpackers.',
                'contact' => 'palmas@example.com',
                'category' => 'Accommodation',
            ],
        ];

        $allUsers = $users->prepend($admin);
        $categoryMap = $categories->keyBy('name');

        $listings = collect($listingsData)->map(function ($data, $index) use ($allUsers, $categoryMap) {
            return Listing::create([
                'user_id' => $allUsers[$index % $allUsers->count()]->id,
                'category_id' => $categoryMap[$data['category']]->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'contact' => $data['contact'],
                'status' => 'published',
            ]);
        });

        // Reviews (approved ones trigger the observer to recalculate avg_rating/reviews_count)
        foreach ($listings as $listing) {
            for ($i = 0; $i < rand(2, 3); $i++) {
                Review::create([
                    'listing_id' => $listing->id,
                    'user_id' => $users->random()->id,
                    'rating' => rand(3, 5),
                    'comment' => $this->faker->sentence(),
                    'approved' => true,
                ]);
            }

            // One pending review per listing
            Review::create([
                'listing_id' => $listing->id,
                'user_id' => $users->random()->id,
                'rating' => rand(1, 5),
                'comment' => $this->faker->sentence(),
                'approved' => false,
            ]);
        }

        // Media (2 per listing)
        foreach ($listings as $i => $listing) {
            for ($j = 1; $j <= 2; $j++) {
                Media::create([
                    'listing_id' => $listing->id,
                    'path' => 'images/listing-' . ($i + 1) . '-' . $j . '.jpg',
                    'type' => 'image',
                ]);
            }
        }
    }
}
