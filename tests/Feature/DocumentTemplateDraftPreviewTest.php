<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTemplateDraftPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_render_a_live_pdf_from_unsaved_draft_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $template = DocumentTemplate::create([
            'code' => 'preview-registration',
            'name' => 'Preview Registration',
            'type' => 'registration_form',
            'is_active' => true,
            'current_version' => 3,
            'design_config' => ['primary_color' => '#123456'],
            'sections_data' => ['header' => ['title' => 'Saved title']],
        ]);

        $this->actingAs($admin)
            ->post(route('document-templates.preview-pdf.live', $template), [
                'design_config' => ['primary_color' => '#654321'],
                'sections_data' => ['header' => ['title' => 'Unsaved draft title']],
            ])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="preview_preview-registration.pdf"')
            ->assertSee('%PDF-', false);

        $template->refresh();
        $this->assertSame('#123456', $template->design_config['primary_color']);
        $this->assertSame('Saved title', $template->sections_data['header']['title']);
        $this->assertSame(3, $template->current_version);
    }
}
