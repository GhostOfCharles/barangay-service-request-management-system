<?php

namespace App\Models;

use App\Enums\TicketStatus;
use App\Support\YearlyNumber;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * An asynchronous issue ticket raised by a resident (FR-13), e.g. TK-2026-0012.
 */
#[Fillable(['ticket_no', 'resident_id', 'service_request_id', 'subject', 'status'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Resident, $this>
     */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    /**
     * The request the ticket is about, if the resident linked one.
     *
     * @return BelongsTo<ServiceRequest, $this>
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Open a new ticket with its first message; status starts as Open.
     */
    public static function open(Resident $resident, User $author, string $subject, string $message, ?int $serviceRequestId = null): self
    {
        return DB::transaction(function () use ($resident, $author, $subject, $message, $serviceRequestId) {
            $ticket = self::create([
                'ticket_no' => YearlyNumber::next(self::class, 'ticket_no', 'TK'),
                'resident_id' => $resident->id,
                'service_request_id' => $serviceRequestId,
                'subject' => $subject,
                'status' => TicketStatus::Open,
            ]);

            $ticket->messages()->create(['user_id' => $author->id, 'message' => $message]);

            return $ticket;
        });
    }

    /**
     * Add a reply. A Staff or Administrator reply sets Answered; a resident reply sets Open again.
     */
    public function reply(User $author, string $message): void
    {
        DB::transaction(function () use ($author, $message) {
            $this->messages()->create(['user_id' => $author->id, 'message' => $message]);
            $this->update(['status' => $author->isPersonnel() ? TicketStatus::Answered : TicketStatus::Open]);
        });
    }

    public function close(): void
    {
        $this->update(['status' => TicketStatus::Closed]);
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::Closed;
    }
}
