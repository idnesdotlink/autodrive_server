<?php

namespace App\Listeners;

use App\Events\CreateMember;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

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
        /* $db = DB::connection('autodrive_tip');
        $event_log = $db->table('event_logs');
        $event_log->insert([
            ['name' => 'create member']
        ]); */
        $event->members->log();
    }
}