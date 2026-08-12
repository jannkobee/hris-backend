<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Administrator Roles
    |--------------------------------------------------------------------------
    |
    | Users assigned one of these roles may start chats with any user. All
    | other users may start chats only with people in one of these roles.
    |
    */
    'administrator_roles' => array_filter(explode(',', env('MESSAGING_ADMIN_ROLES', 'Admin'))),
];
