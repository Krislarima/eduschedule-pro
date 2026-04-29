<?php
// =============================================
// EduSchedule Pro - API Cahier de Texte
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
    // GET - Liste ou détail des cahiers
    // -----------------------------------------
    case 'GET':
        $utilisateur = verifierToken();

        if ($id) {
            // Détail d'un cahier
            $stmt = $conn->prepare(
                "SELECT ct.*,
                        u.email AS delegue_email,
                        c.jour, c.heure_debut, c.heure_fin,
                        m.libelle AS matiere,
                        e.nom AS enseignant_nom,
                        e.prenom AS enseignant_prenom,
                        cl.libelle AS classe
                 FROM cahiers_texte ct
                 JOIN utilisateurs u ON ct.id_delegue = u.id
                 JOIN creneaux c ON ct.id_creneau = c.id
                 JOIN matieres m ON c.id_matiere = m.id
                 JOIN enseignants e ON c.id_enseignant = e.id
                 JOIN emploi_temps et ON c.id_emploi_temps = et.id
                 JOIN classes cl ON et.id_classe = cl.id
                 WHERE ct.id = :id"
            );
            $stmt->execute([':id' => $id]);
            $cahier = $stmt->fetch();

            if (!$cahier) {
                http_response_code(404);
                echo json_encode([
                    "success" => false,
                    "message" => "Cahier introuvable"
                ]);
                exit();
            }

            // Récupérer les signatures
            $sigs = $conn->prepare(
                "SELECT * FROM signatures WHERE id_cahier = :id"
            );
            $sigs->execute([':id' => $id]);
            $cahier['signatures'] = $sigs->fetchAll();

            // Récupérer les travaux
            $travaux = $conn->prepare(
                "SELECT * FROM travaux_demandes WHERE id_cahier = :id"
            );
            $travaux->execute([':id' => $id]);
            $cahier['travaux'] = $travaux->fetchAll();

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data"    => $cahier
            ]);

        } else {
            // Liste des cahiers
            $id_creneau = $_GET['id_creneau'] ?? null;
            $id_classe  = $_GET['id_classe'] ?? null;
            $mois       = $_GET['mois'] ?? null;

            $sql = "SELECT ct.*,
                           m.libelle AS matiere,
                           e.nom AS enseignant_nom,
                           e.prenom AS enseignant_prenom,
                           cl.libelle AS classe,
                           c.jour, c.heure_debut, c.heure_fin
                    FROM cahiers_texte ct
                    JOIN creneaux c ON ct.id_creneau = c.id
                    JOIN matieres m ON c.id_matiere = m.id
                    JOIN enseignants e ON c.id_enseignant = e.id
                    JOIN emploi_temps et ON c.id_emploi_temps = et.id
                    JOIN classes cl ON et.id_classe = cl.id
                    WHERE 1=1";
            $params = [];

            if ($id_creneau) {
                $sql .= " AND ct.id_creneau = :id_creneau";
                $params[':id_creneau'] = $id_creneau;
            }
            if ($id_classe) {
                $sql .= " AND et.id_classe = :id_classe";
                $params[':id_classe'] = $id_classe;
            }
            if ($mois) {
                $sql .= " AND MONTH(ct.date_creation) = :mois";
                $params[':mois'] = $mois;
            }

            $sql .= " ORDER BY ct.date_creation DESC";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $cahiers = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "data"    => $cahiers,
                "total"   => count($cahiers)
            ]);
        }
        break;

    // -----------------------------------------
    // POST - Créer ou signer un cahier
    // -----------------------------------------
    case 'POST':
        $utilisateur = verifierToken();
        $data = json_decode(file_get_contents("php://input"), true);

        // Signer un cahier
        if ($action === 'signer' && $id) {
            verifierRole(['delegue', 'enseignant']);

            if (empty($data['signature_base64']) || empty($data['type'])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Signature et type requis"
                ]);
                exit();
            }

            // Enregistrer la signature
            $stmt = $conn->prepare(
                "INSERT INTO signatures 
                 (id_cahier, type_signataire, id_utilisateur, signature_base64) 
                 VALUES (:id_cahier, :type, :id_user, :signature)"
            );
            $stmt->execute([
                ':id_cahier'  => $id,
                ':type'       => $data['type'],
                ':id_user'    => $utilisateur['id'],
                ':signature'  => $data['signature_base64']
            ]);

            // Mettre à jour le statut
            $nouveau_statut = $data['type'] === 'delegue'
                ? 'signe_delegue' : 'cloture';

            $update = $conn->prepare(
                "UPDATE cahiers_texte 
                 SET statut = :statut 
                 WHERE id = :id"
            );
            $update->execute([
                ':statut' => $nouveau_statut,
                ':id'     => $id
            ]);

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Signature enregistrée avec succès",
                "statut"  => $nouveau_statut
            ]);
            exit();
        }

        // Clôturer une séance
        if ($action === 'cloture' && $id) {
            verifierRole(['enseignant']);

            if (empty($data['heure_fin'])) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Heure de fin requise"
                ]);
                exit();
            }

            $stmt = $conn->prepare(
                "UPDATE cahiers_texte 
                 SET heure_fin_reelle = :heure_fin, statut = 'cloture'
                 WHERE id = :id"
            );
            $stmt->execute([
                ':heure_fin' => $data['heure_fin'],
                ':id'        => $id
            ]);

            // Signature enseignant si fournie
            if (!empty($data['signature_base64'])) {
                $sig = $conn->prepare(
                    "INSERT INTO signatures 
                     (id_cahier, type_signataire, id_utilisateur, signature_base64) 
                     VALUES (:id_cahier, 'enseignant', :id_user, :signature)"
                );
                $sig->execute([
                    ':id_cahier' => $id,
                    ':id_user'   => $utilisateur['id'],
                    ':signature' => $data['signature_base64']
                ]);
            }

            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Séance clôturée avec succès"
            ]);
            exit();
        }

        // Créer un nouveau cahier
        verifierRole(['delegue', 'admin']);

        if (empty($data['id_creneau'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "id_creneau requis"
            ]);
            exit();
        }

        // Vérifier si cahier existe déjà
        $check = $conn->prepare(
            "SELECT id FROM cahiers_texte WHERE id_creneau = :id"
        );
        $check->execute([':id' => $data['id_creneau']]);
        if ($check->fetch()) {
            http_response_code(409);
            echo json_encode([
                "success" => false,
                "message" => "Un cahier existe déjà pour ce créneau"
            ]);
            exit();
        }

        // Créer le cahier
        $stmt = $conn->prepare(
            "INSERT INTO cahiers_texte 
             (id_creneau, id_delegue, titre_cours, contenu_json) 
             VALUES (:id_creneau, :id_delegue, :titre, :contenu)"
        );
        $stmt->execute([
            ':id_creneau' => $data['id_creneau'],
            ':id_delegue' => $utilisateur['id'],
            ':titre'      => $data['titre_cours'] ?? '',
            ':contenu'    => json_encode($data['contenu'] ?? [])
        ]);

        $id_cahier = $conn->lastInsertId();

        // Ajouter les travaux si fournis
        if (!empty($data['travaux']) && is_array($data['travaux'])) {
            foreach ($data['travaux'] as $travail) {
                $t = $conn->prepare(
                    "INSERT INTO travaux_demandes 
                     (id_cahier, description, date_limite, type) 
                     VALUES (:id_cahier, :desc, :date, :type)"
                );
                $t->execute([
                    ':id_cahier' => $id_cahier,
                    ':desc'      => $travail['description'],
                    ':date'      => $travail['date_limite'] ?? null,
                    ':type'      => $travail['type'] ?? 'devoir'
                ]);
            }
        }

        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Cahier de texte créé avec succès",
            "id"      => $id_cahier
        ]);
        break;

    // -----------------------------------------
    // PUT - Modifier un cahier
    // -----------------------------------------
    case 'PUT':
        $utilisateur = verifierRole(['delegue', 'admin']);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "ID du cahier requis"
            ]);
            exit();
        }

        // Vérifier que le cahier est encore modifiable
        $check = $conn->prepare(
            "SELECT statut FROM cahiers_texte WHERE id = :id"
        );
        $check->execute([':id' => $data['id']]);
        $cahier = $check->fetch();

        if ($cahier['statut'] === 'cloture') {
            http_response_code(403);
            echo json_encode([
                "success" => false,
                "message" => "Cahier clôturé — modification impossible"
            ]);
            exit();
        }

        $stmt = $conn->prepare(
            "UPDATE cahiers_texte 
             SET titre_cours = :titre, contenu_json = :contenu
             WHERE id = :id"
        );
        $stmt->execute([
            ':titre'   => $data['titre_cours'] ?? '',
            ':contenu' => json_encode($data['contenu'] ?? []),
            ':id'      => $data['id']
        ]);

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Cahier modifié avec succès"
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
