<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Entity\File;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<File>
 */
final class FileFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return File::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'originalFilename' => self::faker()->word() . '.csv',
            'storedFilename' => self::faker()->uuid() . '.csv',
            'path' => sys_get_temp_dir() . '/' . self::faker()->uuid() . '.csv',
            'mimeType' => 'text/csv',
            'extension' => 'csv',
            'size' => self::faker()->numberBetween(10, 1_000_000),
        ];
    }
}
