<?php

class RSAPassword
{
    /**
     * The max age of the RSA key (in seconds).
     */
    const MAX_AGE = 86400;

    /**
     * The private constructor.
     */
    private function __construct()
    {
        // nothing
    }

    /**
     * Decrypt the RSA encrypted data.
     *
     * @param string $encrypted The data to decrypt, which is urlencoded and RSA encrypted and base64 encoded.
     * @return string Returns the decrypted text on success.
     * @throws Exception Throws exceptions on error.
     */
    public static function decrypt($encrypted)
    {
        $instance = new RSAPassword();
        $details = $instance->readFromStore();
        $private_key = $details['private_key'];
        $keybits = $details['keybits'];
        $chunk_size = $keybits + 7 >> 3;
        $encrypted_data = base64_decode($encrypted);

        if ($encrypted_data === false) {
            throw new Exception('Failed to base64 decode the encrypted data!', 1);
        }

        $urlencoded_decrypted = '';

        do {
            if (strlen($encrypted_data) > $chunk_size) {
                $encrypted_chunk = substr($encrypted_data, 0, $chunk_size);
                $encrypted_data = substr($encrypted_data, $chunk_size);
            } else {
                $encrypted_chunk = $encrypted_data;
                $encrypted_data = NULL;
            }

            $result = openssl_private_decrypt($encrypted_chunk, $decrypted_chunk, $private_key);

            if ($result === false) {
                throw new Exception('Failed to decrypt the data!', 2);
            }

            $urlencoded_decrypted = $urlencoded_decrypted . $decrypted_chunk;
        } while (!is_null($encrypted_data));

        return urldecode($urlencoded_decrypted);
    }

    /**
     * Get the public key details.
     *
     * @throws Throws exceptions on generating RSA key failed.
     * @return array Returns the key details.
     */
    public static function getPublicDetails()
    {
        $instance = new RSAPassword();
        $details = $instance->readFromStore();

        return array(
            'keybits' => $details['keybits'],
            'modulus' => $details['modulus'],
            'exponent' => $details['exponent']
        );
    }

    /**
     * Read the RSA key from store. If it does not exist or is expired, a new RSA key will generated.
     *
     * @throws Throws exceptions on generating RSA key failed.
     * @return array Returns the RSA key info.
     */
    private function readFromStore()
    {
        $store = store('security/rsa-key-details');

        $details = $store->lock()->get('details', null);

        if (is_null($details)) {
            $details = $this->generate();
            $store->set('details', $details);
        } else {
            $time_passed = time() - $details['created_at'];
            if ($time_passed > self::MAX_AGE) {
                $details = $this->generate();
                $store->set('details', $details);
            }
        }

        $store->unlock();

        return $details;
    }

    /**
     * Generate a RSA key.
     *
     * @throws Throws exceptions on error.
     * @return array Returns the RSA key info.
     */
    private function generate()
    {
        $config = array(
            'digest_alg' => 'sha512',
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        );

        $res = openssl_pkey_new($config);

        if ($res === false) {
            throw new Exception('Failed to generate the RSA key.', 3);
        }

        $state = openssl_pkey_export($res, $private_key);

        if ($state === false) {
            throw new Exception('Failed to export the private key!', 4);
        }

        $details = openssl_pkey_get_details($res);

        if ($details === false) {
            throw new Exception('Failed to get the key details!', 5);
        }

        $key_details = array(
            'created_at' => time(),
            'private_key' => $private_key,
            'public_key' => $details['key'],
            'keybits' => $details['bits'],
            'exponent' => base64_encode($details['rsa']['e']),
            'modulus' => base64_encode($details['rsa']['n'])
        );

        return $key_details;
    }
}
