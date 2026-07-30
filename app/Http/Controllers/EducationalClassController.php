<?php

namespace App\Http\Controllers;

use App\Models\EducationalClass;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EducationalClassController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'category' => 'nullable|string|max:100', 'level' => 'nullable|in:beginner,intermediate,advanced,all',
            'instructor_id' => 'nullable|integer', 'from_date' => 'nullable|date', 'max_price' => 'nullable|numeric|min:0',
        ]);

        $query = EducationalClass::where('status', 'published')
            ->with(['instructor:id,name', 'venue:id,name,address'])
            ->withCount('activeEnrollments');
        foreach (['category', 'level', 'instructor_id'] as $filter) {
            $query->when($request->filled($filter), fn ($q) => $q->where($filter, $request->input($filter)));
        }
        $query->when($request->filled('from_date'), fn ($q) => $q->whereDate('starts_on', '>=', $request->date('from_date')))
            ->when($request->filled('max_price'), fn ($q) => $q->where('price', '<=', $request->input('max_price')))
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->string('search').'%')
                    ->orWhere('description', 'like', '%'.$request->string('search').'%');
            }));

        return $query->orderBy('starts_on')->paginate(20);
    }

    public function show(EducationalClass $educationalClass)
    {
        abort_unless($educationalClass->status === 'published', 404);

        return $educationalClass->load(['instructor:id,name', 'venue:id,name,address'])
            ->loadCount('activeEnrollments');
    }

    public function manageIndex(Request $request)
    {
        return EducationalClass::where('instructor_id', $request->user()->id)
            ->withCount('activeEnrollments')->latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->validateVenueOwnership($request, $data['venue_id'] ?? null);
        abort_if(empty($data['venue_id']) && empty($data['location']), 422, 'A venue or a location is required.');

        $class = EducationalClass::create([
            ...$data,
            'instructor_id' => $request->user()->id,
            'slug' => $this->uniqueSlug($data['title']),
            'status' => $data['status'] ?? 'draft',
        ]);

        return response()->json($class, 201);
    }

    public function update(Request $request, EducationalClass $educationalClass)
    {
        $this->authorizeOwner($request, $educationalClass);
        $data = $this->validated($request, true);
        $this->validateVenueOwnership($request, $data['venue_id'] ?? $educationalClass->venue_id);
        $venueId = array_key_exists('venue_id', $data) ? $data['venue_id'] : $educationalClass->venue_id;
        $location = array_key_exists('location', $data) ? $data['location'] : $educationalClass->location;
        abort_if(empty($venueId) && empty($location), 422, 'A venue or a location is required.');
        if (isset($data['capacity'])) {
            abort_if($data['capacity'] < $educationalClass->activeEnrollments()->count(), 409, 'Capacity cannot be less than active registrations.');
        }
        if (isset($data['title']) && $data['title'] !== $educationalClass->title) {
            $data['slug'] = $this->uniqueSlug($data['title']);
        }
        $educationalClass->update($data);

        return response()->json($educationalClass->fresh());
    }

    public function destroy(Request $request, EducationalClass $educationalClass)
    {
        $this->authorizeOwner($request, $educationalClass);
        if ($educationalClass->activeEnrollments()->exists()) {
            return response()->json(['message' => 'A class with active enrollments cannot be deleted. Cancel it instead.'], 409);
        }
        $educationalClass->delete();

        return response()->noContent();
    }

    public function analytics(Request $request)
    {
        $classes = EducationalClass::where('instructor_id', $request->user()->id)
            ->withCount(['activeEnrollments'])
            ->withSum('activeEnrollments as registered_value', 'price')
            ->withSum(['activeEnrollments as registered_revenue' => fn ($q) => $q->where('payment_status', 'paid')], 'price')
            ->orderByDesc('active_enrollments_count')->get();

        return response()->json([
            'summary' => [
                'classes_count' => $classes->count(),
                'published_classes_count' => $classes->where('status', 'published')->count(),
                'total_registrations' => $classes->sum('active_enrollments_count'),
                'registered_value' => (float) $classes->sum('registered_value'),
                'paid_revenue' => (float) $classes->sum('registered_revenue'),
            ],
            'best_class' => $classes->first(),
            'classes' => $classes,
        ]);
    }

    public function enrollments(Request $request, EducationalClass $educationalClass)
    {
        $this->authorizeOwner($request, $educationalClass);

        return $educationalClass->enrollments()->with('user:id,name,email')->latest('registered_at')->paginate(30);
    }

    public function adminIndex(Request $request)
    {
        $query = EducationalClass::with(['instructor:id,name,email', 'venue:id,name'])->withCount('activeEnrollments')->latest();
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')));

        return $query->paginate(30);
    }

    public function adminStatus(Request $request, EducationalClass $educationalClass)
    {
        $data = $request->validate(['status' => 'required|in:draft,published,cancelled']);
        $educationalClass->update($data);

        return response()->json($educationalClass);
    }

    private function validated(Request $request, bool $sometimes = false): array
    {
        $prefix = $sometimes ? 'sometimes|' : 'required|';

        return $request->validate([
            'title' => $prefix.'string|max:255',
            'description' => $prefix.'string|max:10000',
            'category' => 'nullable|string|max:100',
            'level' => ($sometimes ? 'sometimes|' : '').'in:beginner,intermediate,advanced,all',
            'capacity' => $prefix.'integer|min:1|max:100000',
            'price' => $prefix.'numeric|min:0',
            'venue_id' => 'nullable|integer|exists:venues,id',
            'location' => 'nullable|string|max:500',
            'schedule' => $prefix.'array|min:1',
            'schedule.*.day' => 'required|string|max:30',
            'schedule.*.start_time' => 'required|date_format:H:i',
            'schedule.*.end_time' => 'required|date_format:H:i|after:schedule.*.start_time',
            'features' => 'nullable|array',
            'features.*' => 'string|max:500',
            'registration_deadline' => 'nullable|date',
            'starts_on' => $prefix.'date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
            'status' => ($sometimes ? 'sometimes|' : 'nullable|').Rule::in(['draft', 'published', 'cancelled']),
        ]);
    }

    private function validateVenueOwnership(Request $request, ?int $venueId): void
    {
        if ($venueId && ! $request->user()->isSuperAdmin()) {
            abort_unless(Venue::whereKey($venueId)->where('owner_id', $request->user()->id)->exists(), 403);
        }
    }

    private function authorizeOwner(Request $request, EducationalClass $class): void
    {
        abort_unless($request->user()->isSuperAdmin() || $class->instructor_id === $request->user()->id, 403);
    }

    private function uniqueSlug(string $title): string
    {
        return Str::slug($title).'-'.strtolower(Str::random(6));
    }
}
