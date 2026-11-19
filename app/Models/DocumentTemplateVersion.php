<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplateVersion extends Model
{
    protected $fillable = [
        'document_template_id',
        'version_number',
        'design_config',
        'sections_data',
        'change_summary',
        'created_by',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'design_config' => 'array',
        'sections_data' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
