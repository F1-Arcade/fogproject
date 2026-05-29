<?php
/**
 * Venue config encryption helper.
 *
 * Provides AES-256-CBC encryption for password-type config values.
 *
 * PHP version 5
 *
 * @category VenueConfigCrypto
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
/**
 * Venue config encryption helper.
 *
 * @category VenueConfigCrypto
 * @package  FOGProject
 * @author   Gavin Williams (https://github.com/gavinwilliams)
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://github.com/F1-Arcade/fogproject
 */
class VenueConfigCrypto
{
    /**
     * Prefix to identify encrypted values.
     */
    const ENC_PREFIX = '$ENC$';

    /**
     * Cipher algorithm.
     */
    const CIPHER = 'aes-256-cbc';

    /**
     * Derive encryption key from FOG's database password + static salt.
     *
     * @return string 32-byte raw key
     */
    private static function _deriveKey()
    {
        $secret = defined('DATABASE_PASSWORD') ? DATABASE_PASSWORD : 'fog-default';
        $salt = 'venue-config-encryption-salt-v1';
        return hash_hkdf('sha256', $secret, 32, 'venue-config', $salt);
    }

    /**
     * Encrypt a plain-text value.
     *
     * @param string $plaintext The value to encrypt
     *
     * @return string Encrypted value with prefix, or original if empty
     */
    public static function encrypt($plaintext)
    {
        if ($plaintext === '' || $plaintext === null) {
            return '';
        }
        // Don't double-encrypt
        if (self::isEncrypted($plaintext)) {
            return $plaintext;
        }
        $key = self::_deriveKey();
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivLen);
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        if ($ciphertext === false) {
            return $plaintext;
        }
        return self::ENC_PREFIX . base64_encode($iv . $ciphertext);
    }

    /**
     * Decrypt an encrypted value.
     *
     * @param string $encrypted The encrypted value (with prefix)
     *
     * @return string Decrypted plain text, or original if not encrypted
     */
    public static function decrypt($encrypted)
    {
        if (!self::isEncrypted($encrypted)) {
            return $encrypted;
        }
        $payload = base64_decode(substr($encrypted, strlen(self::ENC_PREFIX)));
        if ($payload === false) {
            return '';
        }
        $key = self::_deriveKey();
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        if (strlen($payload) < $ivLen) {
            return '';
        }
        $iv = substr($payload, 0, $ivLen);
        $ciphertext = substr($payload, $ivLen);
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        if ($plaintext === false) {
            return '';
        }
        return $plaintext;
    }

    /**
     * Check if a value is already encrypted.
     *
     * @param string $value The value to check
     *
     * @return bool
     */
    public static function isEncrypted($value)
    {
        return is_string($value)
            && strpos($value, self::ENC_PREFIX) === 0;
    }
}
