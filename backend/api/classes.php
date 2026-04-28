<?php
// =============================================
// EduSchedule Pro - API Classes
// =============================================

require_once __DIR__ . '/../../backend/config/cors.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/middleware/auth.php';

$methode = $_SERVER['REQUEST_METHOD'];
$db = new Database();
$conn = $db->getConnection();

switch ($methode) {

    // -----------------------------------------
    // GET /api/classes - Liste toutes les classes
    // -----------------------------------------
    case 'GET':
        $utilisateur = verifierToken();

        $annee = $_GET['annee'] ?? null;

        if ($annee) {
            $stmt = $conn->prepare(
                "SELECT * FROM classes 
                 WHERE annee_academique = :annee 
                 ORDER BY niveau, code"
            );
            $stmt->execute([':annee' => $annee]);
        } else {
            $stmt = $conn->prepare(
                "SELECT * FROM classes ORDER BY niveau, code"
            );
            $stmt->execute();
        }

        $classes = $stmt->fetchAll();

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "data"    => $classes,
            "total"   => count($classes)
        ]);
        break;

    // -----------------------------------------
    // POST /api/classes - Créer une classe
    // -----------------------------------------
    case 'POST':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        // Vérification des champs obligatoires
        if (empty($data['code']) || empty($data['libelle']) || 
            empty($data['niveau']) || empty($data['annee_academique'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Champs obligatoires manquants : code, libelle, niveau, annee_academique"
            ]);
            exit();
        }

        // Vérifier si le code existe déjà
        $check = $conn->prepare(
            "SELECT id FROM classes WHERE code = :code"
        );
        $check->execute([':code' => $data['code']]);
        if ($check->fetch()) {
            http_response_code(409);
            echo json_encode([
                "success" => false,
                "message" => "Une classe avec ce code existe déjà"
            ]);
            exit();
        }

        // Insérer la classe
        $stmt = $conn->prepare(
            "INSERT INTO classes (code, libelle, niveau, annee_academique) 
             VALUES (:code, :libelle, :niveau, :annee)"
        );
        $stmt->execute([
            ':code'    => $data['code'],
            ':libelle' => $data['libelle'],
            ':niveau'  => $data['niveau'],
            ':annee'   => $data['annee_academique']
        ]);

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Classe créée avec succès",
            "id"      => $conn->lastInsertId()
        ]);
        break;

    // -----------------------------------------
    // PUT /api/classes - Modifier une classe
    // -----------------------------------------
    case 'PUT':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID de la classe requis"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "UPDATE classes 
             SET code = :code, libelle = :libelle, 
                 niveau = :niveau, annee_academique = :annee 
             WHERE id = :id"
        );
        $stmt->execute([
            ':code'    => $data['code'],
            ':libelle' => $data['libelle'],
            ':niveau'  => $data['niveau'],
            ':annee'   => $data['annee_academique'],
            ':id'      => $data['id']
        ]);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Classe modifiée avec succès"
        ]);
        break;

    // -----------------------------------------
    // DELETE /api/classes - Supprimer une classe
    // -----------------------------------------
    case 'DELETE':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID de la classe requis"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "DELETE FROM classes WHERE id = :id"
        );
        $stmt->execute([':id' => $data['id']]);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Classe supprimée avec succès"
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