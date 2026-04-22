<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Request as GrantRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends BaseController
{
    /** Staff2 uploads a staff attachment to a specific request. */
    public function store(Request $request, $requestId)
    {
        $grantRequest = GrantRequest::findOrFail($requestId);

        if (Auth::user()->role !== 'staff2') {
            abort(403, 'Only Staff 2 can upload documents to requests.');
        }

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'mimetypes:application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'max:10240'],
        ]);

        $file     = $request->file('document');
        $path     = $file->store("documents/request-{$requestId}", 'public');

        Document::create([
            'request_id'      => $grantRequest->id,
            'request_type_id' => $grantRequest->request_type_id,
            'uploaded_by'     => Auth::id(),
            'uploader_role'   => Auth::user()->role,
            'file_path'       => $path,
            'original_name'   => $file->getClientOriginalName(),
            'document_type'   => 'staff_attachment',
        ]);

        return redirect()->route('requests.show', $grantRequest->id)
            ->with('success', 'Document uploaded successfully.');
    }

    /** Upload user submission documents during request creation/editing */
    public function storeUserSubmission(Request $request, $requestId = null)
    {
        $user = Auth::user();
        
        if (!$user->isAdmission()) {
            abort(403, 'Only admission users can submit documents.');
        }

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'mimetypes:application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'max:10240'],
            'request_id' => 'nullable|exists:requests,id',
        ]);

        $file = $request->file('document');
        
        // Determine storage path
        if ($requestId) {
            $path = $file->store("documents/request-{$requestId}", 'public');
        } else {
            $path = $file->store("documents/temporary/{$user->id}", 'public');
        }

        $document = Document::create([
            'request_id'      => $requestId,
            'uploaded_by'     => $user->id,
            'uploader_role'   => $user->role,
            'file_path'       => $path,
            'original_name'   => $file->getClientOriginalName(),
            'document_type'   => 'user_submission',
        ]);

        return response()->json([
            'success' => true,
            'document_id' => $document->id,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ]);
    }

    /** Staff2 deletes a document they uploaded. */
    public function destroy($id)
    {
        $document = Document::findOrFail($id);

        if ($document->uploaded_by !== Auth::id() && !in_array(Auth::user()->role, ['staff2', 'admin'])) {
            abort(403);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->back()->with('success', 'Document removed.');
    }

    /** Secure download — any authorized viewer of the request can download. */
    public function download($id)
    {
        $document     = Document::with('request')->findOrFail($id);
        $grantRequest = $document->request;

        if ($grantRequest) {
            $this->authorize('view', $grantRequest);
        } elseif (!$document->isTemplate() || !$document->is_active) {
            abort(403);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        // Increment download count
        $document->incrementDownloadCount();

        return Storage::disk('public')->download($document->file_path, $document->original_name);
    }

    /** Get documents by category for a request */
    public function getByCategory($requestId, $category)
    {
        $grantRequest = GrantRequest::findOrFail($requestId);
        $this->authorize('view', $grantRequest);

        if (!in_array($category, ['template', 'user_submission', 'staff_attachment'])) {
            abort(400, 'Invalid document category.');
        }

        $documents = $grantRequest->documents()
            ->where('document_type', $category)
            ->with('uploader')
            ->get();

        return response()->json([
            'documents' => $documents->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'original_name' => $doc->original_name,
                    'file_size' => $doc->getFormattedFileSize(),
                    'file_extension' => $doc->getFileExtension(),
                    'is_pdf' => $doc->isPdf(),
                    'is_image' => $doc->isImage(),
                    'download_url' => $doc->getDownloadUrl(),
                    'uploaded_by' => $doc->uploader->name,
                    'uploaded_at' => $doc->created_at->format('M j, Y H:i'),
                    'download_count' => $doc->download_count,
                ];
            }),
        ]);
    }

    /** Move temporary documents to request after request creation */
    public function moveToRequest(Request $request, $requestId)
    {
        $grantRequest = GrantRequest::findOrFail($requestId);
        $user = Auth::user();

        if (!$user->isAdmission() || $grantRequest->user_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:documents,id',
        ]);

        $documents = Document::whereIn('id', $request->document_ids)
            ->where('uploaded_by', $user->id)
            ->whereNull('request_id')
            ->get();

        foreach ($documents as $document) {
            // Move file from temporary to request folder
            $oldPath = $document->file_path;
            $newPath = "documents/request-{$requestId}/" . basename($oldPath);
            
            try {
                $moveSuccess = Storage::disk('public')->move($oldPath, $newPath);
                
                if (!$moveSuccess) {
                    \Log::error("Failed to move document file", [
                        'old_path' => $oldPath,
                        'new_path' => $newPath,
                        'request_id' => $requestId,
                        'request_type_id' => $grantRequest->request_type_id,
                        'document_id' => $document->id
                    ]);
                    continue;
                }
                
                $document->update([
                    'request_id' => $requestId,
                    'request_type_id' => $grantRequest->request_type_id,
                    'file_path' => $newPath,
                ]);
                
            } catch (\Exception $e) {
                \Log::error("Exception while moving document file", [
                    'old_path' => $oldPath,
                    'new_path' => $newPath,
                    'request_id' => $requestId,
                    'request_type_id' => $grantRequest->request_type_id,
                    'document_id' => $document->id,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return response()->json([
            'success' => true,
            'moved_count' => $documents->count(),
        ]);
    }
}
