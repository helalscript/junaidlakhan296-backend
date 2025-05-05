<?php
namespace App\Mail;

use File;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Log;

class InfoMailWithQrCode extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $subject;
    public $customMessage;
    public $qrCodeImage;

    public function __construct($user, $subject, $customMessage = null, $qrCodeImage = null)
    {
        $this->user = $user;
        $this->subject = $subject;
        $this->customMessage = $customMessage;
        $this->qrCodeImage = $qrCodeImage;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        Log::info(' ' . $this->qrCodeImage);
        return $this->subject($this->subject)
            ->view('mail.info_mail_with_qrcode');
    }

}

