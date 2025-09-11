<?php

namespace fenomeno\WallsOfBetrayal\Database;

use fenomeno\WallsOfBetrayal\Database\Contrasts\RepositoryInterface;
use fenomeno\WallsOfBetrayal\Exceptions\Database\InvalidDatabaseType;

class SqlQueriesFileManager
{
    public const MYSQL  = 'mysql';
    public const SQLITE = 'sqlite';
    private const SUPPORTED_TYPES = [self::MYSQL, self::SQLITE];

    /** @var array<string, array<string>> */
    private array $sharedFiles = [];

    /** @var array<string, array<string>> */
    private array $queryFiles = [];

    /**
     * @param string $databaseType Type (mysql/sqlite)
     * @param array<class-string<RepositoryInterface>> $repositories
     *
     * @throws InvalidDatabaseType
     */
    public function __construct(private readonly string $databaseType, array $repositories)
    {
        $this->validateDatabaseType();
        $this->initFilesArrays();
        $this->loadRepositoryFiles($repositories);
    }

    /**
     * @return array<string>
     */
    public function getQueryFiles(): array
    {
        return $this->queryFiles[$this->databaseType] ?? [];
    }

    public function getAllQueryFiles(): array
    {
        return $this->queryFiles;
    }

    /**
     * @param string $databaseType
     * @param string $filePath
     * @return self
     *
     * @throws InvalidDatabaseType
     */
    public function addSharedFile(string $databaseType, string $filePath): self
    {
        if (! in_array($databaseType, self::SUPPORTED_TYPES, true)) {
            throw new InvalidDatabaseType("Invalid database type: $databaseType");
        }

        if (! in_array($filePath, $this->sharedFiles[$databaseType], true)) {
            $this->sharedFiles[$databaseType][] = $filePath;
            $this->queryFiles[$databaseType][]  = $filePath;
        }

        return $this;
    }

    /**
     * @throws InvalidDatabaseType
     */
    private function validateDatabaseType(): void
    {
        if (! in_array($this->databaseType, self::SUPPORTED_TYPES, true)) {
            throw new InvalidDatabaseType("Invalid database type: $this->databaseType");
        }
    }

    private function initFilesArrays(): void
    {
        $this->sharedFiles = $this->queryFiles = array_fill_keys(self::SUPPORTED_TYPES, []);
    }

    /**
     * @param array<class-string<RepositoryInterface>> $repositories
     */
    private function loadRepositoryFiles(array $repositories): void
    {
        foreach ($repositories as $repository) {
            if (! is_a($repository, RepositoryInterface::class, true)) {
                continue;
            }

            $files = $repository::getQueriesFiles();

            if (isset($files[self::MYSQL]) || isset($files[self::SQLITE])) {
                foreach (self::SUPPORTED_TYPES as $type) {
                    if (isset($files[$type])) {
                        $repFiles = is_array($files[$type]) ? $files[$type] : [$files[$type]];
                        if(! is_array($repFiles)) {
                            continue;
                        }
                        $this->queryFiles[$type] = array_merge($this->queryFiles[$type], $repFiles);
                    }
                }
            }
        }

        foreach (self::SUPPORTED_TYPES as $type) {
            $this->queryFiles[$type] = array_unique($this->queryFiles[$type]);
        }
    }
}