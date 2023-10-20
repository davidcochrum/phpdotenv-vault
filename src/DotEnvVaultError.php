<?php

namespace DotenvVault;

use Exception;
use Throwable;

class DotEnvVaultError extends Exception
{
    public static function fromApiResponse(string $response, string $defaultCodeString): self
    {
        $data = json_decode($response);
        if ($error1 = $data->errors[0] ?? null) {
            return new self($error1->message,$error1->code ?? $defaultCodeString, $error1->suggestions ?? []);
        }
        return new self($defaultCodeString, $defaultCodeString);
    }

    /** @var string */
    private $codeString;
    /** @var string[] */
    private $suggestions;

    /**
     * @param string[] $suggestions
     */
    public function __construct($message = "", string $codeString = '', array $suggestions = [], int $status = 0, Throwable $previous = null)
    {
        $this->codeString = $codeString;
        $this->suggestions = array_map(
            function (string $suggestion) {
                return preg_replace('/npx dotenv-vault(@latest)?/i', 'vendor/bin/dotenv-vault', $suggestion);
            },
            $suggestions
        );
        parent::__construct($message, $status, $previous);
    }

    public function getCodeString(): string
    {
        return $this->codeString;
    }

    /** @return string[] */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    public function addSuggestion(string $suggestion): void
    {
        $this->suggestions[] = $suggestion;
    }
}