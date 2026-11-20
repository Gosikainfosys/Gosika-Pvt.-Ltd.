<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentVaultTest extends TestCase
{
    use RefreshDatabase;

    private const COMPANY_CATEGORY = '01_COMPANY_LEGAL_DOCUMENTS';
    private const HR_CATEGORY = '02_HUMAN_RESOURCES_HR';

    public function test_document_vault_lists_folder_categories_and_filters_by_category_and_search(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $companyDocument = $this->createDocument(self::COMPANY_CATEGORY, 'Company incorporation');
        $hrDocument = $this->createDocument(self::HR_CATEGORY, 'Personnel handbook');

        $this->actingAs($user)
            ->get(route('documents.index', [
                'category' => self::HR_CATEGORY,
                'search' => 'Personnel',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Documents/Index')
                ->has('categories', 11)
                ->where('categories.0.value', self::COMPANY_CATEGORY)
                ->where('categories.0.label', self::COMPANY_CATEGORY)
                ->where('categories.0.description', 'Official company registration, PAN, VAT, MOA/AOA, licenses, legal certificates and company agreements.')
                ->where('categories.1.value', self::HR_CATEGORY)
                ->where('categories.1.description', 'Employee personal records, registration forms, employment agreements, attendance, leave, salary and HR documents.')
                ->where('categories.2.description', 'Company income, expenses, cash books, bank transactions, invoices, purchases, sales and financial statements.')
                ->where('categories.3.description', 'Office assets, inventory, maintenance, visitors, correspondence, meetings and daily administration records.')
                ->where('categories.4.description', 'Vendor registrations, product information, product uploads, stock, customer inquiries, orders, complaints and returns.')
                ->where('categories.5.description', 'Project planning, software requirements, architecture, database design, development documentation and project progress.')
                ->where('categories.6.description', 'Software testing, bugs, deployment, hosting, domains, backups, security, technical access and maintenance records.')
                ->where('categories.7.description', 'Company logos, brand guidelines, promotional designs, social media content, marketing campaigns, photos and videos.')
                ->where('categories.8.description', 'Daily, weekly and monthly reports, project progress, management decisions, meeting minutes and business reviews.')
                ->where('categories.9.description', 'Excel, Word and PDF templates, official letterheads, reusable forms and master document designs.')
                ->where('categories.10.value', '11_ARCHIVE_OLD_RECORDS')
                ->where('categories.10.description', 'Previous-year records, completed projects, former employee documents, expired agreements and historical records.')
                ->where('documents.total', 1)
                ->where('documents.data.0.id', $hrDocument->id)
                ->where('documents.data.0.category', self::HR_CATEGORY)
                ->where('stats.categories.' . self::HR_CATEGORY, 1)
            );

        $this->assertNotSame($companyDocument->id, $hrDocument->id);
    }

    public function test_document_can_be_uploaded_to_a_folder_category(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('documents.store'), [
                'title' => 'Company registration',
                'category' => self::COMPANY_CATEGORY,
                'file' => UploadedFile::fake()->create('registration.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect();

        $document = Document::firstOrFail();
        $this->assertSame(self::COMPANY_CATEGORY, $document->category);
        $this->assertSame($user->id, $document->uploaded_by);
        Storage::disk('public')->assertExists($document->file_path);
    }

    public function test_upload_reports_a_clear_message_when_php_rejects_the_file(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $file = UploadedFile::fake()->create('oversized.pdf', 1, 'application/pdf');
        $failedUpload = new UploadedFile(
            $file->getPathname(),
            $file->getClientOriginalName(),
            'application/pdf',
            UPLOAD_ERR_INI_SIZE,
            true,
        );

        $this->actingAs($user)
            ->from(route('documents.index'))
            ->post(route('documents.store'), [
                'title' => 'Rejected document',
                'category' => self::COMPANY_CATEGORY,
                'file' => $failedUpload,
            ])
            ->assertRedirect(route('documents.index'))
            ->assertSessionHasErrors([
                'file' => 'PHP rejected this file because it exceeds upload_max_filesize. Set upload_max_filesize to 20M and post_max_size to 24M in cPanel PHP settings for this domain, then retry.',
            ]);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_legacy_categories_are_not_accepted_for_new_uploads(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($user)
            ->from(route('documents.index'))
            ->post(route('documents.store'), [
                'title' => 'Legacy category document',
                'category' => 'company',
                'file' => UploadedFile::fake()->create('legacy.pdf', 1, 'application/pdf'),
            ])
            ->assertRedirect(route('documents.index'))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_documents_can_be_downloaded_and_deleted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $document = $this->createDocument(self::COMPANY_CATEGORY, 'Company registration');
        Storage::disk('public')->put($document->file_path, 'document contents');

        $this->actingAs($user)
            ->get(route('documents.download', $document))
            ->assertOk()
            ->assertDownload($document->file_name);

        $this->delete(route('documents.destroy', $document))
            ->assertRedirect();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('public')->assertMissing($document->file_path);
    }

    public function test_pdf_document_can_be_previewed_inline(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $document = $this->createDocument(self::COMPANY_CATEGORY, 'Company registration');
        Storage::disk('public')->put(
            $document->file_path,
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF",
        );

        $this->actingAs($user)
            ->get(route('documents.preview', $document))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename=company-registration.pdf')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    private function createDocument(string $category, string $title): Document
    {
        return Document::create([
            'document_number' => 'TEST-DOC-' . str_pad((string) (Document::count() + 1), 3, '0', STR_PAD_LEFT),
            'title' => $title,
            'category' => $category,
            'file_path' => "documents/{$category}/" . str_replace(' ', '-', strtolower($title)) . '.pdf',
            'file_name' => str_replace(' ', '-', strtolower($title)) . '.pdf',
            'file_type' => 'pdf',
            'file_size_kb' => 1,
        ]);
    }
}
