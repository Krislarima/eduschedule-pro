<?php
// =============================================
// EduSchedule Pro - API Salles
// =============================================

require_once __DIR__ . '/../../backend/config/cors.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/middleware/auth.php';

$methode = $_SERVER['REQUEST_METHOD'];
$db = new Database();
$conn = $db->getConnection();

switch ($methode) {

    // -----------------------------------------
    // GET /api/salles - Liste toutes les salles
    // -----------------------------------------
    case 'GET':
        $utilisateur = verifierToken();

        $stmt = $conn->prepare(
            "SELECT * FROM salles ORDER BY batiment, code"
        );
        $stmt->execute();
        $salles = $stmt->fetchAll();

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "data"    => $salles,
            "total"   => count($salles)
        ]);
        break;

    // -----------------------------------------
    // POST /api/salles - Créer une salle
    // -----------------------------------------
    case 'POST':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['code']) || empty($data['capacite'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Champs obligatoires : code, capacite"
            ]);
            exit();
        }

        // Vérifier si le code existe déjà
        $check = $conn->prepare(
            "SELECT id FROM salles WHERE code = :code"
        );
        $check->execute([':code' => $data['code']]);
        if ($check->fetch()) {
            http_response_code(409);
            echo json_encode([
                "success" => false,
                "message" => "Une salle avec ce code existe déjà"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "INSERT INTO salles (code, capacite, equipements, batiment) 
             VALUES (:code, :capacite, :equipements, :batiment)"
        );
        $stmt->execute([
            ':code'        => $data['code'],
            ':capacite'    => $data['capacite'],
            ':equipements' => $data['equipements'] ?? null,
            ':batiment'    => $data['batiment'] ?? null
        ]);

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Salle créée avec succès",
            "id"      => $conn->lastInsertId()
        ]);
        break;

    // -----------------------------------------
    // PUT /api/salles - Modifier une salle
    // -----------------------------------------
    case 'PUT':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID de la salle requis"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "UPDATE salles 
             SET code = :code, capacite = :capacite,
                 equipements = :equipements, batiment = :batiment
             WHERE id = :id"
        );
        $stmt->execute([
            ':code'        => $data['code'],
            ':capacite'    => $data['capacite'],
            ':equipements' => $data['equipements'] ?? null,
            ':batiment'    => $data['batiment'] ?? null,
            ':id'          => $data['id']
        ]);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Salle modifiée avec succès"
        ]);
        break;

    // -----------------------------------------
    // DELETE /api/salles - Supprimer une salle
    // -----------------------------------------
    case 'DELETE':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID de la salle requis"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "DELETE FROM salles WHERE id = :id"
        );
        $stmt->execute([':id' => $data['id']]);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Salle supprimée avec succès"
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