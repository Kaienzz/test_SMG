<?php

namespace App\Services\StandardItem;

use Exception;

class StandardItemValidationException extends Exception
{
    private array $errors;
    
    public function __construct(array $errors, string $message = 'Standard item validation failed')
    {
        $this->errors = $errors;
        parent::__construct($message);
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    public function getErrorsAsString(): string
    {
        return implode(', ', $this->errors);
    }
}