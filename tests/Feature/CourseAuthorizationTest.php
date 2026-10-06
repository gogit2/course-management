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

    private User $owner;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->course = Course::factory()->for($this->owner)->create([
            'title' => 'Original title',
            'price' => 10,
        ]);
    }

    public function test_owner_can_update_course(): void
    {
        Sanctum::actingAs($this->owner);

        $this->putJson("/api/courses/{$this->course->id}", ['title' => 'New title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'New title');
    }

    public function test_non_owner_cannot_update_course(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/courses/{$this->course->id}", [
            'title' => 'Hacked',
            'price' => 0,
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You are not allowed to update this course');

        $this->course->refresh();
        $this->assertSame('Original title', $this->course->title);
        $this->assertEquals(10, $this->course->price);
    }

    public function test_owner_can_delete_course(): void
    {
        Sanctum::actingAs($this->owner);

        $this->deleteJson("/api/courses/{$this->course->id}")->assertOk();

        $this->assertModelMissing($this->course);
    }

    public function test_non_owner_cannot_delete_course(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/courses/{$this->course->id}")
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You are not allowed to delete this course');

        $this->assertModelExists($this->course);
    }

    public function test_guest_cannot_update_or_delete_course(): void
    {
        $this->putJson("/api/courses/{$this->course->id}", ['title' => 'Hacked'])
            ->assertUnauthorized();

        $this->deleteJson("/api/courses/{$this->course->id}")
            ->assertUnauthorized();

        $this->assertSame('Original title', $this->course->fresh()->title);
    }
}
