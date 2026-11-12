<?php

namespace App\Models;

use App\Enums\RequestCategory;
use Database\Factories\ServiceTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A service the barangay offers, with its fee and required-document checklist (FR-14).
 */
#[Fillable(['name', 'category', 'fee', 'is_active'])]
class ServiceType extends Model
{
    /** @use HasFactory<ServiceTypeFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => RequestCategory::class,
            'fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<DocumentRequirement, $this>
     */
    public function documentRequirements(): HasMany
    {
        return $this->hasMany(DocumentRequirement::class)->orderBy('id');
    }

    /**
     * @return HasMany<ServiceRequest, $this>
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * The fee, checklist and category the submission forms need, as plain data for the page script.
     *
     * @return array{id: int, name: string, category: string, fee: string, documents: list<array{id: int, name: string, required: bool}>}
     */
    public function toFormOption(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category->label(),
            'fee' => $this->fee,
            'documents' => $this->documentRequirements->map(fn (DocumentRequirement $document): array => [
                'id' => $document->id,
                'name' => $document->document_name,
                'required' => $document->is_required,
            ])->values()->all(),
        ];
    }
}
