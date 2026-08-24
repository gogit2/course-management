<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;

class CourseController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $courses = Course::query()
            ->latest()
            ->paginate(10);

        return $this->successResponse($courses, 'Courses retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourseRequest $request)
    {
        $course = $request->user()->courses()->create($request->validated());

        return $this->successResponse($course, 'Course created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $course = $this->findCourse($id);

        return $this->successResponse($course, 'Course retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCourseRequest $request, $id)
    {
        $course = $this->findCourse($id);

        $course->update($request->validated());

        return $this->successResponse($course, 'Course updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $course = $this->findCourse($id);

        $course->delete();

        return $this->successResponse(null, 'Course deleted successfully');
    }

    private function findCourse($id): Course
    {
        $course = Course::find($id);

        if (! $course) {
            abort(
                $this->notFoundResponse('Course', $id)
            );
        }

        return $course;
    }
}
