<?php
date_default_timezone_set('Africa/Ouagadougou');
// =============================================
// EduSchedule Pro - Connexion Base de Données
// =============================================

class Database {
    // Paramètres de connexion
    private $host     = "localhost";
    private $db_name  = "eduschedule_pro";
    private $username = "root";
    private $password = "";
    private $conn     = null;

    // Méthode de connexion
    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . 
                ";dbname=" . $this->db_name . 
                ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            // Mode erreur : exceptions
            $this->conn->setAttribute(
                PDO::ATTR_ERRMODE, 
                PDO::ERRMODE_EXCEPTION
            );
            // Retourner les résultats en tableau associatif
            $this->conn->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE, 
                PDO::FETCH_ASSOC
            );
        } catch(PDOException $e) {
            echo json_encode([
                "success" => false,
                "message" => "Erreur de connexion : " . $e->getMessage()
            ]);
            die();
        }
        return $this->conn;
    }
}
?>