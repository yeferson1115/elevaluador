<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificadosZipListoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public int $totalRegistros,
        public ?string $downloadUrl,
        public ?string $zipFileName,
        public bool $exportaTodosFiltrados,
        public string $filtro,
        public array $errores = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu ZIP de certificados está listo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.certificados-zip-listo',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
