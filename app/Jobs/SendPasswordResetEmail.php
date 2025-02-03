<?php

namespace App\Jobs;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetEmail implements ShouldQueue
{
    use Queueable, Dispatchable, SerializesModels, InteractsWithQueue;

    protected $user;
    protected $link;

    public function __construct(User $user, $link)
    {
        $this->user = $user;
        $this->link = $link;
    }

    public function handle(): void
    {
        Mail::to($this->user->email)->send(new PasswordResetMail($this->user, $this->link));
    }
}
