<?php

namespace App\Http\Controllers;

use App\Models\FormTemplate;
use App\Models\RequestTypeTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FormTemplateController extends BaseController
{
    public function index()
    {
        $templates = FormTemplate::query()
            ->with('uploader')
            ->with('requestTypes')
            ->latest('created_at')
            ->get();

        return view('form-templates.index', compact('templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'title' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9\s\-_\.]+$/'],
            'request_type_id' => ['nullable', 'exists:request_types,id'],
            'is_default' => ['nullable', 'boolean'],
            'template_type' => ['nullable', 'string', 'in:request_type_form,supporting_document,general_form'],
        ];

        // File is required unless setting as default template for existing request type
        $isSettingDefault = $request->boolean('is_default', false) && $request->input('request_type_id');
        
        if (!$isSettingDefault) {
            $rules['file'] = [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120', // 5MB max
            ];
        } else {
            $rules['file'] = [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120', // 5MB max
            ];
        }

        $request->validate($rules);

        $file = $request->file('file');
        $isSettingDefault = $request->boolean('is_default', false) && $request->input('request_type_id');
        
        try {
            $template = null;
            $path = null;
            
            // Handle file upload if provided
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
            }
            
            // If setting default template without file, use existing default template or create placeholder
            if ($isSettingDefault && !$path) {
                $requestType = \App\Models\RequestType::find($request->input('request_type_id'));
                if ($requestType && $requestType->default_template_id) {
                    // Use existing default template
                    $template = $requestType->defaultTemplate;
                    $path = $template->file_path;
                } else {
                    return back()->withErrors(['file' => 'No existing default template found. Please upload a file.']);
                }
            }
            
            // Create new template if we have a file or if it's not a default template setting
            if ($path && !$template) {
                $allowedTypes = ['request_type_form', 'supporting_document', 'general_form'];
                $templateType = in_array($request->input('template_type'), $allowedTypes, true)
                    ? $request->input('template_type')
                    : ($request->input('request_type_id') ? 'request_type_form' : 'general_form');

                $template = FormTemplate::create([
                    'title' => $request->input('title'),
                    'template_type' => $templateType,
                    'file_path' => $path,
                    'uploaded_by' => $request->user()->id,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // If request type is specified, create/update the relationship
            if ($request->input('request_type_id') && $template) {
                $isDefault = $request->boolean('is_default', false);
                
                // Check if association already exists
                $existingAssociation = RequestTypeTemplate::where('request_type_id', $request->input('request_type_id'))
                    ->where('form_template_id', $template->id)
                    ->first();
                
                if (!$existingAssociation) {
                    RequestTypeTemplate::create([
                        'request_type_id' => $request->input('request_type_id'),
                        'form_template_id' => $template->id,
                        'is_default' => $isDefault,
                        'sort_order' => RequestTypeTemplate::where('request_type_id', $request->input('request_type_id'))->max('sort_order') + 1,
                    ]);
                } else {
                    // Update existing association
                    $existingAssociation->update(['is_default' => $isDefault]);
                }

                if ($isDefault) {
                    // Ensure only one default template per request type
                    RequestTypeTemplate::where('request_type_id', $request->input('request_type_id'))
                        ->where('form_template_id', '!=', $template->id)
                        ->update(['is_default' => false]);
                }
            }

            $message = $isSettingDefault ? 'Default template updated successfully.' : 'Template uploaded successfully.';
            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            \Log::error('Template operation failed: ' . $e->getMessage());
            return back()->withErrors(['file' => 'Operation failed: ' . $e->getMessage()]);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $template = FormTemplate::query()->findOrFail($id);

        Storage::disk('public')->delete($template->file_path);
        $template->delete();

        return back()->with('success', 'Blank form removed.');
    }
}
