<?php
// =============================================
// EduSchedule Pro - API Créneaux & QR-Code
// =============================================

require_once __DIR__ . '/../../backend/config/cors.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/middleware/auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$methode = $_SERVER['REQUEST_METHOD'];
$db      = new Database();
$conn    = $db->getConnection();
$action  = $_GET['action'] ?? null;
$id      = $_GET['id'] ?? null;

switch ($methode) {

    // -----------------------------------------
    // GET ?action=qr&id={id} — Générer QR-Code
    // -----------------------------------------
    case 'GET':
        $utilisateur = verifierToken();

        if ($action === 'qr' && $id) {

            // Récupérer le créneau
            $stmt = $conn->prepare(
                "SELECT c.*, 
                        m.libelle AS matiere,
                        e.nom AS enseignant_nom,
                        e.prenom AS enseignant_prenom,
                        s.code AS salle,
                        cl.libelle AS classe,
                        et.semaine_debut
                 FROM creneaux c
                 JOIN matieres m ON c.id_matiere = m.id
                 JOIN enseignants e ON c.id_enseignant = e.id
                 JOIN salles s ON c.id_salle = s.id
                 JOIN emploi_temps et ON c.id_emploi_temps = et.id
                 JOIN classes cl ON et.id_classe = cl.id
                 WHERE c.id = :id"
            );
            $stmt->execute([':id' => $id]);
            $creneau = $stmt->fetch();

            if (!$creneau) {
                http_response_code(404);
                echo json_encode([
                    "success" => false,
                    "message" => "Créneau non trouvé"
                ]);
                exit();
            }

            // Générer le token QR sécurisé
            $token_data = [
                'id_creneau' => $creneau['id'],
                'horodatage' => time(),
                'cle'        => hash('sha256', 
                    $creneau['id'] . $creneau['heure_debut'] . 
                    'eduschedule_secret_2026'
                )
            ];
            $token = base64_encode(json_encode($token_data));

            // Calculer l'expiration (heure prévue + 15 min)
            $heure_debut  = strtotime($creneau['semaine_debut'] . ' ' . $creneau['heure_debut']);
            $qr_expire = date('Y-m-d H:i:s', time() + (30 * 60));
            $update = $conn->prepare(
                "UPDATE creneaux 
                 SET qr_token = :token, qr_expire = :expire 
                 WHERE id = :id"
            );
            $update->execute([
                ':token'  => $token,
                ':expire' => $qr_expire,
                ':id'     => $id
            ]);

            // Générer l'image QR-Code
            $options = new QROptions([
                'outputType' => 'png',
                'eccLevel'   => 'H',
                'scale'      => 10,
                'imageBase64'=> true,
            ]);

            $url_pointage = "http://localhost/eduschedule-pro/backend/api/pointages.php?token=" . urlencode($token);
            $qrcode = (new QRCode($options))->render($url_pointage);

            http_response_code(200);
            echo json_encode([
                "success"    => true,
                "token"      => $token,
                "qr_expire"  => $qr_expire,
                "qr_image"   => $qrcode,
                "creneau"    => [
                    "id"          => $creneau['id'],
                    "matiere"     => $creneau['matiere'],
                    "classe"      => $creneau['classe'],
                    "enseignant"  => $creneau['enseignant_prenom'] . ' ' . $creneau['enseignant_nom'],
                    "salle"       => $creneau['salle'],
                    "jour"        => $creneau['jour'],
                    "heure_debut" => $creneau['heure_debut'],
                    "heure_fin"   => $creneau['heure_fin']
                ]
            ]);

        } else {
            // GET liste des créneaux
            $id_emploi_temps = $_GET['id_emploi_temps'] ?? null;

            if (!$id_emploi_temps) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "id_emploi_temps requis"
                ]);
                exit();
            }

            $stmt = $conn->prepare(
                "SELECT c.*,
                        m.libelle AS matiere,
                        e.nom AS enseignant_nom,
                        e.prenom AS enseignant_prenom,
                        s.code AS salle
                 FROM creneaux c
                 JOIN matieres m ON c.id_matiere = m.id
                 JOIN enseignants e ON c.id_enseignant = e.id
                 JOIN salles s ON c.id_salle = s.id
                 WHERE c.id_emploi_temps = :id_emploi_temps
                 ORDER BY FIELD(c.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'), c.heure_debut"
            );
            $stmt->execute([':id_emploi_temps' => $id_emploi_temps]);
            $creneaux = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data"    => $creneaux,
                "total"   => count($creneaux)
            ]);
        }
        break;

    // -----------------------------------------
    // DELETE - Supprimer un créneau
    // -----------------------------------------
    case 'DELETE':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID du créneau requis"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "DELETE FROM creneaux WHERE id = :id"
        );
        $stmt->execute([':id' => $data['id']]);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Créneau supprimé avec succès"
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