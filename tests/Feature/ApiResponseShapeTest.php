<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class ApiResponseShapeTest extends TestCase
{
    use RefreshDatabase;

    private function assertApiShape(TestResponse $response, int $status, bool $success): TestResponse
    {
        $response->assertStatus($status)->assertJsonPath('success', $success);

        $this->assertSame(
            ['success', 'message', 'data', 'errors'],
            array_keys($response->json()),
            'Unexpected response keys: '.$response->getContent()
        );
        $this->assertIsString($response->json('message'));

        return $response;
    }

    public function test_auth_success_responses(): void
    {
        $this->assertApiShape($this->postJson('/api/register', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]), 201, true)->assertJsonPath('errors', null);

        $this->assertApiShape($this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]), 200, true);
    }

    public function test_logout_success_response(): void
    {
        $token = User::factory()->create()->createToken('api-token')->plainTextToken;

        $this->assertApiShape($this->withToken($token)->postJson('/api/logout'), 200, true);
    }

    public function test_course_success_responses(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $course = Course::factory()->for($user)->create();

        $this->assertApiShape($this->getJson('/api/courses'), 200, true);
        $this->assertApiShape($this->getJson("/api/courses/{$course->id}"), 200, true);
        $this->assertApiShape($this->postJson('/api/courses', ['title' => 'A', 'price' => 1]), 201, true);
        $this->assertApiShape($this->putJson("/api/courses/{$course->id}", ['title' => 'B']), 200, true);
        $this->assertApiShape($this->deleteJson("/api/courses/{$course->id}"), 200, true);
    }

    public function test_invalid_credentials_response(): void
    {
        $this->assertApiShape($this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong',
        ]), 401, false)->assertJsonPath('errors', null);
    }

    public function test_validation_responses_on_every_form_request(): void
    {
        $this->assertApiShape($this->postJson('/api/register', []), 422, false)
            ->assertJsonPath('message', 'Validation Error')
            ->assertJsonValidationErrors(['name', 'email', 'password']);

        $this->assertApiShape($this->postJson('/api/login', []), 422, false)
            ->assertJsonPath('message', 'Validation Error')
            ->assertJsonValidationErrors(['email', 'password']);

        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $course = Course::factory()->for($user)->create();

        $this->assertApiShape($this->postJson('/api/courses', []), 422, false)
            ->assertJsonPath('message', 'Validation Error')
            ->assertJsonValidationErrors(['title', 'price']);

        $this->assertApiShape($this->putJson("/api/courses/{$course->id}", ['price' => 'abc']), 422, false)
            ->assertJsonPath('message', 'Validation Error')
            ->assertJsonValidationErrors('price');
    }

    public function test_unauthenticated_response(): void
    {
        $this->assertApiShape($this->getJson('/api/courses'), 401, false)
            ->assertJsonPath('message', 'Unauthenticated')
            ->assertJsonPath('errors', null);
    }

    public function test_course_not_found_and_forbidden_responses(): void
    {
        $course = Course::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->assertApiShape($this->getJson('/api/courses/999'), 404, false)
            ->assertJsonPath('errors', null);
        $this->assertApiShape($this->deleteJson("/api/courses/{$course->id}"), 403, false)
            ->assertJsonPath('errors', null);
    }

    public function test_unknown_route_response(): void
    {
        $this->assertApiShape($this->getJson('/api/does-not-exist'), 404, false);
    }

    public function test_method_not_allowed_response(): void
    {
        $this->assertApiShape($this->patchJson('/api/login'), 405, false);
    }

    public function test_too_many_requests_response(): void
    {
        Route::middleware('throttle:1,1')->get('/api/test-throttled', fn () => 'ok');

        $this->getJson('/api/test-throttled')->assertOk();

        $this->assertApiShape($this->getJson('/api/test-throttled'), 429, false)
            ->assertHeader('Retry-After');
    }

    public function test_server_error_hides_details_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/test-boom', fn () => throw new RuntimeException('secret details'));

        $this->assertApiShape($this->getJson('/api/test-boom'), 500, false)
            ->assertJsonPath('message', 'Server Error');
    }
}
