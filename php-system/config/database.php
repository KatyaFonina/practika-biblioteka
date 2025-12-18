<?php
class DatabaseConfig {
    const DB_HOST = 'localhost';
    const DB_NAME = 'library_db';
    const DB_USER = 'root'; 
    const DB_PASS = '12345';     
    const DB_CHARSET = 'utf8mb4';
    
    public static function getConnection() {
        try {
            $dsn = "mysql:host=" . self::DB_HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::DB_CHARSET;
            $pdo = new PDO($dsn, self::DB_USER, self::DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            die("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }
}
?>