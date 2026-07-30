<?php

namespace App\Http\Controllers;

use App\Domain\Payments\Services\EducationalClassPaymentService;
use App\Models\EducationalClass;
use App\Models\EducationalClassEnrollment;
use Illuminate\Http\Request;

class EducationalClassEnrollmentController extends Controller
{
    public function store(Request $request, EducationalClass $educationalClass, EducationalClassPaymentService $payments)
    {
        try {
            $payment = $payments->initiate($request->user()->id, $educationalClass);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json([
            'invoice' => $payment['invoice']->number,
            'amount' => $payment['invoice']->amount,
            'payment_url' => $payment['payment_url'],
            'class' => ['id' => $educationalClass->id, 'slug' => $educationalClass->slug, 'title' => $educationalClass->title],
        ], 201);
    }

    public function index(Request $request)
    {
        return EducationalClassEnrollment::where('user_id', $request->user()->id)
            ->with('educationalClass.instructor:id,name')->latest('registered_at')->paginate(20);
    }

    public function destroy(Request $request, EducationalClass $educationalClass)
    {
        $enrollment = EducationalClassEnrollment::where('educational_class_id', $educationalClass->id)
            ->where('user_id', $request->user()->id)->where('status', 'registered')->firstOrFail();
        abort_if($enrollment->payment_status === 'paid', 409, 'Paid registrations must be cancelled by support.');

        $enrollment->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return response()->json($enrollment);
    }
}
