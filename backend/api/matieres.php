<?php
// =============================================
// EduSchedule Pro - API Matières
// =============================================

require_once __DIR__ . '/../../backend/config/cors.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/middleware/auth.php';

$methode = $_SERVER['REQUEST_METHOD'];
$db = new Database();
$conn = $db->getConnection();

switch ($methode) {

    // -----------------------------------------
    // GET /api/matieres - Liste toutes les matières
    // -----------------------------------------
    case 'GET':
        $utilisateur = verifierToken();

        $stmt = $conn->prepare(
            "SELECT * FROM matieres ORDER BY libelle"
        );
        $stmt->execute();
        $matieres = $stmt->fetchAll();

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "data"    => $matieres,
            "total"   => count($matieres)
        ]);
        break;

    // -----------------------------------------
    // POST /api/matieres - Créer une matière
    // -----------------------------------------
    case 'POST':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['code']) || empty($data['libelle']) || 
            empty($data['volume_horaire_total'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Champs obligatoires : code, libelle, volume_horaire_total"
            ]);
            exit();
        }

        // Vérifier si le code existe déjà
        $check = $conn->prepare(
            "SELECT id FROM matieres WHERE code = :code"
        );
        $check->execute([':code' => $data['code']]);
        if ($check->fetch()) {
            http_response_code(409);
            echo json_encode([
                "success" => false,
                "message" => "Une matière avec ce code existe déjà"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "INSERT INTO matieres (code, libelle, volume_horaire_total, coefficient) 
             VALUES (:code, :libelle, :volume, :coefficient)"
        );
        $stmt->execute([
            ':code'        => $data['code'],
            ':libelle'     => $data['libelle'],
            ':volume'      => $data['volume_horaire_total'],
            ':coefficient' => $data['coefficient'] ?? 1.00
        ]);

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Matière créée avec succès",
            "id"      => $conn->lastInsertId()
        ]);
        break;

    // -----------------------------------------
    // PUT /api/matieres - Modifier une matière
    // -----------------------------------------
    case 'PUT':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID de la matière requis"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "UPDATE matieres 
             SET code = :code, libelle = :libelle,
                 volume_horaire_total = :volume, 
                 coefficient = :coefficient
             WHERE id = :id"
        );
        $stmt->execute([
            ':code'        => $data['code'],
            ':libelle'     => $data['libelle'],
            ':volume'      => $data['volume_horaire_total'],
            ':coefficient' => $data['coefficient'] ?? 1.00,
            ':id'          => $data['id']
        ]);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Matière modifiée avec succès"
        ]);
        break;

    // -----------------------------------------
    // DELETE /api/matieres - Supprimer une matière
    // -----------------------------------------
    case 'DELETE':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID de la matière requis"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "DELETE FROM matieres WHERE id = :id"
        );
        $stmt->execute([':id' => $data['id']]);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Matière supprimée avec succès"
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