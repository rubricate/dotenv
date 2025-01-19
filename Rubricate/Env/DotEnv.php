<?php

namespace Rubricate\Env;

use InvalidArgumentException;
use RuntimeException;

class DotEnv
{
    private const COMMENT_PREFIX = '#';
    private const VARIABLE_DELIMITER = '=';

    protected bool $isFile = false;

    public function __construct(string $file)
    {
        if (!file_exists($file)) {
            throw new InvalidArgumentException(".env file not found: {$file}");
        }

        $this->prepareFile($file);
    }

    private function prepareFile(string $file): void
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $this->processLine($line);
        }

        $this->isFile = true;
    }

    public function isFile(): bool
    {
       return  $this->isFile;
    }

    private function processLine(string $line): void
    {
        if ($this->isComment($line) || !$this->isDelimiter($line)) {
            return;
        }

        [$key, $value] = array_map('trim', explode(self::VARIABLE_DELIMITER, $line, 2));

        $this->setEnvironmentVariable($key, $this->sanitizeValue($value));
    }

    private function sanitizeValue(string $value): string
    {
        return trim($value, " \t\n\r\0\x0B\"'");
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return getenv($key)?: $default;
    }

    public function required(array $keys): void
    {
        foreach ($keys as $key) {

                $this->get($key) ?? throw new RuntimeException(
                    "The required environment variable {$key} is not set."
                );
        }
    }

    private function isComment($line): bool
    {
        return str_starts_with(trim($line), self::COMMENT_PREFIX);
    }

    private function isDelimiter($line): bool
    {
        return str_contains($line, self::VARIABLE_DELIMITER);
    }

    private function setEnvironmentVariable($k, $v): void
    {
        putenv("$k=$v");
        $_ENV[$k] = $v;
        $_SERVER[$k] = $v;
    }
}

