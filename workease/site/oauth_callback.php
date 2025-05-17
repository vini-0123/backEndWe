<?php
// oauth_callback.php
session_start();
require 'vendor/autoload.php'; // Composer's autoloader
include_once './factory/conexao.php'; // Your database connection

// Use the SAME configuration as in social_auth.php
define('CALLBACK_URL', 'http://localhost/YOUR_PROJECT_FOLDER/oauth_callback.php'); // <<< CHANGE THIS
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID'); // <<< CHANGE THIS
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET'); // <<< CHANGE THIS
define('LINKEDIN_CLIENT_ID', 'YOUR_LINKEDIN_CLIENT_ID'); // <<< CHANGE THIS
define('LINKEDIN_CLIENT_SECRET', 'YOUR_LINKEDIN_CLIENT_SECRET'); // <<< CHANGE THIS

$config = [
    'callback' => CALLBACK_URL,
    'providers' => [
        'Google' => [
            'enabled' => true,
            'keys' => ['id' => GOOGLE_CLIENT_ID, 'secret' => GOOGLE_CLIENT_SECRET],
        ],
        'LinkedIn' => [
            'enabled' => true,
            'keys' => ['id' => LINKEDIN_CLIENT_ID, 'secret' => LINKEDIN_CLIENT_SECRET],
            // It's good to specify API version for LinkedIn if HybridAuth doesn't default well
            // 'version' => '2.0', // Example
        ],
    ],
    // 'debug_mode' => true, // Enable for detailed logs during development
    // 'debug_file' => __DIR__ . '/hybridauth.log', // Ensure this file is writable
];

$adapter = null;
$providerName = $_SESSION['oauth_provider'] ?? null; // Get provider from session

try {
    if (!$providerName) {
        throw new Exception("Nome do provedor OAuth não encontrado na sessão.");
    }

    $hybridauth = new Hybridauth\Hybridauth($config);

    // After the user is redirected back, HybridAuth needs to process the request
    // using the specific provider adapter that initiated the flow.
    $adapter = $hybridauth->authenticate($providerName); // This processes the callback for the given provider

    // If $adapter->authenticate() is successful, the user is authenticated with the provider.
    // Now, get the user profile.
    $userProfile = $adapter->getUserProfile();

    // The providerName is already known from the session or $adapter->getId() can be used now.
    // $providerName = $adapter->getId(); // This should work now if $adapter is valid

    // --- Extract User Data ---
    $socialId = $userProfile->identifier;
    $email = $userProfile->email;
    $firstName = $userProfile->firstName;
    $lastName = $userProfile->lastName;
    $displayName = $userProfile->displayName;
    $photoURL = $userProfile->photoURL;

    $nomeCompleto = $displayName ?: trim($firstName . ' ' . $lastName);
    if (empty($nomeCompleto) && !empty($email)) {
        $nomeCompleto = explode('@', $email)[0];
    } elseif (empty($nomeCompleto)) {
        $nomeCompleto = "Usuário_" . substr(uniqid(), -5);
    }

    if (empty($email)) {
        throw new Exception("Não foi possível obter o endereço de e-mail de {$providerName}.");
    }

    // --- Database Logic (remains largely the same) ---
    $stmt = $mysqli->prepare("SELECT id, nome, nivel_acesso, ativo, social_provider, social_id FROM usuarios WHERE email = ?");
    if (!$stmt) { throw new Exception("DB Prepare Error (SELECT): " . $mysqli->error); }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario_existente = $result->fetch_assoc();
    $stmt->close();

    $userId = null;
    $userLevel = 'user';

    if ($usuario_existente) {
        $userId = $usuario_existente['id'];
        $userLevel = $usuario_existente['nivel_acesso'];

        if (empty($usuario_existente['social_provider']) || $usuario_existente['social_id'] !== $socialId || $usuario_existente['social_provider'] !== $providerName) {
            $updateStmt = $mysqli->prepare("UPDATE usuarios SET social_provider = ?, social_id = ?, nome = ?, foto_url = ? WHERE id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("ssssi", $providerName, $socialId, $nomeCompleto, $photoURL, $userId);
                if(!$updateStmt->execute()){
                     error_log("Error updating social info for existing user (ID: {$userId}): " . $updateStmt->error);
                }
                $updateStmt->close();
            } else {
                 error_log("DB Prepare Error (UPDATE existing user social): " . $mysqli->error);
            }
        }
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_nome'] = $nomeCompleto;
        $_SESSION['nivel_acesso'] = $userLevel;
        $_SESSION['login_provider'] = $providerName;
        $_SESSION['login_time'] = time();

    } else {
        $stmt_insert = $mysqli->prepare(
            "INSERT INTO usuarios (nome, email, senha, nivel_acesso, ativo, data_cadastro, social_provider, social_id, foto_url)
             VALUES (?, ?, NULL, ?, 1, NOW(), ?, ?, ?)"
        );
        if (!$stmt_insert) { throw new Exception("DB Prepare Error (INSERT new social user): " . $mysqli->error); }
        $stmt_insert->bind_param("ssssss", $nomeCompleto, $email, $userLevel, $providerName, $socialId, $photoURL);

        if ($stmt_insert->execute()) {
            $userId = $stmt_insert->insert_id;
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_nome'] = $nomeCompleto;
            $_SESSION['nivel_acesso'] = $userLevel;
            $_SESSION['login_provider'] = $providerName;
            $_SESSION['login_time'] = time();
        } else {
            throw new Exception("Erro ao registrar novo usuário via {$providerName}: " . $stmt_insert->error);
        }
        $stmt_insert->close();
    }

    unset($_SESSION['oauth_provider']); // Clean up

    header('Location: ../site/index.php');
    exit;

} catch (\Exception $e) {
    error_log("OAuth Callback Error (" . ($providerName ?? 'Unknown') . "): " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    $_SESSION['oauth_error'] = "Falha na autenticação com ".htmlspecialchars($providerName ?? 'provedor social').". Detalhes: " . htmlspecialchars(substr($e->getMessage(),0, 250));
    unset($_SESSION['oauth_provider']); // Clean up session
    header('Location: cadastro.php');
    exit;
} finally {
    if ($adapter && $adapter->isConnected()) {
        $adapter->disconnect();
    }
}
?>