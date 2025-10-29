<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollaboratorImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filename',
        'original_filename',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the import.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para imports em processamento
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope para imports completados
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope para imports falhados
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Marcar import como iniciado
     */
    public function markAsStarted(int $totalRows): void
    {
        $this->update([
            'status' => 'processing',
            'total_rows' => $totalRows,
            'started_at' => now(),
        ]);
    }

    /**
     * Marcar import como completado
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Marcar import como falhado
     */
    public function markAsFailed(array $errors = []): void
    {
        $this->update([
            'status' => 'failed',
            'errors' => $errors,
            'completed_at' => now(),
        ]);
    }

    /**
     * Incrementar contadores
     */
    public function incrementCounters(bool $success = true): void
    {
        $this->increment('processed_rows');
        
        if ($success) {
            $this->increment('successful_rows');
        } else {
            $this->increment('failed_rows');
        }
    }

    /**
     * Adicionar erro para uma linha específica
     */
    public function addError(int $line, array $errors): void
    {
        $currentErrors = $this->errors ?? [];
        $currentErrors[$line] = $errors;
        
        $this->update(['errors' => $currentErrors]);
    }

    /**
     * Calcular progresso em porcentagem
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }

    /**
     * Getter para compatibilidade - success_count
     */
    public function getSuccessCountAttribute(): int
    {
        return $this->successful_rows ?? 0;
    }

    /**
     * Getter para compatibilidade - error_count
     */
    public function getErrorCountAttribute(): int
    {
        return $this->failed_rows ?? 0;
    }

    /**
     * Verificar se o import foi bem-sucedido
     */
    public function getIsSuccessfulAttribute(): bool
    {
        return $this->status === 'completed' && $this->failed_rows === 0;
    }

    /**
     * Obter resumo do import
     */
    public function getSummaryAttribute(): array
    {
        return [
            'total_rows' => $this->total_rows,
            'processed_rows' => $this->processed_rows,
            'successful_rows' => $this->successful_rows,
            'failed_rows' => $this->failed_rows,
            'progress_percentage' => $this->progress_percentage,
            'is_successful' => $this->is_successful,
            'duration' => $this->started_at && $this->completed_at 
                ? $this->completed_at->diffInSeconds($this->started_at) 
                : null,
        ];
    }
}