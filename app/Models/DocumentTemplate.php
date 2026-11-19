<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'is_active',
        'current_version',
        'design_config',
        'sections_data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'current_version' => 'integer',
        'design_config' => 'array',
        'sections_data' => 'array',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentTemplateVersion::class)->orderBy('version_number', 'desc');
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedEmployeeDocument::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function defaultDesignConfig(): array
    {
        return [
            'primary_color' => '#F36B12',
            'secondary_color' => '#E94B0B',
            'accent_color' => '#FFF3EB',
            'text_color' => '#202124',
            'font_family' => 'DejaVu Sans',
            'font_size' => '10px',
            'page_margin_top' => '10mm',
            'page_margin_bottom' => '10mm',
            'page_margin_left' => '12mm',
            'page_margin_right' => '12mm',
            'show_watermark' => true,
            'watermark_opacity' => 0.05,
            'company_name' => 'GOSIKA INFOSYS',
            'tagline' => 'Build • Innovate • Grow',
            'header_top_right' => 'Together for a Better Tomorrow',
            'footer_text' => 'Gosika Infosys - Growing Together',
        ];
    }
}
