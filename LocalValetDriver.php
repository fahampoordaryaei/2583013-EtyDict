<?php

declare(strict_types=1);

use Valet\Drivers\ValetDriver;

class LocalValetDriver extends ValetDriver
{
    public function serves(string $sitePath, string $siteName, string $uri): bool
    {
        return is_file($sitePath . '/public/index.php');
    }

    public function isStaticFile(string $sitePath, string $siteName, string $uri): string|false
    {
        $file = $this->publicFile($sitePath, $uri);
        if ($file === null || in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), [
            'php', 'phtml', 'phar', 'sql', 'log', 'ini', 'json', 'lock',
        ], true)) {
            return false;
        }

        return $file;
    }

    public function frontControllerPath(string $sitePath, string $siteName, string $uri): ?string
    {
        $public = $sitePath . '/public';
        $file = $this->publicFile($sitePath, $uri);
        if ($file === null) {
            $file = $this->publicFile($sitePath, rtrim($uri, '/') . '/index.php');
        }
        if ($file === null || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') {
            $file = $public . '/404.php';
        }

        $_SERVER['DOCUMENT_ROOT'] = $public;
        $_SERVER['SCRIPT_FILENAME'] = $file;
        $_SERVER['SCRIPT_NAME'] = substr($file, strlen($public));
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

        return $file;
    }

    private function publicFile(string $sitePath, string $uri): ?string
    {
        if (str_contains($uri, '\\') || str_contains($uri, "\0") || preg_match('#(?:^|/)\.|[:<>|?*"]#', $uri)) {
            return null;
        }

        $public = $sitePath . '/public';
        $candidate = $public . '/' . ltrim($uri, '/');
        if (!is_file($candidate)) {
            return null;
        }

        $root = realpath($public);
        $resolved = realpath($candidate);
        if ($root === false || $resolved === false
            || !str_starts_with(str_replace('\\', '/', $resolved), str_replace('\\', '/', $root) . '/')) {
            return null;
        }

        return $candidate;
    }
}
