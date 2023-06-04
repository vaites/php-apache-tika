<?php declare(strict_types=1);

namespace Vaites\ApacheTika\Contracts;

use Vaites\ApacheTika\Contracts\Metadata as MetadataContract;

interface Entity
{
    public function metadata(): MetadataContract;
}