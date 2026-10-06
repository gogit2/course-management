<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Only the course owner can update it.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->id === $course->user_id;
    }

    /**
     * Only the course owner can delete it.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->id === $course->user_id;
    }
}
