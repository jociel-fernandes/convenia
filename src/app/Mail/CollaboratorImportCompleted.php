<?php

namespace App\Mail;

use App\Models\CollaboratorImport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CollaboratorImportCompleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public CollaboratorImport $import,
        public User $user
    ) {
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->import->status === 'completed' 
            ? 'Import de Colaboradores Finalizado com Sucesso'
            : 'Import de Colaboradores Finalizado com Erros';

        return new Envelope(
            to: [$this->user->email],
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.collaborator-import-completed',
            with: [
                'user' => $this->user,
                'import' => $this->import,
                'isSuccess' => $this->import->status === 'completed',
                'hasErrors' => $this->import->failed_rows > 0,
                'totalRows' => $this->import->total_rows,
                'successfulRows' => $this->import->successful_rows,
                'failedRows' => $this->import->failed_rows,
                'errors' => $this->import->errors,
                'progressPercentage' => $this->import->progress_percentage,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}