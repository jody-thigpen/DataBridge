<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Candidate invite link lifetime
    |--------------------------------------------------------------------------
    |
    | Intake invitation links remain valid for this many days after they are
    | sent (or resent). After expiry the candidate must receive a new invite
    | from a client user.
    |
    */

    'invite_ttl_days' => (int) env('CANDIDATE_INVITE_TTL_DAYS', 3),

];
