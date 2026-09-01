<?php

namespace App\Http\Controllers\Performance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePerformanceReviewRequest;
use App\Models\PerformanceReview;
use App\Services\AuditLog\AuditLogServiceInterface;
use Illuminate\Http\Request;

class PerformanceReviewController extends Controller
{
    private AuditLogServiceInterface $auditLogs;

    public function __construct(AuditLogServiceInterface $auditLogs)
    {
        $this->auditLogs = $auditLogs;
        $this->middleware('permission:manage-employees');
    }

    public function index()
    {
        return response()->json(['data' => PerformanceReview::query()->latest()->get()]);
    }

    public function store(StorePerformanceReviewRequest $request)
    {
        $review = PerformanceReview::query()->create([...$request->validated(), 'reviewer_id' => $request->user()->getKey()]);
        $this->auditLogs->insertLog($review, 'create performance review');

        return response()->json(['message' => 'Performance review created successfully.', 'data' => $review], 201);
    }

    public function finalize(Request $request, PerformanceReview $review)
    {
        abort_if($review->status === 'finalized', 409, 'This performance review is already finalized.');
        $review->update(['status' => 'finalized']);
        $this->auditLogs->insertLog($review, 'finalize performance review', ['reviewer_id' => $request->user()->getKey()]);

        return response()->json(['message' => 'Performance review finalized successfully.', 'data' => $review->fresh()], 202);
    }
}
