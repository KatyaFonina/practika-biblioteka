CREATE DATABASE IF NOT EXISTS library_db;

CREATE TABLE IF NOT EXISTS physical_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    year INT,
    location VARCHAR(100),
    status ENUM('available', 'borrowed', 'lost') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)

CREATE TABLE IF NOT EXISTS physical_loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    reader_card VARCHAR(50) NOT NULL,
    date_taken DATE NOT NULL,
    date_returned DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES physical_books(id) ON DELETE CASCADE,
    INDEX idx_book_id (book_id),
    INDEX idx_reader_card (reader_card)
) 