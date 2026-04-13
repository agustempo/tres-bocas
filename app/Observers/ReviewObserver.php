<?php

namespace App\Observers;

use App\Models\Review;

class ReviewObserver
{
    public function saved(Review $review): void
    {
        if ($review->reviewed_user_id) {
            $this->recalculateUser($review);
        }
    }

    private function recalculateUser(Review $review): void
    {
        $user = $review->reviewedUser;

        $user->avg_rating = $user->reviewsReceived()
            ->where('approved', true)
            ->avg('rating') ?? 0;

        $user->reviews_count = $user->reviewsReceived()
            ->where('approved', true)
            ->count();

        $user->saveQuietly();
    }
}
