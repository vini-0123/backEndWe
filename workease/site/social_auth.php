<?php
// social_auth.php
session_start();
require 'vendor/autoload.php'; // Composer's autoloader

// !!! IMPORTANT: STORE KEYS SECURELY (e.g., environment variables, config file outside web root) !!!
// Replace placeholders with your actual credentials and callback URL
// For local development, ensure this matches what's in Google/LinkedIn dev consoles
define('CALLBACK_URL', 'http://localhost/YOUR_PROJECT_FOLDER/oauth_callback.php'); // <<< CHANGE THIS
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID'); // <<< CHANGE THIS
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET'); // <<< CHANGE THIS
define('LINKEDIN_CLIENT_ID', 'YOUR_LINKEDIN_CLIENT_ID'); // <<< CHANGE THIS
define('LINKEDIN_CLIENT_SECRET', 'YOUR_LINKEDIN_CLIENT_SECRET'); // <<< CHANGE THIS

// Configuration array for HybridAuth
$config = [
    'callback' => CALLBACK_URL,
    'providers' => [
        'Google' => [
            'enabled' => true,
            'keys' => ['id' => GOOGLE_CLIENT_ID, 'secret' => GOOGLE_CLIENT_SECRET],
            'scope' => 'profile email', // Request basic profile and email
        ],
        'LinkedIn' => [
            'enabled' => true,
            'keys' => ['id' => LINKEDIN_CLIENT_ID, 'secret' => LINKEDIN_CLIENT_SECRET],
            'scope' => 'r_liteprofile r_emailaddress', // Common scopes for LinkedIn v2
             // For LinkedIn v2, HybridAuth might need 'version' => '2.0' and correct field mapping if default doesn't work
        ],
    ],
    // Optional: Debugging (remove or set to false in production)
    // 'debug_mode' => true,
    // 'debug_file' => __DIR__ . '/hybridauth.log', // Ensure this file is writable
];

try {
    $hybridauth = new Hybridauth\Hybridauth($config);

    $providerName = filter_input(INPUT_GET, 'provider');

    if (!$providerName) {
        throw new Exception("Nenhum provedor social especificado.");
    }

    if (!isset($config['providers'][$providerName]) || !$config['providers'][$providerName]['enabled']) {
         throw new Exception("Provedor social '{$providerName}' não está habilitado ou configurado.");
    }

    // Store the provider in the session to know which one to process in the callback
    $_SESSION['oauth_provider'] = $providerName;

    // Authenticate the user with the provider (this will redirect)
    $adapter = $hybridauth->authenticate($providerName);

    // Get user profile
    $userProfile = $adapter->getUserProfile();
    
    // Debug line to check profile data
    error_log('User Profile: ' . print_r($userProfile, true));
    
    // Set session variables with checks
    $_SESSION['user_name'] = !empty($userProfile->displayName) ? $userProfile->displayName : 'Usuário';
    $_SESSION['user_email'] = !empty($userProfile->email) ? $userProfile->email : '';
    $_SESSION['logged_in'] = true;
    
    // Verify session was set
    error_log('Session after login: ' . print_r($_SESSION, true));
    
    // Redirect to index page after successful login
    header('Location: ../site/index.php');
    exit;

} catch (\Exception $e) {
    error_log("OAuth Init Error (" . ($providerName ?? 'Unknown Provider') . "): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    $_SESSION['oauth_error'] = "Erro ao iniciar autenticação com ".htmlspecialchars($providerName).". Detalhes: " . htmlspecialchars(substr($e->getMessage(), 0, 200)); // Keep error message brief
    header('Location: cadastro.php'); // Redirect back to registration with error
    exit;
}
?>