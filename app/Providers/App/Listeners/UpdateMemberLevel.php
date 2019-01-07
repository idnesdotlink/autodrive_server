<?php

namespace App\Providers\App\Listeners;

use App\Providers\App\Events\CreateMember;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateMemberLevel
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  CreateMember  $event
     * @return void
     */
    public function handle(CreateMember $event)
    {
        //
    }
}
