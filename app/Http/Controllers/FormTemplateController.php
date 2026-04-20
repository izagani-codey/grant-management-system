<?php

namespace App\Http\Controllers;

use App\Models\Document;
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
            'name' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9\s\-_\.]+$/'],
            'description' => ['nullable', 'string'],
            'request_type_id' => ['nullable', 'exists:request_types,id'],
        ];

        $rules['file'] = [
            'required',
            'file',
            'mimes:pdf,jpg,jpeg,png',
            'mimetypes:application/pdf,image/jpeg,image/png',
            'max:5120', // 5MB max
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
                Document::create([
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

    // ==========================================
    // Template Filling Methods
    // ==========================================

    public function fillTemplate($id)
    {
        $template = Document::findOrFail($id);
        
        if (!$template->isTemplate()) {
            abort(404, 'Template not found.');
        }

        if (!$template->isActive()) {
            abort(403, 'Template is not active.');
        }

        // Generate HTML form from template
        $html = $this->generateTemplateForm($template);

        return response()->json([
            'success' => true,
            'template_id' => $template->id,
            'template_name' => $template->name,
            'html' => $html,
        ]);
    }

    public function saveFilledTemplate(Request $request, $id)
    {
        $template = Document::findOrFail($id);
        
        if (!$template->isTemplate()) {
            abort(404, 'Template not found.');
        }

        $request->validate([
            'filled_data' => 'required|array',
            'request_id' => 'nullable|exists:requests,id',
        ]);

        $filledData = $request->filled_data;
        $requestId = $request->request_id;

        // Create a new document with the filled template
        $filledDocument = Document::create([
            'name' => $template->name . ' (Filled)',
            'description' => 'Filled template based on: ' . $template->name,
            'request_type_id' => $template->request_type_id,
            'document_type' => 'user_submission',
            'file_path' => $this->generateFilledPdf($template, $filledData),
            'original_name' => $template->original_name,
            'uploaded_by' => auth()->id(),
            'uploader_role' => auth()->user()->role,
            'is_active' => true,
        ]);

        // If request_id is provided, associate with request
        if ($requestId) {
            $filledDocument->update(['request_id' => $requestId]);
        }

        return response()->json([
            'success' => true,
            'document_id' => $filledDocument->id,
            'message' => 'Template filled and saved successfully.',
        ]);
    }

    private function generateTemplateForm($template): string
    {
        // For now, return a basic form structure
        // In a real implementation, this would parse the PDF/template and generate appropriate form fields
        
        $fields = [
            'name' => ['label' => 'Full Name', 'type' => 'text', 'required' => true, 'prefill' => 'user.name'],
            'staff_id' => ['label' => 'Staff ID', 'type' => 'text', 'required' => true, 'prefill' => 'user.staff_id'],
            'email' => ['label' => 'Email', 'type' => 'email', 'required' => true, 'prefill' => 'user.email'],
            'designation' => ['label' => 'Designation', 'type' => 'text', 'required' => false, 'prefill' => 'user.designation'],
            'department' => ['label' => 'Department', 'type' => 'text', 'required' => false, 'prefill' => 'user.department'],
            'phone' => ['label' => 'Phone', 'type' => 'tel', 'required' => false, 'prefill' => 'user.phone'],
            'purpose' => ['label' => 'Purpose of Request', 'type' => 'textarea', 'required' => true],
        ];

        $html = '<form id="templateForm" class="space-y-4">';
        
        foreach ($fields as $name => $field) {
            $required = $field['required'] ? 'required' : '';
            $prefill = $field['prefill'] ?? '';
            
            $html .= '<div class="form-group">';
            $html .= '<label for="' . $name . '" class="block text-sm font-medium text-gray-700 mb-1">';
            $html .= $field['label'] . ($field['required'] ? ' <span class="text-red-500">*</span>' : '');
            $html .= '</label>';
            
            if ($field['type'] === 'textarea') {
                $html .= '<textarea name="' . $name . '" id="' . $name . '" ' . $required;
                $html .= ' class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"';
                $html .= ' data-prefill="' . $prefill . '" rows="3"></textarea>';
            } else {
                $html .= '<input type="' . $field['type'] . '" name="' . $name . '" id="' . $name . '" ' . $required;
                $html .= ' class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"';
                $html .= ' data-prefill="' . $prefill . '">';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</form>';
        
        return $html;
    }

    private function generateFilledPdf($template, $filledData): string
    {
        // For now, create a simple text file as placeholder
        // In a real implementation, this would use a PDF library to fill the template
        
        $content = "Filled Template: " . $template->name . "\n\n";
        $content .= "Filled on: " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= "Filled by: " . auth()->user()->name . "\n\n";
        $content .= "Data:\n";
        
        foreach ($filledData as $key => $value) {
            $content .= "$key: $value\n";
        }
        
        $filename = 'filled_template_' . $template->id . '_' . time() . '.txt';
        $path = 'filled-templates/' . $filename;
        
        Storage::disk('public')->put($path, $content);
        
        return $path;
    }
}
