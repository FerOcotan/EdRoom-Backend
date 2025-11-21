<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClassScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $studentName;
    public string $classTitle;
    public ?string $startAt;
    public ?string $endAt;
    public ?string $classUrl;
    public ?string $teacherName;

    public function __construct(
        string $studentName,
        string $classTitle,
        ?string $startAt = null,
        ?string $endAt = null,
        ?string $classUrl = null,
        ?string $teacherName = null
    ) {
        $this->studentName = $studentName;
        $this->classTitle = $classTitle;
        $this->startAt = $startAt;
        $this->endAt = $endAt;
        $this->classUrl = $classUrl;
        $this->teacherName = $teacherName;
    }

    public function build()
    {
        return $this->subject("Nueva sesión programada: {$this->classTitle}")
            ->markdown('mail.class_scheduled');
    }
}
