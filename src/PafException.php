<?php

namespace Gcd\UkAddressLookup;

use Rhubarb\Crown\Exceptions\RhubarbException;

class PafException extends RhubarbException
{
    public function __construct(
        $privateMessage = '',
        $publicMessage = 'There was an issue with the address finder service. Please try again later.',
        ?\Exception $previous = null
    ) {
        parent::__construct($privateMessage, $previous);

        if ($publicMessage) {
            $this->publicMessage = $publicMessage;
        }
    }
}
