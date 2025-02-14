<?php

namespace App\Message;

use Symfony\Component\HttpFoundation\File\File;

final class ImportCsvCustomers
{

     public function __construct(
         public readonly string $filepath,
         public readonly string $filename
     ) {
     }
}
