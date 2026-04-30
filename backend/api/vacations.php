<?php
// =============================================
// EduSchedule Pro - API Vacations
// =============================================

require_once __DIR__ . '/../../backend/config/cors.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/middleware/auth.php';

$methode = $_SERVER['REQUEST_METHOD'];
$db      = new Database();
$conn    = $db->getConnection();
$action  = $_GET['action'] ?? null;
$id      = $_GET['id'] ?? null;

switch ($methode) {

    // -----------------------------------------
    // GET - Liste ou détail des vacations
    // -----------------------------------------
    case 'GET':
        $utilisateur = verifierToken();

        if ($id) {
            // Détail d'une vacation
            $stmt = $conn->prepare(
                "SELECT v.*,
                        e.nom AS enseignant_nom,
                        e.prenom AS enseignant_prenom,
                        e.matricule, e.taux_horaire
                 FROM vacations v
                 JOIN enseignants e ON v.id_enseignant = e.id
                 WHERE v.id = :id"
            );
            $stmt->execute([':id' => $id]);
            $vacation = $stmt->fetch();

            if (!$vacation) {
                http_response_code(404);
                echo json_encode([
                    "success" => false,
                    "message" => "Vacation introuvable"
                ]);
                exit();
            }

            // Récupérer les lignes de détail
            $lignes = $conn->prepare(
                "SELECT vl.*,
                        c.jour, c.heure_debut, c.heure_fin,
                        m.libelle AS matiere,
                        cl.libelle AS classe
                 FROM vacation_lignes vl
                 JOIN creneaux c ON vl.id_creneau = c.id
                 JOIN matieres m ON c.id_matiere = m.id
                 JOIN emploi_temps et ON c.id_emploi_temps = et.id
                 JOIN classes cl ON et.id_classe = cl.id
                 WHERE vl.id_vacation = :id"
            );
            $lignes->execute([':id' => $id]);
            $vacation['lignes'] = $lignes->fetchAll();

            // Récupérer les validations
            $validations = $conn->prepare(
                "SELECT val.*,
                        u.email AS validateur_email
                 FROM validations val
                 JOIN utilisateurs u ON val.id_validateur = u.id
                 WHERE val.id_vacation = :id
                 ORDER BY val.date_validation ASC"
            );
            $validations->execute([':id' => $id]);
            $vacation['validations'] = $validations->fetchAll();

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data"    => $vacation
            ]);

        } else {
            // Liste des vacations
            $id_enseignant = $_GET['id_enseignant'] ?? null;
            $mois          = $_GET['mois'] ?? null;
            $annee         = $_GET['annee'] ?? null;

            $sql = "SELECT v.*,
                           e.nom AS enseignant_nom,
                           e.prenom AS enseignant_prenom,
                           e.matricule
                    FROM vacations v
                    JOIN enseignants e ON v.id_enseignant = e.id
                    WHERE 1=1";
            $params = [];

            if ($id_enseignant) {
                $sql .= " AND v.id_enseignant = :id_enseignant";
                $params[':id_enseignant'] = $id_enseignant;
            }
            if ($mois) {
                $sql .= " AND v.mois = :mois";
                $params[':mois'] = $mois;
            }
            if ($annee) {
                $sql .= " AND v.annee = :annee";
                $params[':annee'] = $annee;
            }

            $sql .= " ORDER BY v.annee DESC, v.mois DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $vacations = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data"    => $vacations,
                "total"   => count($vacations)
            ]);
        }
        break;

    // -----------------------------------------
    // POST - Générer ou valider une vacation
    // -----------------------------------------
    case 'POST':
        $utilisateur = verifierToken();
        $data = json_decode(file_get_contents("php://input"), true);

        // Valider une vacation (surveillant)
        if ($action === 'valider' && $id) {
            verifierRole(['surveillant', 'comptable']);

            $role = $utilisateur['role'];
            $nouveau_statut = $role === 'surveillant'
                ? 'validee_surveillant' : 'approuvee_comptable';

            // Enregistrer la validation
            $stmt = $conn->prepare(
                "INSERT INTO validations 
                 (id_vacation, id_validateur, role_validateur, 
                  visa_base64, commentaire) 
                 VALUES (:id_vacation, :id_validateur, :role, 
                         :visa, :commentaire)"
            );
            $stmt->execute([
                ':id_vacation'   => $id,
                ':id_validateur' => $utilisateur['id'],
                ':role'          => $role,
                ':visa'          => $data['visa_base64'] ?? null,
                ':commentaire'   => $data['commentaire'] ?? null
            ]);

            // Mettre à jour le statut
            $update = $conn->prepare(
                "UPDATE vacations SET statut = :statut WHERE id = :id"
            );
            $update->execute([
                ':statut' => $nouveau_statut,
                ':id'     => $id
            ]);

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Vacation validée avec succès",
                "statut"  => $nouveau_statut
            ]);
            exit();
        }

        // Signer une vacation (enseignant)
        if ($action === 'signer' && $id) {
            verifierRole(['enseignant']);

            $update = $conn->prepare(
                "UPDATE vacations 
                 SET statut = 'signee_enseignant' 
                 WHERE id = :id"
            );
            $update->execute([':id' => $id]);

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Vacation signée avec succès"
            ]);
            exit();
        }

        // Générer une fiche de vacation
        verifierRole(['admin', 'surveillant']);

        if (empty($data['id_enseignant']) || 
            empty($data['mois']) || 
            empty($data['annee'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "id_enseignant, mois et annee requis"
            ]);
            exit();
        }

        // Vérifier si vacation déjà générée
        $check = $conn->prepare(
            "SELECT id FROM vacations 
             WHERE id_enseignant = :id AND mois = :mois AND annee = :annee"
        );
        $check->execute([
            ':id'   => $data['id_enseignant'],
            ':mois' => $data['mois'],
            ':annee'=> $data['annee']
        ]);
        if ($check->fetch()) {
            http_response_code(409);
            echo json_encode([
                "success" => false,
                "message" => "Une fiche de vacation existe déjà pour ce mois"
            ]);
            exit();
        }

        // Récupérer les séances pointées du mois
        $seances = $conn->prepare(
            "SELECT c.id AS id_creneau,
                    c.heure_debut, c.heure_fin,
                    e.taux_horaire,
                    ct.heure_fin_reelle,
                    p.heure_pointage_reelle
             FROM creneaux c
             JOIN enseignants e ON c.id_enseignant = e.id
             JOIN pointages p ON p.id_creneau = c.id
             LEFT JOIN cahiers_texte ct ON ct.id_creneau = c.id
             WHERE c.id_enseignant = :id_enseignant
             AND MONTH(p.heure_pointage_reelle) = :mois
             AND YEAR(p.heure_pointage_reelle) = :annee
             AND p.statut != 'echoue'
             AND ct.statut = 'cloture'"
        );
        $seances->execute([
            ':id_enseignant' => $data['id_enseignant'],
            ':mois'          => $data['mois'],
            ':annee'         => $data['annee']
        ]);
        $liste_seances = $seances->fetchAll();

        if (empty($liste_seances)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Aucune séance clôturée trouvée pour ce mois"
            ]);
            exit();
        }

        // Calculer les montants
        $montant_brut = 0;
        $lignes_vacation = [];

        foreach ($liste_seances as $seance) {
            // Calculer la durée en heures
            $debut = strtotime($seance['heure_pointage_reelle']);
            $fin   = $seance['heure_fin_reelle']
                ? strtotime(date('Y-m-d', $debut) . ' ' . $seance['heure_fin_reelle'])
                : strtotime(date('Y-m-d', $debut) . ' ' . $seance['heure_fin']);

            $duree_heures = round(($fin - $debut) / 3600, 2);
            $taux         = $seance['taux_horaire'];
            $montant      = $duree_heures * $taux;
            $montant_brut += $montant;

            $lignes_vacation[] = [
                'id_creneau'   => $seance['id_creneau'],
                'duree_heures' => $duree_heures,
                'taux'         => $taux,
                'montant'      => $montant
            ];
        }

        $montant_net = $montant_brut; // Pas de retenues pour l'instant

        // Créer la vacation
        $stmt = $conn->prepare(
            "INSERT INTO vacations 
             (id_enseignant, mois, annee, montant_brut, montant_net) 
             VALUES (:id_enseignant, :mois, :annee, :brut, :net)"
        );
        $stmt->execute([
            ':id_enseignant' => $data['id_enseignant'],
            ':mois'          => $data['mois'],
            ':annee'         => $data['annee'],
            ':brut'          => $montant_brut,
            ':net'           => $montant_net
        ]);

        $id_vacation = $conn->lastInsertId();

        // Insérer les lignes de détail
        foreach ($lignes_vacation as $ligne) {
            $l = $conn->prepare(
                "INSERT INTO vacation_lignes 
                 (id_vacation, id_creneau, duree_heures, taux, montant) 
                 VALUES (:id_vacation, :id_creneau, :duree, :taux, :montant)"
            );
            $l->execute([
                ':id_vacation' => $id_vacation,
                ':id_creneau'  => $ligne['id_creneau'],
                ':duree'       => $ligne['duree_heures'],
                ':taux'        => $ligne['taux'],
                ':montant'     => $ligne['montant']
            ]);
        }

        http_response_code(201);
        echo json_encode([
            "success"      => true,
            "message"      => "Fiche de vacation générée avec succès",
            "id"           => $id_vacation,
            "montant_brut" => $montant_brut,
            "montant_net"  => $montant_net,
            "nb_seances"   => count($lignes_vacation)
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