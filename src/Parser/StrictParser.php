<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Parser;

class StrictParser extends DefaultParser
{
    public function __construct()
    {
        parent::__construct(true);
    }
}
