<?php

return [
    "not_found" => 'The requested user isn\'t found',
    "authenticated" => [
        "unauthenticated" =>
            "Authentication Required, log in to perform this action",
        "found" => 'Authenticated users can\'t perform this action',
        "logout" => 'You\'re now logged out',
    ],
    "deleted" => [
        "soft" => "Failed to delete user.",
        "restore" => "Failed to restore deleted user.",
        "force_delete" => "Failed to force delete deleted user.",
    ],
];
