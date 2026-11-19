<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. employee_registration_form, employment_bond
            $table->string('name');
            $table->string('type')->default('general'); // registration_form, employment_bond, general
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('current_version')->default(1);
            $table->json('design_config')->nullable(); // primary_color, secondary_color, font_family, margins, etc.
            $table->json('sections_data')->nullable(); // structured sections, fields, clauses, signatures
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('document_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('design_config')->nullable();
            $table->json('sections_data')->nullable();
            $table->string('change_summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['document_template_id', 'version_number'], 'dtv_tmpl_ver_uniq');
        });

        Schema::create('generated_employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('document_template_id')->nullable()->constrained('document_templates')->nullOnDelete();
            $table->string('document_type'); // registration_form, employment_bond, other
            $table->string('document_number')->unique(); // e.g. GOS-REG-0001-V1, GOS-BOND-0001-V1
            $table->string('agreement_number')->nullable();
            $table->string('title');
            $table->unsignedInteger('version')->default(1);
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedInteger('file_size_kb')->default(0);
            $table->string('status')->default('generated'); // generated, printed, signed, archived
            $table->timestamp('printed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('physical_location')->nullable(); // e.g. Cabinet A, Shelf 2, Folder GSK-001
            $table->string('received_by_hr')->nullable();
            $table->text('hr_remarks')->nullable();
            $table->string('scanned_copy_path')->nullable();
            $table->json('snapshot_data')->nullable(); // variables snapshot at generation time
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_employee_documents');
        Schema::dropIfExists('document_template_versions');
        Schema::dropIfExists('document_templates');
    }
};
