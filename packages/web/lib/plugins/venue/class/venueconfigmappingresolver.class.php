<?php
/**
 * Venue config mapping resolver.
 *
 * Resolves dotted API JSON paths (e.g. "address.postcode") against a
 * decoded venue payload and normalises leaf values into strings
 * suitable for storage as venue config values.
 *
 * PHP version 5
 *
 * @category VenueConfigMappingResolver
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Venue config mapping resolver.
 *
 * @category VenueConfigMappingResolver
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class VenueConfigMappingResolver
{
    /**
     * Canonical list of API JSON paths available in the mapping dropdown.
     *
     * @return array
     */
    public static function availablePaths(): array
    {
        return [
            'id',
            'name',
            'reference',
            'description',
            'defaultLocale',
            'timezone',
            'currency',
            'country',
            'email',
            'phone',
            'operator',
            'collinsId',
            'mobileOrderingURL',
            'bookingsLiveDate',
            'address.address1',
            'address.address2',
            'address.address3',
            'address.city',
            'address.town',
            'address.postcode',
            'address.country',
            'ageRestrictions.min_age',
            'ageRestrictions.restricted_age',
            'ageRestrictions.restricted_from_time',
            'simulators',
            'tracks',
            'supportedLocales',
            'priority',
        ];
    }

    /**
     * Resolve a dotted path against the supplied decoded payload.
     *
     * Returns null if the path is empty, the data is not an array,
     * or any segment of the path is missing or traverses a non-array.
     * Leaf values are normalised:
     *  - arrays become JSON-encoded strings
     *  - booleans become '1' or '0'
     *  - nulls stay null
     *  - scalars are returned as-is
     *
     * @param array  $data Decoded venue payload.
     * @param string $path Dotted JSON path.
     *
     * @return mixed
     */
    public static function resolve(array $data, string $path)
    {
        if ($path === '') {
            return null;
        }
        $parts = explode('.', $path);
        $cursor = $data;
        foreach ($parts as $part) {
            if (!is_array($cursor) || !array_key_exists($part, $cursor)) {
                return null;
            }
            $cursor = $cursor[$part];
        }
        if (is_array($cursor)) {
            return json_encode($cursor);
        }
        if (is_bool($cursor)) {
            return $cursor ? '1' : '0';
        }
        if ($cursor === null) {
            return null;
        }
        return $cursor;
    }
}
