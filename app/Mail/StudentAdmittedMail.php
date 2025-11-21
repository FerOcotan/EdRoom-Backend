<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentAdmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $studentName;
    public string $studentEmail;
    public string $classTitle;
    public string $classId;
    public ?string $notes;
    public ?string $classUrl;

    public function __construct(
        string $studentName,
        string $studentEmail,
        string $classTitle,
        string $classId,
        ?string $notes = null,
        ?string $classUrl = null
    ) {
        $this->studentName = $studentName;
        $this->studentEmail = $studentEmail;
        $this->classTitle = $classTitle;
        $this->classId = $classId;
        $this->notes = $notes;
        $this->classUrl = $classUrl;
    }

    public function build()
    {
        return $this->subject("Has sido admitido al curso: {$this->classTitle}")
            ->markdown('mail.admitted');
    }
}
