<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedEmployeeDocument extends Model
{
    protected $fillable = [
        'employee_id',
        'document_template_id',
        'document_type',
        'document_number',
        'agreement_number',
        'title',
        'version',
        'file_path',
        'file_name',
        'file_size_kb',
        'status',
        'printed_at',
        'signed_at',
        'archived_at',
        'physical_location',
        'received_by_hr',
        'hr_remarks',
        'scanned_copy_path',
        'snapshot_data',
        'generated_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'file_size_kb' => 'integer',
        'printed_at' => 'datetime',
        'signed_at' => 'datetime',
        'archived_at' => 'datetime',
        'snapshot_data' => 'array',
    ];

    protected $appends = ['file_url', 'scanned_copy_url'];

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    public function getScannedCopyUrlAttribute(): ?string
    {
        return $this->scanned_copy_path ? asset('storage/' . $this->scanned_copy_path) : null;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
