<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;
use App\Models\RequestType;
use App\Models\User;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Get the Equipment Purchase request type (first available)
        $requestType = RequestType::where('slug', 'equipment-purchase')->first();
        
        // Get any Staff 2 user
        $staff2User = User::where('role', 'staff2')->first();
        
        if ($requestType && $staff2User) {
            // Check if the file exists before creating template
            $filePath = 'blank-forms/imfTpCXuJWfsGCbPfDSNu14icefUpL4EaKrZc9yv.pdf';
            
            if (!\Storage::disk('public')->exists($filePath)) {
                $this->command->warn("Template file not found: {$filePath}");
                $this->command->warn('Skipping template creation. Please upload the template file first.');
                return;
            }
            
            // Create the template that was previously uploaded
            $template = Document::create([
                'name' => 'testing 2',
                'document_type' => 'template',
                'request_type_id' => $requestType->id,
                'file_path' => $filePath,
                'original_name' => 'testing 2.pdf',
                'uploaded_by' => $staff2User->id,
                'uploader_role' => 'staff2',
                'is_template' => true,
                'is_active' => true,
            ]);
            
            // Assign it to request type
            $requestType->default_template_id = $template->id;
            $requestType->save();
            
            $this->command->info('Template created and assigned to Equipment Purchase');
        } else {
            if (!$requestType) {
                $this->command->warn('⚠️ Equipment Purchase request type not found');
            }
            if (!$staff2User) {
                $this->command->warn('⚠️ Staff2 user not found');
            }
        }
    }
}
