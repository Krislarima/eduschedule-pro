<?php
// =============================================
// EduSchedule Pro - API Authentification
// =============================================

require_once __DIR__ . '/../../backend/config/cors.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/middleware/auth.php';

$methode = $_SERVER['REQUEST_METHOD'];

switch ($methode) {

    // -----------------------------------------
    // POST /api/auth/login
    // -----------------------------------------
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        // Vérifier que les champs sont présents
        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Email et mot de passe requis"
            ]);
            exit();
        }

        // Connexion à la base de données
        $db = new Database();
        $conn = $db->getConnection();

        // Chercher l'utilisateur par email
        $stmt = $conn->prepare(
            "SELECT * FROM utilisateurs WHERE email = :email AND actif = 1"
        );
        $stmt->execute([':email' => $data['email']]);
        $utilisateur = $stmt->fetch();

        // Vérifier si l'utilisateur existe
        if (!$utilisateur) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "message" => "Email ou mot de passe incorrect"
            ]);
            exit();
        }

        // Vérifier le mot de passe
        if (!password_verify($data['password'], $utilisateur['mot_de_passe_hash'])) {
            http_response_code(401);
            echo json_encode([
                "success" => false,
                "message" => "Email ou mot de passe incorrect"
            ]);
            exit();
        }

        // Générer le token JWT
        $token = genererToken($utilisateur);

        // Logger l'activité
        $log = $conn->prepare(
            "INSERT INTO logs_activite (id_utilisateur, action, ip) 
             VALUES (:id, 'login', :ip)"
        );
        $log->execute([
            ':id' => $utilisateur['id'],
            ':ip' => $_SERVER['REMOTE_ADDR']
        ]);

        // Retourner le token et les infos utilisateur
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Connexion réussie",
            "token"   => $token,
            "utilisateur" => [
                "id"    => $utilisateur['id'],
                "email" => $utilisateur['email'],
                "role"  => $utilisateur['role']
            ]
        ]);
        break;

    // -----------------------------------------
    // DELETE /api/auth/logout
    // -----------------------------------------
    case 'DELETE':
        $utilisateur = verifierToken();

        // Logger la déconnexion
        $db = new Database();
        $conn = $db->getConnection();
        $log = $conn->prepare(
            "INSERT INTO logs_activite (id_utilisateur, action, ip) 
             VALUES (:id, 'logout', :ip)"
        );
        $log->execute([
            ':id' => $utilisateur['id'],
            ':ip' => $_SERVER['REMOTE_ADDR']
        ]);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Déconnexion réussie"
        ]);
        break;

    default:
        http_response_code(405);
        echo json_encode([
            "success" => false,
            "message" => "Méthode non autorisée"
        ]);
        break;
}
?>