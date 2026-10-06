<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createCourseFor(User $user): Course
    {
        return $user->courses()->create([
            'title' => 'Original title',
            'price' => 10,
        ]);
    }

    public function test_owner_can_update_course(): void
    {
        $owner = User::factory()->create();
        $course = $this->createCourseFor($owner);

        Sanctum::actingAs($owner);

        $this->putJson("/api/courses/{$course->id}", ['title' => 'New title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'New title');
    }

    public function test_non_owner_cannot_update_course(): void
    {
        $course = $this->createCourseFor(User::factory()->create());

        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/courses/{$course->id}", ['title' => 'Hacked'])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertSame('Original title', $course->fresh()->title);
    }

    public function test_owner_can_delete_course(): void
    {
        $owner = User::factory()->create();
        $course = $this->createCourseFor($owner);

        Sanctum::actingAs($owner);

        $this->deleteJson("/api/courses/{$course->id}")->assertOk();

        $this->assertModelMissing($course);
    }

    public function test_non_owner_cannot_delete_course(): void
    {
        $course = $this->createCourseFor(User::factory()->create());

        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/courses/{$course->id}")
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertModelExists($course);
    }
}
