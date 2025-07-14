<?php
namespace App\Message;

class SendEmailMessage
{
    private string $email;
    private string $resetUrl;

    public function __construct(string $email, string $resetUrl)
    {
        $this->email = $email;
        $this->resetUrl = $resetUrl;
    }

    public function getEmail(): string { return $this->email; }
    public function getResetUrl(): string { return $this->resetUrl; }
}