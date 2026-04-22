<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SQLite does not support adding CHECK constraints via ALTER TABLE.
     * We rebuild the table with the constraint included, using explicit column
     * names in the INSERT to avoid positional-mapping issues from prior migrations.
     */
    public function up(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('DROP TABLE IF EXISTS documents_new');

        DB::statement('
            CREATE TABLE documents_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                request_id INTEGER NULL,
                request_type_id INTEGER NULL,
                uploaded_by INTEGER NOT NULL,
                uploader_role VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                is_template TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                document_type VARCHAR(255) NOT NULL DEFAULT \'user_submission\'
                    CHECK (document_type IN (\'template\',\'user_submission\',\'staff_attachment\')),
                name VARCHAR(255) NULL,
                description TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                download_count INTEGER NOT NULL DEFAULT 0,
                FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
                FOREIGN KEY (request_type_id) REFERENCES request_types(id) ON DELETE SET NULL,
                FOREIGN KEY (uploaded_by) REFERENCES users(id)
            )
        ');

        DB::statement('
            INSERT INTO documents_new
                (id, request_id, request_type_id, uploaded_by, uploader_role,
                 file_path, original_name, is_template, created_at, updated_at,
                 document_type, name, description, is_active, download_count)
            SELECT
                id, request_id, request_type_id, uploaded_by, uploader_role,
                file_path, original_name, is_template, created_at, updated_at,
                document_type, name, description, is_active, download_count
            FROM documents
        ');

        DB::statement('DROP TABLE documents');
        DB::statement('ALTER TABLE documents_new RENAME TO documents');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('DROP TABLE IF EXISTS documents_new');

        DB::statement('
            CREATE TABLE documents_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                request_id INTEGER NULL,
                request_type_id INTEGER NULL,
                uploaded_by INTEGER NOT NULL,
                uploader_role VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                is_template TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                document_type VARCHAR(255) NOT NULL DEFAULT \'user_submission\',
                name VARCHAR(255) NULL,
                description TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                download_count INTEGER NOT NULL DEFAULT 0,
                FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
                FOREIGN KEY (request_type_id) REFERENCES request_types(id) ON DELETE SET NULL,
                FOREIGN KEY (uploaded_by) REFERENCES users(id)
            )
        ');

        DB::statement('
            INSERT INTO documents_new
                (id, request_id, request_type_id, uploaded_by, uploader_role,
                 file_path, original_name, is_template, created_at, updated_at,
                 document_type, name, description, is_active, download_count)
            SELECT
                id, request_id, request_type_id, uploaded_by, uploader_role,
                file_path, original_name, is_template, created_at, updated_at,
                document_type, name, description, is_active, download_count
            FROM documents
        ');

        DB::statement('DROP TABLE documents');
        DB::statement('ALTER TABLE documents_new RENAME TO documents');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
