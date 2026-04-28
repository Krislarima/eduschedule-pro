<?php
// =============================================
// EduSchedule Pro - API Pointages QR-Code
// =============================================

require_once __DIR__ . '/../../backend/config/cors.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/middleware/auth.php';

$methode = $_SERVER['REQUEST_METHOD'];
$db      = new Database();
$conn    = $db->getConnection();

switch ($methode) {

    // -----------------------------------------
    // POST /api/pointages/scan — Valider un QR
    // -----------------------------------------
    case 'POST':
        $utilisateur = verifierToken();
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['token_qr'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Token QR requis"
            ]);
            exit();
        }

        // Décoder le token
        $token_decode = json_decode(base64_decode($data['token_qr']), true);

        if (!$token_decode || empty($token_decode['id_creneau'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Token QR invalide"
            ]);
            exit();
        }

        $id_creneau = $token_decode['id_creneau'];

        // Récupérer le créneau
        $stmt = $conn->prepare(
            "SELECT c.*, 
                    et.semaine_debut,
                    e.id AS enseignant_id
             FROM creneaux c
             JOIN emploi_temps et ON c.id_emploi_temps = et.id
             JOIN enseignants e ON c.id_enseignant = e.id
             WHERE c.id = :id"
        );
        $stmt->execute([':id' => $id_creneau]);
        $creneau = $stmt->fetch();

        if (!$creneau) {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Créneau introuvable"
            ]);
            exit();
        }

        // Vérifier que le token correspond
        $cle_attendue = hash('sha256',
            $id_creneau . $creneau['heure_debut'] .
            'eduschedule_secret_2026'
        );

        if ($token_decode['cle'] !== $cle_attendue) {
            // Logger la tentative échouée
            $log = $conn->prepare(
                "INSERT INTO pointages 
                 (id_creneau, heure_pointage_reelle, ip_source, token_utilise, statut) 
                 VALUES (:id, NOW(), :ip, :token, 'echoue')"
            );
            $log->execute([
                ':id'    => $id_creneau,
                ':ip'    => $_SERVER['REMOTE_ADDR'],
                ':token' => $data['token_qr']
            ]);

            http_response_code(401);
            echo json_encode([
                "success" => false,
                "message" => "Token QR non authentique"
            ]);
            exit();
        }

        // Vérifier que le QR n'a pas expiré
        if ($creneau['qr_expire'] && strtotime($creneau['qr_expire']) < time()) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "QR-Code expiré — la fenêtre horaire est dépassée"
            ]);
            exit();
        }

        // Vérifier que ce créneau n'a pas déjà été pointé
        $check = $conn->prepare(
            "SELECT id FROM pointages 
             WHERE id_creneau = :id AND statut != 'echoue'"
        );
        $check->execute([':id' => $id_creneau]);
        if ($check->fetch()) {
            http_response_code(409);
            echo json_encode([
                "success" => false,
                "message" => "Ce créneau a déjà été pointé"
            ]);
            exit();
        }

        // Calculer le statut (retard si > 30 min après heure prévue)
        $heure_prevue = strtotime(
            $creneau['semaine_debut'] . ' ' . $creneau['heure_debut']
        );
        $diff_minutes = (time() - $heure_prevue) / 60;
        $statut = $diff_minutes > 30 ? 'retard' : 'valide';

        // Enregistrer le pointage
        $insert = $conn->prepare(
            "INSERT INTO pointages 
             (id_creneau, heure_pointage_reelle, ip_source, token_utilise, statut) 
             VALUES (:id, NOW(), :ip, :token, :statut)"
        );
        $insert->execute([
            ':id'     => $id_creneau,
            ':ip'     => $_SERVER['REMOTE_ADDR'],
            ':token'  => $data['token_qr'],
            ':statut' => $statut
        ]);

        // Invalider le QR-Code (usage unique)
        $invalidate = $conn->prepare(
            "UPDATE creneaux SET qr_token = NULL, qr_expire = NULL WHERE id = :id"
        );
        $invalidate->execute([':id' => $id_creneau]);

        // Logger l'activité
        $log = $conn->prepare(
            "INSERT INTO logs_activite (id_utilisateur, action, details_json, ip) 
             VALUES (:id, 'pointage', :details, :ip)"
        );
        $log->execute([
            ':id'      => $utilisateur['id'],
            ':details' => json_encode([
                'id_creneau' => $id_creneau,
                'statut'     => $statut
            ]),
            ':ip'      => $_SERVER['REMOTE_ADDR']
        ]);

        // Alerte si retard
        $message = $statut === 'retard'
            ? "Pointage enregistré avec retard de " . round($diff_minutes) . " minutes"
            : "Pointage enregistré avec succès";

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => $message,
            "statut"  => $statut,
            "heure_pointage" => date('H:i:s'),
            "creneau" => [
                "id"          => $creneau['id'],
                "jour"        => $creneau['jour'],
                "heure_debut" => $creneau['heure_debut'],
                "heure_fin"   => $creneau['heure_fin']
            ]
        ]);
        break;

    // -----------------------------------------
    // GET /api/pointages — Liste des pointages
    // -----------------------------------------
    case 'GET':
        $utilisateur = verifierRole(['admin', 'surveillant']);

        $id_creneau = $_GET['id_creneau'] ?? null;

        $sql = "SELECT p.*, 
                       c.jour, c.heure_debut, c.heure_fin,
                       m.libelle AS matiere,
                       e.nom AS enseignant_nom,
                       e.prenom AS enseignant_prenom
                FROM pointages p
                JOIN creneaux c ON p.id_creneau = c.id
                JOIN matieres m ON c.id_matiere = m.id
                JOIN enseignants e ON c.id_enseignant = e.id
                WHERE 1=1";
        $params = [];

        if ($id_creneau) {
            $sql .= " AND p.id_creneau = :id_creneau";
            $params[':id_creneau'] = $id_creneau;
        }

        $sql .= " ORDER BY p.heure_pointage_reelle DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $pointages = $stmt->fetchAll();

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "data"    => $pointages,
            "total"   => count($pointages)
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