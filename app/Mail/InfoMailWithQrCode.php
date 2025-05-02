<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class InfoMailWithQrCode extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $subject;
    public $customMessage;
    public $qrCodeImage;
    public $descriptionMessage;

    public function __construct($user, $subject, $customMessage = null, $qrCodeImage = null, $descriptionMessage = null)
    {
        $this->user = $user;
        $this->subject = $subject;
        $this->customMessage = $customMessage;
        $this->qrCodeImage = $qrCodeImage;
        $this->descriptionMessage = $descriptionMessage;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)
            // ->attach(storage_path('app/public/' . $this->qrImagePath))
            ->view('mail.info_mail_with_qrcode');
    }
}

