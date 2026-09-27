<?php

declare(strict_types=1);

namespace Garage\Services;

use Garage\Core\Database;
use Garage\Models\Brand;
use Garage\Models\Car;
use Garage\Models\Series;
use Garage\Support\Str;

/**
 * CSV import/export of the collection data (photos are added later, car by car).
 *
 * Import is all-or-nothing: every row is validated first; if any row fails, nothing
 * is written and the errors are reported by line. A row whose "slug" matches an
 * existing car updates it, so export → edit in Excel → import round-trips.
 */
final class CsvService
{
    public const MAX_BYTES = 2 * 1024 * 1024;
    public const MAX_ROWS = 2000;

    public const COLUMNS = [
        'slug', 'name', 'brand', 'model', 'cost_mxn', 'series', 'real_year', 'casting_year',
        'collection_number', 'color', 'rarity', 'condition', 'acquired_at', 'notes', 'favorite',
    ];

    /** Accepted header spellings (lower-case, accents removed) → column. */
    private const ALIASES = [
        'nombre' => 'name', 'hot wheels' => 'name', 'casting' => 'name',
        'marca' => 'brand', 'modelo' => 'model',
        'costo' => 'cost_mxn', 'cost' => 'cost_mxn', 'precio' => 'cost_mxn', 'price' => 'cost_mxn', 'costo_mxn' => 'cost_mxn',
        'serie' => 'series', 'linea' => 'series',
        'ano' => 'real_year', 'ano_real' => 'real_year', 'year' => 'real_year',
        'ano_pieza' => 'casting_year', 'ano_fabricacion' => 'casting_year',
        'numero' => 'collection_number', 'numero_coleccion' => 'collection_number', 'number' => 'collection_number',
        'rareza' => 'rarity', 'estado' => 'condition', 'item_condition' => 'condition',
        'fecha' => 'acquired_at', 'fecha_adquisicion' => 'acquired_at', 'acquired' => 'acquired_at',
        'notas' => 'notes', 'favorito' => 'favorite', 'is_favorite' => 'favorite',
    ];

    private const RARITY_ALIASES = [
        'mainline' => 'mainline', 'basico' => 'mainline',
        'treasure hunt' => 'treasure_hunt', 'th' => 'treasure_hunt',
        'super treasure hunt' => 'super_treasure_hunt', 'sth' => 'super_treasure_hunt', '$th' => 'super_treasure_hunt',
        'premium' => 'premium', 'red line club' => 'red_line_club', 'rlc' => 'red_line_club',
        'edicion limitada' => 'limited', 'limited edition' => 'limited', 'limitada' => 'limited', 'limited' => 'limited',
        'otro' => 'other', 'other' => 'other',
    ];

    private const CONDITION_ALIASES = [
        'en blister' => 'carded', 'blister' => 'carded', 'carded' => 'carded', 'sellado' => 'carded',
        'suelto' => 'loose', 'loose' => 'loose',
        'danado' => 'damaged', 'damaged' => 'damaged',
    ];

    /** Stream the collection as CSV (UTF-8 with BOM so Excel shows accents). */
    public static function export(bool $includeCost): string
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        $columns = $includeCost ? self::COLUMNS : array_values(array_diff(self::COLUMNS, ['cost_mxn']));
        fputcsv($out, $columns, ',', '"', '');

        foreach (Car::all() as $car) {
            $row = [
                'slug' => $car['slug'], 'name' => $car['name'], 'brand' => $car['brand_name'], 'model' => $car['model'],
                'cost_mxn' => $car['cost_mxn'], 'series' => $car['series_name'], 'real_year' => $car['real_year'],
                'casting_year' => $car['casting_year'], 'collection_number' => $car['collection_number'],
                'color' => $car['color'], 'rarity' => $car['rarity'], 'condition' => $car['item_condition'],
                'acquired_at' => $car['acquired_at'], 'notes' => $car['notes'], 'favorite' => $car['is_favorite'] ? '1' : '0',
            ];
            fputcsv($out, array_map([self::class, 'safeCell'], array_map(static fn (string $c) => $row[$c], $columns)), ',', '"', '');
        }

        rewind($out);
        $csv = (string) stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    /**
     * @return array{ok: bool, created: int, updated: int, errors: array<int, string>}
     */
    public static function import(string $path): array
    {
        $rows = self::read($path);
        if (isset($rows['error'])) {
            return ['ok' => false, 'created' => 0, 'updated' => 0, 'errors' => [0 => $rows['error']]];
        }

        // Pass 1: validate everything. Unknown brands/series are created in pass 2.
        $errors = [];
        $plan = [];
        foreach ($rows['rows'] as $line => $row) {
            $existing = $row['slug'] !== '' ? Car::findBySlug($row['slug']) : null;
            if ($row['slug'] !== '' && $existing === null) {
                $errors[$line] = t('csv.error.slug', ['slug' => $row['slug']]);
                continue;
            }

            $brandName = Str::cut($row['brand'], 80);
            $seriesName = Str::cut($row['series'], 80);
            $input = CarInput::fromArray([
                'name' => $row['name'], 'model' => $row['model'], 'cost_mxn' => $row['cost_mxn'],
                'brand_id' => $brandName !== '' ? (Brand::findByName($brandName)['id'] ?? PHP_INT_MAX) : 0,
                'series_id' => 0,
                'real_year' => $row['real_year'], 'casting_year' => $row['casting_year'],
                'collection_number' => $row['collection_number'], 'color' => $row['color'],
                'rarity' => self::mapAlias($row['rarity'], self::RARITY_ALIASES),
                'item_condition' => self::mapAlias($row['condition'], self::CONDITION_ALIASES),
                'acquired_at' => $row['acquired_at'], 'notes' => $row['notes'], 'is_favorite' => $row['favorite'],
            ], keepCost: $existing !== null);

            unset($input->errors['brand_id']);   // new brands are allowed
            if ($brandName === '') {
                $input->errors['brand_id'] = 'validation.required';
            }
            if (!$input->passes()) {
                $errors[$line] = implode('; ', array_map(
                    static fn (string $field, string $key): string => t('field.' . $field) . ': ' . t($key),
                    array_keys($input->errors),
                    $input->errors
                ));
                continue;
            }

            $plan[] = ['existing' => $existing, 'data' => $input->data, 'brand' => $brandName, 'series' => $seriesName];
        }

        if ($errors !== []) {
            return ['ok' => false, 'created' => 0, 'updated' => 0, 'errors' => array_slice($errors, 0, 25, true)];
        }

        // Pass 2: write everything in one transaction.
        $created = $updated = 0;
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            foreach ($plan as $item) {
                $item['data']['brand_id'] = (int) (Brand::findByName($item['brand'])['id'] ?? Brand::create($item['brand'], '#141414'));
                $item['data']['series_id'] = $item['series'] === ''
                    ? null
                    : (int) (Series::findByName($item['series'])['id'] ?? Series::create($item['series']));

                if ($item['existing']) {
                    Car::update((int) $item['existing']['id'], $item['data']);
                    $updated++;
                } else {
                    Car::create($item['data']);
                    $created++;
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return ['ok' => true, 'created' => $created, 'updated' => $updated, 'errors' => []];
    }

    /** @return array{rows: array<int, array<string, string>>}|array{error: string} */
    private static function read(string $path): array
    {
        $content = (string) file_get_contents($path);
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        if (!preg_match('//u', $content)) {
            $converted = function_exists('iconv') ? @iconv('Windows-1252', 'UTF-8//IGNORE', $content) : false;
            if ($converted === false) {
                return ['error' => t('csv.error.encoding')];
            }
            $content = $converted;
        }

        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $header = fgetcsv($stream, 0, $delimiter, '"', '');
        if (!is_array($header)) {
            return ['error' => t('csv.error.empty')];
        }
        $map = [];
        foreach ($header as $i => $name) {
            $key = str_replace([' ', '-'], ['_', '_'], strtolower(strtr(Str::clean((string) $name), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n'])));
            $key = self::ALIASES[$key] ?? self::ALIASES[str_replace('_', ' ', $key)] ?? $key;
            if (in_array($key, self::COLUMNS, true)) {
                $map[$i] = $key;
            }
        }
        foreach (['name', 'brand', 'model'] as $required) {
            if (!in_array($required, $map, true)) {
                return ['error' => t('csv.error.columns')];
            }
        }

        $rows = [];
        $line = 1;
        while (($cells = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $line++;
            if ($cells === [null] || implode('', array_map('strval', $cells)) === '') {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                return ['error' => t('csv.error.too_many', ['max' => (string) self::MAX_ROWS])];
            }
            $row = array_fill_keys(self::COLUMNS, '');
            foreach ($map as $i => $column) {
                $row[$column] = Str::clean($cells[$i] ?? '', $column === 'notes');
            }
            $rows[$line] = $row;
        }
        fclose($stream);

        return $rows === [] ? ['error' => t('csv.error.empty')] : ['rows' => $rows];
    }

    private static function mapAlias(string $value, array $aliases): string
    {
        if ($value === '') {
            return '';
        }
        $key = strtolower(strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n', '_' => ' ']));

        return $aliases[$key] ?? $value;
    }

    /** Neutralize spreadsheet formulas (CSV injection) in text cells. */
    private static function safeCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) && !is_numeric($value)
            ? "'" . $value
            : $value;
    }
}
