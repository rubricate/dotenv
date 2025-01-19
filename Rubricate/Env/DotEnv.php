<?php

namespace Rubricate\Env;

class DotEnv
{
    protected $isFile = false;

    public function __construct(string $file)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException(".env file not found: {$file}");
        }

        $this->prepareFile($file);
    }

    private function prepareFile(string $file): void
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {

            if ($this->isNotComment($line)) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $key = trim($key);
            $value = $this->sanitizeValue($value);

            $this->setEnvironmentVariable($key, $value);
        }

        $this->isFile = true;
    }

    private function sanitizeValue(string $value): string
    {
        return trim($value, " \t\n\r\0\x0B\"'");
    }

    public function get(string $key, $default = null)
    {
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }

    public function required(array $keys): void
    {
        foreach ($keys as $key) {
            if ($this->get($key) === null) {

                throw new \RuntimeException(
                    "The required environment variable {$key} is not set."
                );
            }
        }
    }

    private function isNotComment($line)
    {
       return (strpos(trim($line), '#') === 0 || strpos($line, '=') === false);
    } 

    private function setEnvironmentVariable($k, $v)
    {
        putenv("$k=$v");
        $_ENV[$k] = $v;
        $_SERVER[$k] = $v;
    } 
}
