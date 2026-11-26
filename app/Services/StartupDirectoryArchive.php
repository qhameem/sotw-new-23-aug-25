<?php

namespace App\Services;

use App\Models\StartupDirectory;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class StartupDirectoryArchive
{
    public const HEADER = ['Directory name', 'URL', 'DR', 'Link type', 'Price'];

    public function path(): string
    {
        return base_path('launchpanda-best-startup-directories.csv');
    }

    public static function urlKey(string $url): string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $host = str_starts_with($host, 'www.') ? substr($host, 4) : $host;

        return $host.(isset($parts['port']) ? ':'.$parts['port'] : '')
            .rtrim($parts['path'] ?? '', '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    public function withLock(callable $operation): mixed
    {
        $lockPath = storage_path('app/startup-directory-archive.lock');
        $lock = @fopen($lockPath, 'c') ?: @fopen($lockPath, 'r');

        if ($lock === false) {
            throw new RuntimeException('Unable to open the directory archive lock.');
        }

        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('Unable to lock the directory archive.');
            }

            return $operation();
        } finally {
            fclose($lock);
        }
    }

    public function delete(StartupDirectory $directory): void
    {
        $this->withLock(function () use ($directory): void {
            $path = $this->path();
            $original = @file_get_contents($path);
            if ($original === false) {
                throw new RuntimeException('Unable to read the directory archive.');
            }

            $replaced = false;

            try {
                DB::transaction(function () use ($directory, $path, $original, &$replaced): void {
                    $current = StartupDirectory::query()->lockForUpdate()->findOrFail($directory->getKey());
                    $key = self::urlKey($current->original_website_url ?? $current->website_url);
                    $filtered = $this->withoutUrl($original, $key);
                    $current->delete();

                    if ($filtered !== $original) {
                        $this->replace($path, $filtered);
                        $replaced = true;
                    }
                });
            } catch (Throwable $exception) {
                if ($replaced) {
                    $this->replace($path, $original);
                }

                throw $exception;
            }
        });
    }

    private function withoutUrl(string $original, string $key): string
    {
        $stream = fopen('php://memory', 'w+');

        try {
            fwrite($stream, $original);
            rewind($stream);
            if (fgetcsv($stream, escape: '') !== self::HEADER) {
                throw new RuntimeException('The directory archive has an unexpected header.');
            }

            $offset = ftell($stream);
            $filtered = substr($original, 0, $offset);

            while (($row = fgetcsv($stream, escape: '')) !== false) {
                $end = ftell($stream);
                if (count($row) !== count(self::HEADER)) {
                    throw new RuntimeException('A directory archive row has an unexpected number of fields.');
                }

                if (self::urlKey($row[1]) !== $key) {
                    $filtered .= substr($original, $offset, $end - $offset);
                }
                $offset = $end;
            }

            return $filtered;
        } finally {
            fclose($stream);
        }
    }

    private function replace(string $path, string $contents): void
    {
        $directory = realpath(dirname($path));
        $temporary = $directory === false ? false : @tempnam($directory, '.startup-directories-');
        if ($temporary === false) {
            throw new RuntimeException('Unable to create a temporary directory archive.');
        }

        try {
            $mode = fileperms($path);
            if (dirname($temporary) !== $directory || $mode === false
                || @file_put_contents($temporary, $contents) !== strlen($contents)
                || ! @chmod($temporary, $mode & 0777)
                || ! @rename($temporary, $path)) {
                throw new RuntimeException('Unable to replace the directory archive.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
