<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Reusable importer for any model that declares a static importColumns() method.
 *
 * Expected shape of importColumns():
 * [
 *   'excel_column_key' => [
 *       'label'     => 'Human Header',           // used by the template export
 *       'attribute' => 'model_attribute_name',   // where the value gets written
 *       'rules'     => 'required|string|max:255',// validated against the raw cell value
 *       'default'   => fn () => 'value',          // optional, used when cell is empty
 *       'resolve'   => fn ($value) => $value,      // optional, transforms the raw value
 *   ],
 *   ...
 * ]
 *
 * Row keys from WithHeadingRow are the slugified header text, so keep your
 * array keys snake_case and matching the intended header (e.g. 'first_name').
 */
class DynamicModelImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    protected array $columns;
    protected int $created = 0;

    public function __construct(
        protected string $modelClass,
        protected $repository
    ) {
        $this->columns = $modelClass::importColumns();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Skip fully blank rows (trailing empty rows in the sheet)
            if ($row->filter()->isEmpty()) {
                continue;
            }

            $data = [];

            foreach ($this->columns as $key => $definition) {
                $value = $row[$key] ?? null;

                if (($value === null || $value === '') && isset($definition['default'])) {
                    $value = ($definition['default'])();
                }

                if (isset($definition['resolve'])) {
                    $value = ($definition['resolve'])($value);
                }

                $data[$definition['attribute']] = $value;
            }

            $this->repository->create($data);
            $this->created++;
        }
    }

    public function rules(): array
    {
        $rules = [];

        foreach ($this->columns as $key => $definition) {
            if (!empty($definition['rules'])) {
                $rules[$key] = $definition['rules'];
            }
        }

        return $rules;
    }

    public function createdCount(): int
    {
        return $this->created;
    }
}
