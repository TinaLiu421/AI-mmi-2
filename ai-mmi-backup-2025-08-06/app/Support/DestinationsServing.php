<?php

namespace App\Support;

class DestinationsServing
{
    /**
     * Destination definition list.
     * id: new unique id used across the app
     * label: display text
     * legacy_ids: previous numeric ids stored in DB (from CountriesPhoneCodes or manual strings)
     * aliases: string variations to recognise previous free text entries
     * visa_country_id: existing visa country id to reuse flag/url metadata when available
     */
    private static $destinations = [
        [
            'id'              => 1,
            'label'           => 'Australia',
            'legacy_ids'      => [8],
            'aliases'         => ['australia'],
            'visa_country_id' => 8,
            'visa_country_label' => 'Australia',
        ],
        [
            'id'              => 2,
            'label'           => 'Canada',
            'legacy_ids'      => [30],
            'aliases'         => ['canada'],
            'visa_country_id' => 30,
            'visa_country_label' => 'Canada',
        ],
        [
            'id'              => 3,
            'label'           => 'China',
            'legacy_ids'      => [35],
            'aliases'         => ['china'],
            'visa_country_id' => 35,
            'visa_country_label' => 'China',
        ],
        [
            'id'              => 4,
            'label'           => 'Europe',
            'legacy_ids'      => [],
            'aliases'         => ['europe'],
            'visa_country_id' => null,
            'visa_country_label' => null,
        ],
        [
            'id'              => 5,
            'label'           => 'Hong Kong',
            'legacy_ids'      => [73],
            'aliases'         => ['hong kong', 'hongkong'],
            'visa_country_id' => 73,
            'visa_country_label' => 'Hong Kong',
        ],
        [
            'id'              => 6,
            'label'           => 'Japan',
            'legacy_ids'      => [84],
            'aliases'         => ['japan'],
            'visa_country_id' => 84,
            'visa_country_label' => 'Japan',
        ],
        [
            'id'              => 7,
            'label'           => 'Korea',
            'legacy_ids'      => [162],
            'aliases'         => ['korea', 'south korea', 'republic of korea'],
            'visa_country_id' => 162,
            'visa_country_label' => 'Korea',
        ],
        [
            'id'              => 8,
            'label'           => 'Malaysia',
            'legacy_ids'      => [103],
            'aliases'         => ['malaysia'],
            'visa_country_id' => 103,
            'visa_country_label' => 'Malaysia',
        ],
        [
            'id'              => 9,
            'label'           => 'New Zealand',
            'legacy_ids'      => [123],
            'aliases'         => ['new zealand'],
            'visa_country_id' => 123,
            'visa_country_label' => 'New Zealand',
        ],
        [
            'id'              => 10,
            'label'           => 'Taiwan',
            'legacy_ids'      => [171],
            'aliases'         => ['taiwan'],
            'visa_country_id' => 171,
            'visa_country_label' => 'Taiwan',
        ],
        [
            'id'              => 11,
            'label'           => 'Thailand',
            'legacy_ids'      => [174],
            'aliases'         => ['thailand'],
            'visa_country_id' => 174,
            'visa_country_label' => 'Thailand',
        ],
        [
            'id'              => 12,
            'label'           => 'Singapore',
            'legacy_ids'      => [156],
            'aliases'         => ['singapore'],
            'visa_country_id' => 156,
            'visa_country_label' => 'Singapore',
        ],
        [
            'id'              => 13,
            'label'           => 'UK',
            'legacy_ids'      => [186],
            'aliases'         => ['uk', 'united kingdom', 'great britain', 'britain', 'gb'],
            'visa_country_id' => 186,
            'visa_country_label' => 'United Kingdom',
            'flag_asset'      => 'asset/image/flags/destinations/uk.png',
        ],
        [
            'id'              => 14,
            'label'           => 'US',
            'legacy_ids'      => [187],
            'aliases'         => ['us', 'usa', 'united states', 'united states of america', 'america'],
            'visa_country_id' => 187,
            'visa_country_label' => 'United States',
        ],
        [
            'id'              => 15,
            'label'           => 'Others',
            'legacy_ids'      => [],
            'aliases'         => ['others', 'other'],
            'visa_country_id' => null,
            'visa_country_label' => null,
        ],
    ];

    public static function all(): array
    {
        return self::$destinations;
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::$destinations as $destination) {
            $options[(string) $destination['id']] = $destination['label'];
        }
        return $options;
    }

    public static function findById($id): ?array
    {
        $id = (int) $id;
        foreach (self::$destinations as $destination) {
            if ((int) $destination['id'] === $id) {
                return $destination;
            }
        }
        return null;
    }

    public static function resolveId($value): ?int
    {
        if (is_numeric($value)) {
            $intVal = (int) $value;
            foreach (self::$destinations as $destination) {
                if ((int) $destination['id'] === $intVal) {
                    return $destination['id'];
                }
                if (in_array($intVal, $destination['legacy_ids'], true)) {
                    return $destination['id'];
                }
            }
        }

        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        foreach (self::$destinations as $destination) {
            if ($normalized === strtolower($destination['label'])) {
                return $destination['id'];
            }
            if (!empty($destination['aliases'])) {
                foreach ($destination['aliases'] as $alias) {
                    if ($normalized === strtolower($alias)) {
                        return $destination['id'];
                    }
                }
            }
        }

        return null;
    }

    public static function normalizeSelections($values): array
    {
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $values = $decoded;
            } else {
                $values = array_map('trim', explode(',', $values));
            }
        }

        if (!is_array($values)) {
            $values = [];
        }

        $results = [];
        foreach ($values as $value) {
            $resolved = self::resolveId($value);
            if ($resolved !== null) {
                $results[] = (string) $resolved;
            }
        }

        return array_values(array_unique($results));
    }

    public static function fromStorage($value): array
    {
        return self::normalizeSelections($value);
    }

    public static function toStorage(array $ids): array
    {
        return self::normalizeSelections($ids);
    }

    public static function toLabels(array $ids): array
    {
        $labels = [];
        foreach ($ids as $id) {
            $destination = self::findById($id);
            if ($destination) {
                $labels[] = $destination['label'];
            }
        }
        return $labels;
    }

    public static function toVisaCountryIds(array $ids): array
    {
        $results = [];
        foreach ($ids as $id) {
            $destination = self::findById($id);
            if ($destination && !empty($destination['visa_country_id'])) {
                $results[] = (int) $destination['visa_country_id'];
            }
        }
        return array_values(array_unique($results));
    }
}
