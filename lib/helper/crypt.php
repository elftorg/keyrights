<?php

namespace Drdroid\Keyrights\Helper;

use Bitrix\Main\Config\Option;

/**
 * Server-side envelope encryption for the client encrypted payload.
 *
 * v2 is AES-256-GCM and authenticates the ciphertext. The old value format
 * (`base64(blowfish_ecb)__base64(iv)`) is deliberately kept as a read-only
 * legacy format so existing records remain decryptable during migration.
 */
class Crypt
{
    private const FORMAT = 'v2';
    private const AAD = 'drdroid.keyrights/server-envelope/v2';
    private const NONCE_LENGTH = 12;
    private const TAG_LENGTH = 16;

    private $key;

    public function __construct()
    {
        $keySource = (string)Option::get('drdroid.keyrights', 'serverKeySource', 'option');
        $this->key = self::loadSecret('serverPassphrase', 'serverKeySource', 'server');
        if ($this->key === '') {
            throw new \RuntimeException('KeyRights server passphrase is not configured for source: ' . $keySource);
        }
    }

    public static function getClientPassphrase()
    {
        return self::loadSecret('clientPassphrase', 'clientKeySource', 'client');
    }

    public static function encrypt($string)
    {
        return (new self())->cryptString((string)$string);
    }

    public static function decrypt($string)
    {
        return (new self())->decryptString($string);
    }

    public static function isLegacy($string)
    {
        return is_string($string)
            && strpos($string, self::FORMAT . ':') !== 0
            && substr_count($string, '__') === 1;
    }

    /** Re-encrypt one legacy envelope without ever exposing plaintext to the caller. */
    public static function migrate($string)
    {
        $instance = new self();
        if (!self::isLegacy($string)) {
            return (string)$string;
        }

        return $instance->cryptString($instance->decryptLegacy($string));
    }

    public function cryptString($string)
    {
        $nonce = random_bytes(self::NONCE_LENGTH);
        $tag = '';
        $ciphertext = openssl_encrypt(
            (string)$string,
            'aes-256-gcm',
            $this->deriveKey(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            self::AAD,
            self::TAG_LENGTH
        );

        if ($ciphertext === false || strlen($tag) !== self::TAG_LENGTH) {
            throw new \RuntimeException('AES-256-GCM encryption failed');
        }

        return implode(':', [
            self::FORMAT,
            $this->encode($nonce),
            $this->encode($tag),
            $this->encode($ciphertext),
        ]);
    }

    public function decryptString($string)
    {
        if (!is_string($string) || $string === '') {
            return '';
        }

        if (strpos($string, self::FORMAT . ':') === 0) {
            return $this->decryptV2($string);
        }

        return $this->decryptLegacy($string);
    }

    private function decryptV2($string)
    {
        $parts = explode(':', $string, 4);
        if (count($parts) !== 4 || $parts[0] !== self::FORMAT) {
            throw new \RuntimeException('Invalid KeyRights v2 ciphertext');
        }

        $nonce = $this->decode($parts[1]);
        $tag = $this->decode($parts[2]);
        $ciphertext = $this->decode($parts[3]);
        if ($nonce === false || strlen($nonce) !== self::NONCE_LENGTH
            || $tag === false || strlen($tag) !== self::TAG_LENGTH
            || $ciphertext === false) {
            throw new \RuntimeException('Invalid KeyRights v2 ciphertext payload');
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->deriveKey(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            self::AAD
        );
        if ($plaintext === false) {
            throw new \RuntimeException('KeyRights ciphertext authentication failed');
        }

        return $plaintext;
    }

    /**
     * Legacy Blowfish ECB decryptor. No legacy value is rewritten unless this
     * method completes successfully, which makes the migration retry-safe.
     */
    private function decryptLegacy($string)
    {
        if (!self::isLegacy($string)) {
            throw new \RuntimeException('Unknown KeyRights ciphertext format');
        }

        [$cryptedStr, $ivStr] = explode('__', $string, 2);
        $crypted = base64_decode($cryptedStr, true);
        $iv = base64_decode($ivStr, true);
        if ($crypted === false || $iv === false || $crypted === '') {
            throw new \RuntimeException('Invalid legacy KeyRights ciphertext');
        }

        // Prefer the bundled pure-PHP legacy implementation. ext-mcrypt is
        // deprecated on PHP 8.x and can emit warnings into an API response.
        if (class_exists('phpseclib3\\Crypt\\Blowfish')) {
            $cipher = new \phpseclib3\Crypt\Blowfish('ecb');
            $cipher->setKey($this->key);
            $cipher->disablePadding();
            $decrypted = $cipher->decrypt($crypted);
        } elseif ($this->supportsOpenSslBlowfish()) {
            $decrypted = openssl_decrypt(
                $crypted,
                'BF-ECB',
                $this->key,
                OPENSSL_RAW_DATA | OPENSSL_NO_PADDING
            );
        } else {
            throw new \RuntimeException(
                'Legacy Blowfish decryptor is unavailable; install phpseclib'
            );
        }

        if (!is_string($decrypted)) {
            throw new \RuntimeException('Legacy KeyRights decryption failed');
        }

        return rtrim($decrypted, "\0 \t\r\n");
    }

    private function deriveKey()
    {
        return hash('sha256', $this->key, true);
    }

    private static function loadSecret($optionName, $sourceOptionName, $purpose)
    {
        $source = (string)Option::get('drdroid.keyrights', $sourceOptionName, 'option');
        if ($source === 'environment' && $purpose === 'server') {
            return (string)getenv('KEYRIGHTS_SERVER_KEY');
        }
        if ($source !== 'bitrix-crypto') {
            return (string)Option::get('drdroid.keyrights', $optionName, '');
        }

        $encoded = (string)Option::get('drdroid.keyrights', $optionName . 'Encrypted', '');
        $encrypted = base64_decode($encoded, true);
        if (!class_exists('\Bitrix\Main\ORM\Fields\CryptoField')
            || !method_exists('\Bitrix\Main\ORM\Fields\CryptoField', 'getDefaultKey')
            || !class_exists('\Bitrix\Main\Security\Cipher')
        ) {
            throw new \RuntimeException('Bitrix crypto key is unavailable for KeyRights secret storage');
        }
        try {
            $cryptoKey = \Bitrix\Main\ORM\Fields\CryptoField::getDefaultKey();
            if ($encrypted === false || !is_string($cryptoKey) || $cryptoKey === '') {
                throw new \RuntimeException('Bitrix crypto key is unavailable for KeyRights secret storage');
            }
            return (new \Bitrix\Main\Security\Cipher())->decrypt(
                $encrypted,
                $cryptoKey . '|drdroid.keyrights|' . $purpose
            );
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Could not decrypt KeyRights secret storage', 0, $exception);
        }
    }

    private function supportsOpenSslBlowfish()
    {
        return function_exists('openssl_get_cipher_methods')
            && in_array('bf-ecb', array_map('strtolower', openssl_get_cipher_methods()), true);
    }

    private function encode($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function decode($value)
    {
        if (!is_string($value) || !preg_match('/^[A-Za-z0-9_-]*$/', $value)) {
            return false;
        }
        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
