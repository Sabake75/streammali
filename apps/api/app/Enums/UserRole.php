<?php

namespace App\Enums;

enum UserRole: string
{
    case Creator = 'creator';
    case Viewer = 'viewer';
    case Moderator = 'moderator';
}
