<?php
// Paramètres de connexion
$host = 'localhost';
$dbname = 'projet_voyage'; // Le nom de la base que tu as créée
$user = 'root';            // L'utilisateur par défaut de XAMPP/LAMPP
$pass = '';                // Le mot de passe par défaut (souvent vide)

try {
    // Création de la connexion PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    
    // Configuration pour afficher les erreurs SQL (très utile pour le débogage !)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    // Si la connexion échoue, on arrête tout et on affiche l'erreur
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
