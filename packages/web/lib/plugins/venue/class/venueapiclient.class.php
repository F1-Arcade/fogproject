<?php
/**
 * Venue API client.
 *
 * Fetches venue data from the F1 Arcade legacy venue APIs and
 * encapsulates region validation plus the dev-region allowlist.
 *
 * PHP version 5
 *
 * @category VenueApiClient
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Venue API client.
 *
 * @category VenueApiClient
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class VenueApiClient
{
    /**
     * Valid region codes.
     */
    const VALID_REGIONS = ['uk', 'us', 'uk-dev', 'us-dev'];

    /**
     * Region code to API base URL.
     */
    const REGION_URLS = [
        'uk'     => 'https://api-legacy.uk.f1arcade.com/venues',
        'us'     => 'https://api-legacy.us.f1arcade.com/venues',
        'uk-dev' => 'https://api-legacy.uk.development.f1arcade.com/venues',
        'us-dev' => 'https://api-legacy.us.development.f1arcade.com/venues',
    ];

    /**
     * Dev regions only show these venue references in the picker.
     */
    const DEV_VENUE_ALLOWLIST = ['chicago-hq', 'tileyard', 'dev-playpen'];

    /**
     * HTTP request timeout in seconds.
     */
    const HTTP_TIMEOUT = 10;

    /**
     * Whether the given region code is a recognised region.
     *
     * @param string $region Region code.
     *
     * @return bool
     */
    public static function isValidRegion(string $region): bool
    {
        return in_array($region, self::VALID_REGIONS, true);
    }

    /**
     * Whether the venue reference should be visible in the picker
     * for the given region. Dev regions apply the allowlist.
     *
     * @param string $region    Region code.
     * @param string $venueRef  Venue reference (slug).
     *
     * @return bool
     */
    public static function isVenueVisible(string $region, string $venueRef): bool
    {
        if (!self::isValidRegion($region)) {
            return false;
        }
        if ($region === 'uk-dev' || $region === 'us-dev') {
            return in_array($venueRef, self::DEV_VENUE_ALLOWLIST, true);
        }
        return true;
    }

    /**
     * Fetch the list of venues for the given region.
     *
     * @param string $region Region code.
     *
     * @throws VenueException On invalid region, network, HTTP or JSON error.
     *
     * @return array Decoded venue list.
     */
    public static function fetchVenues(string $region): array
    {
        if (!isset(self::REGION_URLS[$region])) {
            throw new VenueException(
                sprintf(_('Invalid region: %s'), $region)
            );
        }
        $url = self::REGION_URLS[$region];
        $ch = curl_init($url);
        if ($ch === false) {
            throw new VenueException(
                _('Failed to initialise HTTP client')
            );
        }
        curl_setopt_array(
            $ch,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
                CURLOPT_USERAGENT      => 'FOG-Venue-Plugin/1.0',
            ]
        );
        $body = curl_exec($ch);
        if ($body === false || curl_errno($ch) !== 0) {
            $err = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new VenueException(
                sprintf(
                    _('Venue API request failed: %s (code %d)'),
                    $err,
                    $errno
                )
            );
        }
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200) {
            throw new VenueException(
                sprintf(
                    _('Venue API returned HTTP %d'),
                    $httpCode
                )
            );
        }
        $result = json_decode($body, true);
        if (!is_array($result)) {
            throw new VenueException(
                _('Venue API returned invalid JSON')
            );
        }
        // The F1 Arcade API wraps the venue list in a {"data": [...], "meta": {...}}
        // envelope. Unwrap so callers get a plain list of venues. Fall back to the
        // raw payload if the envelope shape ever changes (e.g. bare array).
        if (isset($result['data']) && is_array($result['data'])) {
            return $result['data'];
        }
        return $result;
    }

    /**
     * Find a single venue in a region by its reference ID.
     *
     * @param string $region   Region code.
     * @param string $venueRef Venue reference (MongoDB ObjectId string).
     *
     * @throws VenueException If the region is invalid or venue not found.
     *
     * @return array The single venue's decoded data.
     */
    public static function findVenue(string $region, string $venueRef): array
    {
        $venues = self::fetchVenues($region);
        foreach ($venues as $venue) {
            if (is_array($venue)
                && isset($venue['id'])
                && $venue['id'] === $venueRef
            ) {
                return $venue;
            }
        }
        throw new VenueException(
            sprintf(
                _('Venue %s not found in region %s'),
                $venueRef,
                $region
            )
        );
    }
}
