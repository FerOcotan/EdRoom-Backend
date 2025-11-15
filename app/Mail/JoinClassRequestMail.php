<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JoinClassRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $requesterName;
    public string $requesterEmail;
    public string $classTitle;
    public string $classId;
    public ?string $notes;
    public ?string $joinUrl;

    public function __construct(
        string $requesterName,
        string $requesterEmail,
        string $classTitle,
        string $classId,
        ?string $notes = null,
        ?string $joinUrl = null
    ) {
        $this->requesterName  = $requesterName;
        $this->requesterEmail = $requesterEmail;
        $this->classTitle     = $classTitle;
        $this->classId        = $classId;
        $this->notes          = $notes;
        $this->joinUrl        = $joinUrl;
    }

    public function build()
    {
        return $this->subject("Solicitud de ingreso a clase: {$this->classTitle}")
            ->markdown('mail.join_request');
    }
}
