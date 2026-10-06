<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_user_can_list_courses_paginated(): void
    {
        Course::factory()->count(12)->create();

        $this->getJson('/api/courses')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(10, 'data.data')
            ->assertJsonPath('data.total', 12);
    }

    public function test_courses_are_listed_newest_first(): void
    {
        $old = Course::factory()->create(['created_at' => now()->subDay()]);
        $new = Course::factory()->create(['created_at' => now()]);

        $this->getJson('/api/courses')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $new->id)
            ->assertJsonPath('data.data.1.id', $old->id);
    }

    public function test_user_can_create_course(): void
    {
        $this->postJson('/api/courses', [
            'title' => 'Laravel Basics',
            'description' => 'Intro course',
            'price' => 49.99,
            'is_published' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Laravel Basics')
            ->assertJsonPath('data.user_id', $this->user->id);

        $this->assertDatabaseHas('courses', [
            'title' => 'Laravel Basics',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_create_ignores_user_id_from_request(): void
    {
        $other = User::factory()->create();

        $this->postJson('/api/courses', [
            'title' => 'Laravel Basics',
            'price' => 10,
            'user_id' => $other->id,
        ])->assertCreated();

        $this->assertDatabaseHas('courses', ['user_id' => $this->user->id]);
        $this->assertDatabaseMissing('courses', ['user_id' => $other->id]);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->postJson('/api/courses', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'price']);
    }

    public function test_create_rejects_negative_price(): void
    {
        $this->postJson('/api/courses', ['title' => 'Course', 'price' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price');
    }

    public function test_user_can_view_a_course(): void
    {
        $course = Course::factory()->create();

        $this->getJson("/api/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $course->id);
    }

    public function test_viewing_missing_course_returns_404(): void
    {
        $this->getJson('/api/courses/999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Course not found with ID 999');
    }

    public function test_updating_missing_course_returns_404(): void
    {
        $this->putJson('/api/courses/999', ['title' => 'New'])->assertNotFound();
    }

    public function test_deleting_missing_course_returns_404(): void
    {
        $this->deleteJson('/api/courses/999')->assertNotFound();
    }

    public function test_update_validates_fields(): void
    {
        $course = Course::factory()->for($this->user)->create();

        $this->putJson("/api/courses/{$course->id}", ['price' => 'abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('price');
    }
}
