<?php

namespace App\Service;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

/**
 * Lightweight single-field decryptor that works with raw DB values
 * (arrays from DBAL queries) without requiring Doctrine entity hydration.
 *
 * The doctrine-encrypt-bundle decrypts ALL @Encrypted fields via postLoad,
 * even fields the template never uses. This service decrypts only the
 * fields you explicitly pass to it.
 */
class FieldDecryptor
{
    private const ENC_SUFFIX = '<ENC>';
    private Key $key;

    public function __construct(string $projectDir)
    {
        $keyPath = $projectDir . '/.Defuse.key';
        $this->key = Key::loadFromAsciiSafeString(trim(file_get_contents($keyPath)));
    }

    public function decrypt(?string $value): ?string
    {
        if ($value === null || !str_ends_with($value, self::ENC_SUFFIX)) {
            return $value;
        }
        $ciphertext = substr($value, 0, -strlen(self::ENC_SUFFIX));
        try {
            return Crypto::decrypt($ciphertext, $this->key);
        } catch (\Exception $e) {
            return '[decrypt error]';
        }
    }

    /**
     * Decrypt a specific key in each row of an array of associative arrays.
     */
    public function decryptColumn(array &$rows, string $column): void
    {
        foreach ($rows as &$row) {
            if (isset($row[$column])) {
                $row[$column] = $this->decrypt($row[$column]);
            }
        }
    }
}
