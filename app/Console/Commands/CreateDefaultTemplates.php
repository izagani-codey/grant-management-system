<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\RequestType;
use App\Models\RequestTypeTemplate;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:create-default-templates')]
#[Description('Create default templates for each request type')]
class CreateDefaultTemplates extends Command
{
    public function handle()
    {
        $this->info('Creating default templates for each request type...');

        $requestTypes = RequestType::all();
        $adminUser = User::where('role', 'admin')->first() ?: User::where('role', 'staff2')->first();

        if (!$adminUser) {
            $this->error('No admin or staff2 user found to assign as template uploader.');
            return 1;
        }

        foreach ($requestTypes as $requestType) {
            $this->info("Creating template for: {$requestType->name}");

            // Create a basic template file (placeholder)
            $templateContent = $this->generateTemplateContent($requestType);
            $fileName = strtolower(str_replace(' ', '_', $requestType->name)) . '_template.pdf';
            $filePath = "templates/{$fileName}";

            // Store the template file
            Storage::disk('public')->put($filePath, $templateContent);

            // Create Document record
            $formTemplate = Document::create([
                'name' => $requestType->name . ' Template',
                'document_type' => 'template',
                'request_type_id' => $requestType->id,
                'file_path' => $filePath,
                'original_name' => $requestType->name . ' Template.pdf',
                'is_template' => true,
                'is_active' => true,
                'uploaded_by' => $adminUser->id,
                'uploader_role' => 'admin',
            ]);

            // Create RequestTypeTemplate record
            RequestTypeTemplate::create([
                'request_type_id' => $requestType->id,
                'form_template_id' => $formTemplate->id,
                'is_default' => true,
                'sort_order' => 1,
            ]);

            $this->info("  - Created template: {$formTemplate->title}");
        }

        $this->info('Default templates created successfully!');
        return 0;
    }

    private function generateTemplateContent($requestType): string
    {
        // Escape special characters for PDF literal strings
        $escapedName = $this->escapePdfString($requestType->name);
        $textContent = "BT\n/F1 12 Tf\n72 720 Td\n({$escapedName} Template) Tj\nET\n";
        $contentLength = strlen($textContent);
        
        // Generate a basic PDF content (placeholder)
        // In a real implementation, you would generate actual PDF content
        return "%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
/Resources <<
/Font <<
/F1 5 0 R
>>
>>
>>
endobj

4 0 obj
<<
/Length {$contentLength}
>>
stream
{$textContent}endstream
endobj

5 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
endobj

xref
0 6
0000000000 65535 f 
0000000009 00000 f 
0000000058 00000 f 
0000000115 00000 f 
0000000261 00000 f 
0000000349 00000 f 
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
456
%%EOF";
    }
    
    private function escapePdfString($string): string
    {
        // Escape special characters for PDF literal strings
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $string);
    }
}
