<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite requires FK checks off while restructuring
        DB::statement('PRAGMA foreign_keys = OFF');

        // 1. Add new columns
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('reviewer_id')->nullable()->after('id');
            $table->unsignedBigInteger('reviewed_user_id')->nullable()->after('reviewer_id');
        });

        // 2. Migrate existing data: map old user_id → reviewer_id, listing owner → reviewed_user_id
        DB::table('reviews')->orderBy('id')->each(function ($review) {
            $reviewedUserId = null;

            if ($review->listing_id) {
                $listing = DB::table('listings')->where('id', $review->listing_id)->first();
                $reviewedUserId = $listing?->user_id;
            }

            DB::table('reviews')->where('id', $review->id)->update([
                'reviewer_id'       => $review->user_id,
                'reviewed_user_id'  => $reviewedUserId,
            ]);
        });

        // 3. Make listing_id nullable (Laravel recreates the table for SQLite)
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('listing_id')->nullable()->change();
        });

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['reviewer_id', 'reviewed_user_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('listing_id')->nullable(false)->change();
        });

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
