<?php

declare(strict_types=1);

namespace Garage\Services;

use Garage\Models\Brand;
use Garage\Models\Car;
use Garage\Models\Series;
use Garage\Support\Str;

/**
 * Validates car fields coming from the admin form or a CSV row.
 * Only name, brand and model are required (section 9 of the spec).
 */
final class CarInput
{
    /** @var array<string, string> field => translation key */
    public array $errors = [];

    /** @var array<string, mixed> normalized values ready for Car::create/update */
    public array $data = [];

    /**
     * @param bool $keepCost When true and the cost field is empty, the cost is left
     *                       untouched (the visitor never saw the private value).
     */
    public static function fromArray(array $in, bool $keepCost = false): self
    {
        $v = new self();

        $v->data['name'] = $v->text($in, 'name', 150, true);
        $v->data['model'] = $v->text($in, 'model', 150, true);

        $brandId = (int) ($in['brand_id'] ?? 0);
        if ($brandId <= 0 || Brand::find($brandId) === null) {
            $v->errors['brand_id'] = 'validation.brand';
        }
        $v->data['brand_id'] = $brandId;

        $cost = Str::clean($in['cost_mxn'] ?? '');
        if (!($keepCost && $cost === '')) {
            $v->data['cost_mxn'] = $v->money($cost);
        }

        $seriesId = (int) ($in['series_id'] ?? 0);
        if ($seriesId > 0 && Series::find($seriesId) === null) {
            $v->errors['series_id'] = 'validation.series';
        }
        $v->data['series_id'] = $seriesId > 0 ? $seriesId : null;

        $maxYear = (int) date('Y') + 2;
        $v->data['real_year'] = $v->year($in, 'real_year', 1886, $maxYear);
        $v->data['casting_year'] = $v->year($in, 'casting_year', 1968, $maxYear);
        $v->data['collection_number'] = $v->text($in, 'collection_number', 20) ?: null;
        $v->data['color'] = $v->text($in, 'color', 60) ?: null;
        $v->data['rarity'] = $v->choice($in, 'rarity', Car::RARITIES);
        $v->data['item_condition'] = $v->choice($in, 'item_condition', Car::CONDITIONS);
        $v->data['acquired_at'] = $v->date($in, 'acquired_at');
        $v->data['notes'] = Str::cut(Str::clean($in['notes'] ?? '', true), 2000) ?: null;
        $v->data['is_favorite'] = self::truthy($in['is_favorite'] ?? '') ? 1 : 0;

        return $v;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public static function truthy(mixed $value): bool
    {
        return in_array(strtolower(Str::clean($value)), ['1', 'on', 'true', 'yes', 'si', 'sí', 'x'], true);
    }

    private function text(array $in, string $field, int $max, bool $required = false): string
    {
        $value = Str::clean($in[$field] ?? '');
        if ($required && $value === '') {
            $this->errors[$field] = 'validation.required';
        } elseif (Str::length($value) > $max) {
            $this->errors[$field] = 'validation.too_long';
        }

        return Str::cut($value, $max);
    }

    private function money(string $raw): ?float
    {
        if ($raw === '') {
            return null;
        }
        $normalized = str_replace(['$', ',', ' ', 'MXN', 'mxn'], '', $raw);
        if (!is_numeric($normalized) || (float) $normalized < 0 || (float) $normalized > 9_999_999) {
            $this->errors['cost_mxn'] = 'validation.money';
            return null;
        }

        return round((float) $normalized, 2);
    }

    private function year(array $in, string $field, int $min, int $max): ?int
    {
        $raw = Str::clean($in[$field] ?? '');
        if ($raw === '') {
            return null;
        }
        if (!ctype_digit($raw) || (int) $raw < $min || (int) $raw > $max) {
            $this->errors[$field] = 'validation.year';
            return null;
        }

        return (int) $raw;
    }

    private function choice(array $in, string $field, array $allowed): ?string
    {
        $raw = Str::clean($in[$field] ?? '');
        if ($raw === '') {
            return null;
        }
        if (!in_array($raw, $allowed, true)) {
            $this->errors[$field] = 'validation.choice';
            return null;
        }

        return $raw;
    }

    private function date(array $in, string $field): ?string
    {
        $raw = Str::clean($in[$field] ?? '');
        if ($raw === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        if ($date === false || $date->format('Y-m-d') !== $raw || $date > new \DateTimeImmutable('tomorrow')) {
            $this->errors[$field] = 'validation.date';
            return null;
        }

        return $raw;
    }
}
