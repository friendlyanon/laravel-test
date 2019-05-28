<?php

namespace App\Jobs;

use App\Mail\ProjectEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendProjectEmail implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $extra;
    protected $email;
    protected $action;
    protected $projectName;

    /**
     * Create a new job instance.
     *
     * @param string $email
     * @param string $action
     * @param string $projectName
     * @param array $extra
     */
    public function __construct($email, $action, $projectName, $extra = null) {
        $this->extra = $extra;
        $this->email = $email;
        $this->action = $action;
        $this->projectName = $projectName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        $email = new ProjectEmail($this->action, $this->projectName, $this->extra);

        Mail::to($this->email)->send($email);
    }
}
