<?php

declare(strict_types=1);

namespace App\Enums;

enum XMediaType: string
{
    case Photo = 'photo';
    case Video = 'video';
    case Gif = 'gif';
}
