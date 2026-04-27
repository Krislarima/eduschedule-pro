<?php
// =============================================
// EduSchedule Pro - API Emploi du Temps
// Auteur : Krislarima
// =============================================

require_once __DIR__ . '/../../backend/config/cors.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/middleware/auth.php';

$methode = $_SERVER['REQUEST_METHOD'];
$db = new Database();
$conn = $db->getConnection();

// Récupérer l'action depuis l'URL
$action = $_GET['action'] ?? null;

switch ($methode) {

    // -----------------------------------------
    // GET - Récupérer les emplois du temps
    // -----------------------------------------
    case 'GET':
        $utilisateur = verifierToken();

        // GET ?action=creneaux&id_classe=1&semaine=2026-04-20
        if ($action === 'creneaux') {
            $id_classe = $_GET['id_classe'] ?? null;
            $semaine   = $_GET['semaine'] ?? null;

            if (!$id_classe) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "id_classe requis"
                ]);
                exit();
            }

            $sql = "SELECT c.*, 
                        m.libelle AS matiere_libelle,
                        m.code AS matiere_code,
                        e.nom AS enseignant_nom,
                        e.prenom AS enseignant_prenom,
                        s.code AS salle_code,
                        s.capacite AS salle_capacite,
                        et.semaine_debut,
                        et.statut_publication
                    FROM creneaux c
                    JOIN emploi_temps et ON c.id_emploi_temps = et.id
                    JOIN matieres m ON c.id_matiere = m.id
                    JOIN enseignants e ON c.id_enseignant = e.id
                    JOIN salles s ON c.id_salle = s.id
                    WHERE et.id_classe = :id_classe";

            $params = [':id_classe' => $id_classe];

            if ($semaine) {
                $sql .= " AND et.semaine_debut = :semaine";
                $params[':semaine'] = $semaine;
            }

            $sql .= " ORDER BY FIELD(c.jour, 'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'), c.heure_debut";

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $creneaux = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data"    => $creneaux,
                "total"   => count($creneaux)
            ]);

        } else {
            // GET liste des emplois du temps
            $id_classe = $_GET['id_classe'] ?? null;
            $semaine   = $_GET['semaine'] ?? null;

            $sql = "SELECT et.*, cl.libelle AS classe_libelle, 
                           cl.niveau AS classe_niveau,
                           u.email AS cree_par_email
                    FROM emploi_temps et
                    JOIN classes cl ON et.id_classe = cl.id
                    JOIN utilisateurs u ON et.cree_par = u.id
                    WHERE 1=1";
            $params = [];

            if ($id_classe) {
                $sql .= " AND et.id_classe = :id_classe";
                $params[':id_classe'] = $id_classe;
            }
            if ($semaine) {
                $sql .= " AND et.semaine_debut = :semaine";
                $params[':semaine'] = $semaine;
            }

            $sql .= " ORDER BY et.semaine_debut DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $emplois = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data"    => $emplois,
                "total"   => count($emplois)
            ]);
        }
        break;

    // -----------------------------------------
    // POST - Créer un emploi du temps + créneaux
    // -----------------------------------------
    case 'POST':

        // Publier un emploi du temps
        if ($action === 'publier') {
            $utilisateur = verifierRole(['admin']);
            $data = json_decode(file_get_contents("php://input"), true);

            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "ID de l'emploi du temps requis"
                ]);
                exit();
            }

            $stmt = $conn->prepare(
                "UPDATE emploi_temps 
                 SET statut_publication = 'publie' 
                 WHERE id = :id"
            );
            $stmt->execute([':id' => $data['id']]);

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Emploi du temps publié avec succès"
            ]);
            exit();
        }

        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        // Vérifier les champs obligatoires
        if (empty($data['id_classe']) || empty($data['semaine_debut'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Champs obligatoires : id_classe, semaine_debut"
            ]);
            exit();
        }

        // Créer l'emploi du temps
        $stmt = $conn->prepare(
            "INSERT INTO emploi_temps (id_classe, semaine_debut, cree_par) 
             VALUES (:id_classe, :semaine_debut, :cree_par)"
        );
        $stmt->execute([
            ':id_classe'    => $data['id_classe'],
            ':semaine_debut'=> $data['semaine_debut'],
            ':cree_par'     => $utilisateur['id']
        ]);

        $id_emploi_temps = $conn->lastInsertId();
        $conflits = [];
        $creneaux_crees = 0;

        // Ajouter les créneaux si fournis
        if (!empty($data['creneaux']) && is_array($data['creneaux'])) {
            foreach ($data['creneaux'] as $creneau) {

                // Vérification conflit enseignant
                $conflit_ens = $conn->prepare(
                    "SELECT c.id FROM creneaux c
                     JOIN emploi_temps et ON c.id_emploi_temps = et.id
                     WHERE c.id_enseignant = :id_enseignant
                     AND c.jour = :jour
                     AND et.semaine_debut = :semaine
                     AND (
                         (c.heure_debut < :heure_fin AND c.heure_fin > :heure_debut)
                     )"
                );
                $conflit_ens->execute([
                    ':id_enseignant' => $creneau['id_enseignant'],
                    ':jour'          => $creneau['jour'],
                    ':semaine'       => $data['semaine_debut'],
                    ':heure_debut'   => $creneau['heure_debut'],
                    ':heure_fin'     => $creneau['heure_fin']
                ]);

                if ($conflit_ens->fetch()) {
                    $conflits[] = "Conflit enseignant : " . $creneau['jour'] . 
                                  " " . $creneau['heure_debut'] . "-" . $creneau['heure_fin'];
                    continue;
                }

                // Vérification conflit salle
                $conflit_salle = $conn->prepare(
                    "SELECT c.id FROM creneaux c
                     JOIN emploi_temps et ON c.id_emploi_temps = et.id
                     WHERE c.id_salle = :id_salle
                     AND c.jour = :jour
                     AND et.semaine_debut = :semaine
                     AND (
                         (c.heure_debut < :heure_fin AND c.heure_fin > :heure_debut)
                     )"
                );
                $conflit_salle->execute([
                    ':id_salle'    => $creneau['id_salle'],
                    ':jour'        => $creneau['jour'],
                    ':semaine'     => $data['semaine_debut'],
                    ':heure_debut' => $creneau['heure_debut'],
                    ':heure_fin'   => $creneau['heure_fin']
                ]);

                if ($conflit_salle->fetch()) {
                    $conflits[] = "Conflit salle : " . $creneau['jour'] . 
                                  " " . $creneau['heure_debut'] . "-" . $creneau['heure_fin'];
                    continue;
                }

                // Insérer le créneau
                $insert = $conn->prepare(
                    "INSERT INTO creneaux 
                     (id_emploi_temps, id_matiere, id_enseignant, id_salle, 
                      jour, heure_debut, heure_fin) 
                     VALUES (:id_emploi_temps, :id_matiere, :id_enseignant, 
                             :id_salle, :jour, :heure_debut, :heure_fin)"
                );
                $insert->execute([
                    ':id_emploi_temps' => $id_emploi_temps,
                    ':id_matiere'      => $creneau['id_matiere'],
                    ':id_enseignant'   => $creneau['id_enseignant'],
                    ':id_salle'        => $creneau['id_salle'],
                    ':jour'            => $creneau['jour'],
                    ':heure_debut'     => $creneau['heure_debut'],
                    ':heure_fin'       => $creneau['heure_fin']
                ]);
                $creneaux_crees++;
            }
        }

        http_response_code(201);
        echo json_encode([
            "success"         => true,
            "message"         => "Emploi du temps créé avec succès",
            "id"              => $id_emploi_temps,
            "creneaux_crees"  => $creneaux_crees,
            "conflits"        => $conflits
        ]);
        break;

    // -----------------------------------------
    // DELETE - Supprimer un emploi du temps
    // -----------------------------------------
    case 'DELETE':
        $utilisateur = verifierRole(['admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID de l'emploi du temps requis"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "DELETE FROM emploi_temps WHERE id = :id"
        );
        $stmt->execute([':id' => $data['id']]);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Emploi du temps supprimé avec succès"
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