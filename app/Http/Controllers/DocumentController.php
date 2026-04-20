<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Request as GrantRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends BaseController
{
    /** Staff2 uploads a document to a specific request. */
    public function store(Request $request, $requestId)
    {
        $grantRequest = GrantRequest::findOrFail($requestId);

        if (!in_array(Auth::user()->role, ['staff2', 'admin'])) {
            abort(403, 'Only Staff 2 can upload documents to requests.');
        }

        $request->validate([
            'document'    => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'is_template' => 'nullable|boolean',
        ]);

        $file     = $request->file('document');
        $path     = $file->store("documents/request-{$requestId}", 'public');

        Document::create([
            'request_id'    => $grantRequest->id,
            'uploaded_by'   => Auth::id(),
            'uploader_role' => Auth::user()->role,
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'is_template'   => $request->boolean('is_template', false),
        ]);

        return redirect()->route('requests.show', $grantRequest->id)
            ->with('success', 'Document uploaded successfully.');
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

        $this->authorize('view', $grantRequest);

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download($document->file_path, $document->original_name);
    }
}
