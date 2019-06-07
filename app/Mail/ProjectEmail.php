<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProjectEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $extra;
    protected $action;
    protected $projectName;

    /**
     * Create a new message instance.
     *
     * @param string $action
     * @param string $projectName
     * @param array $extra
     */
    public function __construct($action, $projectName, $extra = null)
    {
        $this->extra = $extra;
        $this->action = $action;
        $this->projectName = $projectName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.email')->with(
            [
                'extra' => $this->extra,
                'action' => $this->action,
                'projectName' => $this->projectName,
            ]
        );
    }
}
