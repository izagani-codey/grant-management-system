<?php

namespace App\Http\Controllers;

use App\Models\ChecklistReview;
use App\Enums\RequestStatus;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    /**
     * Store or update a checklist review for a request.
     */
    public function store(Request $request, \App\Models\Request $requestModel)
    {
        $request->validate([
            'checklist_item_id' => 'required|exists:checklist_items,id',
            'status' => 'required|in:checked,flagged',
            'note' => 'nullable|string|max:500',
        ]);

        // Ensure user is Staff1 and can review this request
        $this->authorize('review', $requestModel);

        $checklistReview = ChecklistReview::updateOrCreate(
            [
                'request_id' => $requestModel->id,
                'checklist_item_id' => $request->checklist_item_id,
            ],
            [
                'reviewed_by' => auth()->id(),
                'status' => $request->status,
                'note' => $request->note,
            ]
        );

        return response()->json([
            'success' => true,
            'review' => $checklistReview,
            'can_forward' => $requestModel->canBeForwardedToStaff2(),
            'progress' => $requestModel->getChecklistProgress(),
        ]);
    }

    /**
     * Get checklist items and reviews for a request.
     */
    public function show(\App\Models\Request $requestModel)
    {
        $this->authorize('review', $requestModel);

        $checklistItems = $requestModel->getChecklistItems();
        $reviews = $requestModel->getChecklistReviews()->keyBy('checklist_item_id');

        $checklistData = $checklistItems->map(function ($item) use ($reviews) {
            $review = $reviews->get($item->id);
            
            return [
                'id' => $item->id,
                'label' => $item->label,
                'is_required' => $item->is_required,
                'sort_order' => $item->sort_order,
                'review' => $review ? [
                    'status' => $review->status,
                    'note' => $review->note,
                    'reviewed_at' => $review->updated_at,
                    'reviewer' => $review->reviewer ? $review->reviewer->name : null,
                ] : null,
            ];
        })->sortBy('sort_order')->values();

        return response()->json([
            'checklist' => $checklistData,
            'progress' => $requestModel->getChecklistProgress(),
            'can_forward' => $requestModel->canBeForwardedToStaff2(),
        ]);
    }

    /**
     * Bulk update checklist reviews for a request.
     */
    public function bulkUpdate(Request $request, \App\Models\Request $requestModel)
    {
        $request->validate([
            'reviews' => 'required|array',
            'reviews.*.checklist_item_id' => 'required|exists:checklist_items,id',
            'reviews.*.status' => 'required|in:checked,flagged',
            'reviews.*.note' => 'nullable|string|max:500',
        ]);

        $this->authorize('review', $requestModel);

        foreach ($request->reviews as $reviewData) {
            ChecklistReview::updateOrCreate(
                [
                    'request_id' => $requestModel->id,
                    'checklist_item_id' => $reviewData['checklist_item_id'],
                ],
                [
                    'reviewed_by' => auth()->id(),
                    'status' => $reviewData['status'],
                    'note' => $reviewData['note'] ?? null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'progress' => $requestModel->getChecklistProgress(),
            'can_forward' => $requestModel->canBeForwardedToStaff2(),
        ]);
    }
}
