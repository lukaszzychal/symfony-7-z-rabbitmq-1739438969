<?php

namespace App\Message;

use Symfony\Component\HttpFoundation\File\File;

final class ProgressBarMessage
{
     public function __construct(
         public readonly string $fileName,
         public readonly int $percentage
     ) {
     }
}
