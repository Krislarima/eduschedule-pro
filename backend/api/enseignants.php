<?php
// =============================================
// EduSchedule Pro - API Enseignants
// =============================================

require_once __DIR__ . '/../../backend/config/cors.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/middleware/auth.php';

$methode = $_SERVER['REQUEST_METHOD'];
$db = new Database();
$conn = $db->getConnection();

switch ($methode) {

    // -----------------------------------------
    // GET /api/enseignants - Liste les enseignants
    // -----------------------------------------
    case 'GET':
        $utilisateur = verifierToken();

        $specialite = $_GET['specialite'] ?? null;
        $statut = $_GET['statut'] ?? null;

        $sql = "SELECT * FROM enseignants WHERE 1=1";
        $params = [];

        if ($specialite) {
            $sql .= " AND specialite = :specialite";
            $params[':specialite'] = $specialite;
        }
        if ($statut) {
            $sql .= " AND statut = :statut";
            $params[':statut'] = $statut;
        }

        $sql .= " ORDER BY nom, prenom";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $enseignants = $stmt->fetchAll();

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "data"    => $enseignants,
            "total"   => count($enseignants)
        ]);
        break;

    // -----------------------------------------
    // POST /api/enseignants - Créer un enseignant
    // -----------------------------------------
    case 'POST':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['matricule']) || empty($data['nom']) || 
            empty($data['prenom'])   || empty($data['email'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Champs obligatoires : matricule, nom, prenom, email"
            ]);
            exit();
        }

        // Vérifier si l'email ou matricule existe déjà
        $check = $conn->prepare(
            "SELECT id FROM enseignants 
             WHERE email = :email OR matricule = :matricule"
        );
        $check->execute([
            ':email'     => $data['email'],
            ':matricule' => $data['matricule']
        ]);
        if ($check->fetch()) {
            http_response_code(409);
            echo json_encode([
                "success" => false,
                "message" => "Email ou matricule déjà utilisé"
            ]);
            exit();
        }

        // Insérer l'enseignant
        $stmt = $conn->prepare(
            "INSERT INTO enseignants 
             (matricule, nom, prenom, email, specialite, statut, taux_horaire) 
             VALUES (:matricule, :nom, :prenom, :email, :specialite, :statut, :taux)"
        );
        $stmt->execute([
            ':matricule'  => $data['matricule'],
            ':nom'        => $data['nom'],
            ':prenom'     => $data['prenom'],
            ':email'      => $data['email'],
            ':specialite' => $data['specialite'] ?? null,
            ':statut'     => $data['statut'] ?? 'vacataire',
            ':taux'       => $data['taux_horaire'] ?? 0.00
        ]);

        // Créer aussi un compte utilisateur pour l'enseignant
        $hash = password_hash('password123', PASSWORD_BCRYPT);
        $user = $conn->prepare(
            "INSERT INTO utilisateurs (email, mot_de_passe_hash, role, id_lien) 
             VALUES (:email, :hash, 'enseignant', :id_lien)"
        );
        $user->execute([
            ':email'   => $data['email'],
            ':hash'    => $hash,
            ':id_lien' => $conn->lastInsertId()
        ]);

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Enseignant créé avec succès",
            "id"      => $conn->lastInsertId()
        ]);
        break;

    // -----------------------------------------
    // PUT /api/enseignants - Modifier un enseignant
    // -----------------------------------------
    case 'PUT':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID de l'enseignant requis"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "UPDATE enseignants 
             SET matricule = :matricule, nom = :nom, prenom = :prenom,
                 email = :email, specialite = :specialite,
                 statut = :statut, taux_horaire = :taux
             WHERE id = :id"
        );
        $stmt->execute([
            ':matricule'  => $data['matricule'],
            ':nom'        => $data['nom'],
            ':prenom'     => $data['prenom'],
            ':email'      => $data['email'],
            ':specialite' => $data['specialite'] ?? null,
            ':statut'     => $data['statut'] ?? 'vacataire',
            ':taux'       => $data['taux_horaire'] ?? 0.00,
            ':id'         => $data['id']
        ]);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Enseignant modifié avec succès"
        ]);
        break;

    // -----------------------------------------
    // DELETE /api/enseignants - Supprimer un enseignant
    // -----------------------------------------
    case 'DELETE':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID de l'enseignant requis"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "DELETE FROM enseignants WHERE id = :id"
        );
        $stmt->execute([':id' => $data['id']]);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Enseignant supprimé avec succès"
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