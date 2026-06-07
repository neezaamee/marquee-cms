<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'document_name',
        'file_path',
        'document_type',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (CustomerDocument $doc) {
            if (auth()->check() && empty($doc->uploaded_by)) {
                $doc->uploaded_by = auth()->id();
            }
        });
    }

    /**
     * Get the customer this document belongs to.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who uploaded this document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
