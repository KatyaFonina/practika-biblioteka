<?php
require_once __DIR__ . '/../config/database.php';
class LibraryDatabase {
    private $pdo;
    
   public function __construct() {
    $this->pdo = DatabaseConfig::getConnection();
    // Установка кодировки
    $this->pdo->exec("SET NAMES 'utf8mb4'");
    $this->pdo->exec("SET CHARACTER SET utf8mb4");
}
    
    /**
     * Получить книгу по инвентарному номеру
     */
    public function getBookByInventory($inventory_number) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM physical_books 
                WHERE inventory_number = :inventory_number
            ");
            $stmt->execute(['inventory_number' => $inventory_number]);
            $book = $stmt->fetch();
            
            return $book ?: null;
        } catch (PDOException $e) {
            error_log("Database error in getBookByInventory: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Поиск книг по автору
     */
    /**
 * Поиск книг по автору - УЛУЧШЕННАЯ ВЕРСИЯ
 */
public function searchBooksByAuthor($author_name) {
    try {
        // Нормализация и подготовка строки поиска
        $author_name = trim($author_name);
        $author_name = iconv('UTF-8', 'UTF-8//IGNORE', $author_name);
        
        // Разбиваем имя автора на части для поиска
        $searchParts = preg_split('/[\s,\-]+/', $author_name);
        $searchParts = array_filter($searchParts, function($part) {
            return strlen($part) > 1;
        });
        
        if (empty($searchParts)) {
            return [];
        }
        
        // Собираем разные варианты поиска
        $searchConditions = [];
        $params = [];
        
        // Вариант 1: Полное имя (точное совпадение)
        $searchConditions[] = "author LIKE :author_full";
        $params['author_full'] = "%{$author_name}%";
        
        // Вариант 2: Каждая часть имени отдельно
        foreach ($searchParts as $i => $part) {
            $paramName = "author_part_{$i}";
            $searchConditions[] = "author LIKE :{$paramName}";
            $params[$paramName] = "%{$part}%";
        }
        
        // Вариант 3: Русско-английские варианты
        $enVariants = $this->generateSearchVariants($author_name);
        foreach ($enVariants as $i => $variant) {
            $paramName = "author_en_{$i}";
            $searchConditions[] = "LOWER(author) LIKE LOWER(:{$paramName})";
            $params[$paramName] = "%{$variant}%";
        }
        
        // Формируем SQL запрос
        $sql = "SELECT DISTINCT * FROM physical_books WHERE ";
        $sql .= implode(" OR ", $searchConditions);
        $sql .= " ORDER BY author, title";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Database error in searchBooksByAuthor: " . $e->getMessage());
        return [];
    }
}

/**
 * Генерация вариантов поиска для русского и английского
 */
private function generateSearchVariants($author_name) {
    $variants = [];
    $lowerName = mb_strtolower($author_name, 'UTF-8');
    
    // Русские авторы
    $russianAuthors = [
        'агата' => 'agata',
        'булгаков' => 'bulgakov', 
        'лавкрафт' => 'lovecraft',
        'достоевский' => 'dostoevsky',
        'толстой' => 'tolstoy',
        'гоголь' => 'gogol',
        'пушкин' => 'pushkin',
        'лермонтов' => 'lermontov',
        'тургенев' => 'turgenev',
        'чехов' => 'chekhov',
        'набоков' => 'nabokov'
    ];
    
    // Английские авторы
    $englishAuthors = [
        'agata' => 'агата',
        'lovecraft' => 'лавкрафт',
        'christie' => 'кристи',
        'orwell' => 'оруэлл',
        'martin' => 'мартин',
        'rowling' => 'роулинг'
    ];
    
    // Проверяем русские имена
    foreach ($russianAuthors as $ru => $en) {
        if (mb_strpos($lowerName, $ru) !== false) {
            $variants[] = $en;
            $variants[] = $ru;
        }
    }
    
    // Проверяем английские имена  
    foreach ($englishAuthors as $en => $ru) {
        if (strpos($lowerName, $en) !== false) {
            $variants[] = $ru;
            $variants[] = $en;
        }
    }
    
    return array_unique($variants);
}
    private function transliterateRuToEn($string) {
    $ru = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
           'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
           'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
           'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'];
    $en = ['a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p',
           'r','s','t','u','f','h','ts','ch','sh','sch','','y','','e','yu','ya',
           'A','B','V','G','D','E','E','Zh','Z','I','Y','K','L','M','N','O','P',
           'R','S','T','U','F','H','Ts','Ch','Sh','Sch','','Y','','E','Yu','Ya'];
    
    return str_replace($ru, $en, $string);
}

private function transliterateEnToRu($string) {
    $en = ['agata', 'kristi', 'lovecraft', 'bulgakov', 'dostoevsky', 'tolstoy', 'gogol', 'pushkin', 'lermontov', 'turgenev'];
    $ru = ['агата', 'кристи', 'лавкрафт', 'булгаков', 'достоевский', 'толстой', 'гоголь', 'пушкин', 'лермонтов', 'тургенев'];
    
    return str_replace($en, $ru, strtolower($string));
}
    
    /**
     * Зарегистрировать выдачу книги
     */
    public function registerLoan($inventory_number, $reader_card) {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Проверяем существование книги
            $book = $this->getBookByInventory($inventory_number);
            if (!$book) {
                return [
                    'success' => false,
                    'message' => "Книга с инвентарным номером '$inventory_number' не найдена"
                ];
            }
            
            // 2. Проверяем статус книги
            if ($book['status'] !== 'available') {
                $status_msg = [
                    'borrowed' => 'уже выдана другому читателю',
                    'lost' => 'отмечена как утерянная'
                ];
                return [
                    'success' => false,
                    'message' => "Книга не может быть выдана: " . ($status_msg[$book['status']] ?? 'недоступна')
                ];
            }
            
            // 3. Проверяем читательский билет (минимальная проверка)
            if (empty($reader_card) || strlen($reader_card) < 3) {
                return [
                    'success' => false,
                    'message' => "Недействительный номер читательского билета"
                ];
            }
            
            // 4. Создаем запись о выдаче
            $stmt = $this->pdo->prepare("
                INSERT INTO physical_loans (book_id, reader_card, date_taken) 
                VALUES (:book_id, :reader_card, CURDATE())
            ");
            $stmt->execute([
                'book_id' => $book['id'],
                'reader_card' => $reader_card
            ]);
            $loan_id = $this->pdo->lastInsertId();
            
            // 5. Обновляем статус книги
            $stmt = $this->pdo->prepare("
                UPDATE physical_books 
                SET status = 'borrowed' 
                WHERE id = :book_id
            ");
            $stmt->execute(['book_id' => $book['id']]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => "Книга успешно выдана читателю $reader_card",
                'loan_id' => $loan_id,
                'inventory_number' => $inventory_number,
                'date_taken' => date('Y-m-d')
            ];
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Database error in registerLoan: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Ошибка базы данных: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Вернуть книгу
     */
    public function returnBook($inventory_number) {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Находим книгу
            $book = $this->getBookByInventory($inventory_number);
            if (!$book) {
                return [
                    'success' => false,
                    'message' => "Книга с инвентарным номером '$inventory_number' не найдена"
                ];
            }
            
            // 2. Находим активную выдачу
            $stmt = $this->pdo->prepare("
                SELECT * FROM physical_loans 
                WHERE book_id = :book_id AND date_returned IS NULL
                ORDER BY date_taken DESC LIMIT 1
            ");
            $stmt->execute(['book_id' => $book['id']]);
            $loan = $stmt->fetch();
            
            if (!$loan) {
                return [
                    'success' => false,
                    'message' => "Активная выдача для этой книги не найдена"
                ];
            }
            
            // 3. Обновляем дату возврата
            $stmt = $this->pdo->prepare("
                UPDATE physical_loans 
                SET date_returned = CURDATE() 
                WHERE id = :loan_id
            ");
            $stmt->execute(['loan_id' => $loan['id']]);
            
            // 4. Обновляем статус книги
            $stmt = $this->pdo->prepare("
                UPDATE physical_books 
                SET status = 'available' 
                WHERE id = :book_id
            ");
            $stmt->execute(['book_id' => $book['id']]);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => "Книга успешно возвращена в библиотеку"
            ];
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Database error in returnBook: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Ошибка базы данных: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Получить просроченные книги
     */
    public function getOverdueBooks() {
        try {
            // Книги, которые были взяты более 30 дней назад и еще не возвращены
            $stmt = $this->pdo->prepare("
                SELECT 
                    pb.inventory_number,
                    pb.title,
                    pb.author,
                    pl.reader_card,
                    pl.date_taken,
                    DATEDIFF(CURDATE(), pl.date_taken) as days_overdue
                FROM physical_loans pl
                JOIN physical_books pb ON pl.book_id = pb.id
                WHERE pl.date_returned IS NULL 
                AND DATEDIFF(CURDATE(), pl.date_taken) > 30
                ORDER BY days_overdue DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Database error in getOverdueBooks: " . $e->getMessage());
            return [];
        }
    }
    // В класс LibraryDatabase добавьте метод:
public function getAllPhysicalBooks() {
    try {
        $stmt = $this->conn->prepare("
            SELECT * FROM physical_books 
            ORDER BY title ASC
        ");
        $stmt->execute();
        
        $books = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $books[] = [
                'inventory_number' => $row['inventory_number'],
                'title' => $row['title'],
                'author' => $row['author'],
                'year' => $row['year'],
                'location' => $row['location'],
                'status' => $row['status']
            ];
        }
        
        return $books;
        
    } catch (PDOException $e) {
        throw new Exception("Database error: " . $e->getMessage());
    }
}
}
?>