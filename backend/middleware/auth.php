<?php
// =============================================
// EduSchedule Pro - Middleware Authentification
// Auteur : Krislarima
// =============================================

require_once __DIR__ . '/../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Clé secrète JWT (à garder confidentielle)
define('JWT_SECRET', 'eduschedule_pro_secret_key_2026_isge_burkina_faso_rst_development_web');
define('JWT_EXPIRATION', 86400); // 24 heures en secondes

// Générer un token JWT
function genererToken($utilisateur) {
    $payload = [
        'iss'   => 'eduschedule_pro',
        'iat'   => time(),
        'exp'   => time() + JWT_EXPIRATION,
        'id'    => $utilisateur['id'],
        'email' => $utilisateur['email'],
        'role'  => $utilisateur['role']
    ];
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

// Vérifier et décoder un token JWT
function verifierToken() {
    $headers = getallheaders();

    // Vérifier si le header Authorization existe
    if (!isset($headers['Authorization'])) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Token manquant"
        ]);
        exit();
    }

    // Extraire le token du header
    $token = str_replace('Bearer ', '', $headers['Authorization']);

    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Token invalide : " . $e->getMessage()
        ]);
        exit();
    }
}

// Vérifier le rôle de l'utilisateur
function verifierRole($roles_autorises) {
    $utilisateur = verifierToken();
    if (!in_array($utilisateur['role'], $roles_autorises)) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "message" => "Accès refusé - rôle insuffisant"
        ]);
        exit();
    }
    return $utilisateur;
}
?>