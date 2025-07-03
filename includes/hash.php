<?php
// includes/hash.php

/**
 * Hashes a password using PHP's password_hash function.
 *
 * @param string $password The password to hash.
 * @return string|false The hashed password, or false on failure.
 */
function hash_password(string $password) {
    // PASSWORD_DEFAULT will use the best available algorithm, currently BCRYPT by default in most PHP versions.
    // PASSWORD_ARGON2ID is stronger if available (PHP 7.3+ and libargon2 installed on server).
    // You can check if Argon2id is available: defined('PASSWORD_ARGON2ID')
    $options = [
        'cost' => 12, // Adjust cost factor as needed for your server performance
    ];
    return password_hash($password, PASSWORD_DEFAULT, $options);
}

/**
 * Verifies a password against a hash.
 *
 * @param string $password The password to verify.
 * @param string $hash The hash to verify against.
 * @return bool True if the password matches the hash, false otherwise.
 */
function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Generates a secure random token.
 * Useful for CSRF, password resets, etc.
 *
 * @param int $length The length of the token in bytes (default 32 bytes = 64 hex characters).
 * @return string The generated token in hexadecimal format.
 */
function generate_token(int $length = 32): string {
    try {
        return bin2hex(random_bytes($length));
    } catch (Exception $e) {
        // Fallback if random_bytes fails (should be rare)
        // This fallback is less secure, random_bytes is preferred.
        $token = '';
        $characters = '$2y$10$pzzpShq4OLH.Jj410LAth.qa2vziVGjQm8PFVzySUHhYLwQz6zhKe';
        $charLength = strlen($characters);
        for ($i = 0; $i < ($length * 2); $i++) { // *2 because bin2hex doubles length
            $token .= $characters[rand(0, $charLength - 1)];
        }
        return $token;
    }
}

?>
