<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\PdfInfoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FormTemplateController extends BaseController
{
    public function index()
    {
        $templates = Document::query()
            ->where('document_type', 'template')
            ->with('uploader')
            ->with('requestType')
            ->latest('created_at')
            ->get();

        return view('form-templates.index', compact('templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z0-9\s\-_\.]+$/'],
            'description' => ['nullable', 'string'],
            'request_type_id' => ['nullable', 'exists:request_types,id'],
        ];

        $rules['file'] = [
            'required',
            'file',
            'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
            'mimetypes:application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'max:5120',
        ];

        $request->validate($rules);

        $file = $request->file('file');
        
        try {
            // Handle file upload
            if ($file && $file->isValid()) {
                // Check file size manually for additional security
                if ($file->getSize() > 5 * 1024 * 1024) { // 5MB
                    return back()->withErrors(['file' => 'File size exceeds maximum allowed size of 5MB.']);
                }
                
                // Scan for malicious content (basic check)
                $content = file_get_contents($file->getPathname());
                if (strpos($content, '<?php') !== false || strpos($content, '<script') !== false) {
                    return back()->withErrors(['file' => 'File contains potentially malicious content.']);
                }
                
                // Generate safe filename
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $safeFilename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', pathinfo($originalName, PATHINFO_FILENAME));
                $filename = $safeFilename . '_' . time() . '.' . $extension;
                
                $path = $file->storeAs('blank-forms', $filename, 'public');
                
                if (!$path) {
                    return back()->withErrors(['file' => 'Failed to store file. Please check storage permissions.']);
                }
                
                // Create new template document
                $document = Document::create([
                    'name' => $request->input('name'),
                    'description' => $request->input('description'),
                    'request_type_id' => $request->input('request_type_id'),
                    'document_type' => 'template',
                    'is_template' => true,
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'uploaded_by' => $request->user()->id,
                    'uploader_role' => $request->user()->role,
                    'is_active' => true,
                    'download_count' => 0,
                ]);

                if (str_ends_with(strtolower($originalName), '.pdf')) {
                    try {
                        $count = app(PdfInfoService::class)->getPageCount($document->file_path);
                        $document->update(['pdf_page_count' => $count]);
                    } catch (\Throwable $e) {
                        \Log::warning('PDF page count failed: ' . $e->getMessage());
                    }
                }
            }

            return back()->with('success', 'Template uploaded successfully.');
            
        } catch (\Exception $e) {
            \Log::error('Template operation failed: ' . $e->getMessage());
            return back()->withErrors(['file' => 'Operation failed: ' . $e->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $template = Document::query()->findOrFail($id);

        Storage::disk('public')->delete($template->file_path);
        $template->delete();

        return back()->with('success', 'Blank form removed.');
    }
}
