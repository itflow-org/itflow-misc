<?php

/*
 * ###############################################################################################################
 *  ITFlow - Sample encrypted login (username/password) decryption code/tool
 *    ITFlow is distributed "as is" under the GPL License, WITHOUT WARRANTY OF ANY KIND.
 * ###############################################################################################################
 */

// Specify your Master Encryption key here
//  e.g. 'hITarkbAZgdW3WXz' - from /settings_backup.php
$site_encryption_master_key = '';

// Specify your encrypted username/password here from the login_username or login_password field
//  e.g. "pI21VrOTAUnzEbwdozNOndql5BzRN08LHYvA7w=="
$full_ciphertext = '';


// Split ciphertext into IV and Ciphertext
$iv =  substr($full_ciphertext, 0, 16);
$ciphertext = $salt = substr($full_ciphertext, 16);

// Decrypt
$decrypted_text = openssl_decrypt($ciphertext, 'aes-128-cbc', $site_encryption_master_key, 0, $iv);

// Show
echo "$decrypted_text";
