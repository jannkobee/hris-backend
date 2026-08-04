<?php

namespace App\Traits;

use App\Exports\DynamicTemplateExport;
use App\Imports\DynamicModelImport;

/**
 * Add to any model that should support Excel import/template generation.
 * The model only has to implement importColumns() — everything else is
 * handled generically.
 */
trait Importable
{
    /**
     * Column definitions for the Excel import/template. Must be implemented
     * by the model. See DynamicModelImport for the expected array shape.
     */
    abstract public static function importColumns(): array;

    public static function importTemplate(): DynamicTemplateExport
    {
        return new DynamicTemplateExport(static::class);
    }

    public static function importer($repository): DynamicModelImport
    {
        return new DynamicModelImport(static::class, $repository);
    }
}
