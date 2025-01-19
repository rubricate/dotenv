<?php

namespace Rubricate\Env;

use InvalidArgumentException;
use RuntimeException;

class DotEnv
{
    private const COMMENT_PREFIX = '#';
    private const VARIABLE_DELIMITER = '=';

    protected $isFile = false;

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

            if ($this->isComment($line) || $this->isDelimiter($line)) {
                return;
            }

            [$key, $value] = array_map('trim', explode(self::VARIABLE_DELIMITER, $line, 2));

            $this->setEnvironmentVariable($key, $this->sanitizeValue($value));
        }

        $this->isFile = true;
    }

    private function sanitizeValue(string $value): string
    {
        return trim($value, " \t\n\r\0\x0B\"'");
    }

    public function get(string $key, $default = null): ?string
    {
        $value = getenv($key);
        return ($value !== false)? $value: $default;
    }

    public function required(array $keys): void
    {
        foreach ($keys as $key) {
            if ($this->get($key) === null) {

                throw new RuntimeException(
                    "The required environment variable {$key} is not set."
                );
            }
        }
    }

    private function isComment($line): bool
    {
        return (
            strpos(trim($line), self::COMMENT_PREFIX) === 0
        );
    }

    private function isDelimiter($line): bool
    {
        return (
            strpos($line, self::VARIABLE_DELIMITER) === false
        );
    }

    private function setEnvironmentVariable($k, $v): void
    {
        putenv("$k=$v");
        $_ENV[$k] = $v;
        $_SERVER[$k] = $v;
    }
}

