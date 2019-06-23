<?php

/**
 * @todo catch errors and add comments.
 */

class RSAPassword
{
    const MAX_AGE = 86400;

    private function __construct()
    {
        // nothing
    }

    public static function decrypt($base64_encoded_encrypted_data)
    {
        $instance = new RSAPassword();
        $details = $instance->readFromStore();
        $private_key = $details['private_key'];
        $keybits = $details['keybits'];
        $chunk_size = $keybits + 7 >> 3;
        $encrypted_data = base64_decode($base64_encoded_encrypted_data);

        $urlencoded_decrypted = '';

        do {
            if (strlen($encrypted_data) > $chunk_size) {
                $encrypted_chunk = substr($encrypted_data, 0, $chunk_size);
                $encrypted_data = substr($encrypted_data, $chunk_size);
            } else {
                $encrypted_chunk = $encrypted_data;
                $encrypted_data = NULL;
            }

            openssl_private_decrypt($encrypted_chunk, $decrypted_chunk, $private_key);

            $urlencoded_decrypted = $urlencoded_decrypted . $decrypted_chunk;
        } while (!is_null($encrypted_data));

        return urldecode($urlencoded_decrypted);
    }

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

    private function generate()
    {
        $config = array(
            'digest_alg' => 'sha512',
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        );

        $res = openssl_pkey_new($config);

        openssl_pkey_export($res, $private_key);

        $details = openssl_pkey_get_details($res);

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
