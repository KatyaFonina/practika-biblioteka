<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/soap/LibraryDatabase.php';

$type = $_GET['type'] ?? 'overdue';
$format = $_GET['format'] ?? 'html'; // html или xml

$db = new LibraryDatabase();

$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><report></report>');
$xml->addChild('title', 'Отчет по библиотеке');
$xml->addChild('generated_date', date('Y-m-d H:i:s'));

if ($type == 'overdue') {
    $overdueBooks = $db->getOverdueBooks();
    $xml->addChild('type', 'overdue');
    $xml->addChild('count', count($overdueBooks));
    
    $booksNode = $xml->addChild('books');
    foreach ($overdueBooks as $book) {
        $bookNode = $booksNode->addChild('overdue_book');
        $bookNode->addChild('inventory_number', htmlspecialchars($book['inventory_number']));
        $bookNode->addChild('title', htmlspecialchars($book['title']));
        $bookNode->addChild('author', htmlspecialchars($book['author']));
        $bookNode->addChild('reader_card', htmlspecialchars($book['reader_card']));
        $bookNode->addChild('date_taken', $book['date_taken']);
        $bookNode->addChild('days_overdue', $book['days_overdue']);
    }
}

if ($format == 'xml') {
    header('Content-Type: text/xml; charset=utf-8');
    echo $xml->asXML();
    exit();
}

$xsl = new DOMDocument();
if (file_exists('report.xsl')) {
    $xsl->load('report.xsl');
} else {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Отчет библиотеки</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .overdue { color: red; font-weight: bold; }
        </style>
    </head>
    <body>
        <h1>Отчет о просроченных книгах</h1>
        <p>Сгенерирован: ' . date('Y-m-d H:i:s') . '</p>';
    
    if (!empty($overdueBooks)) {
        echo '<table>
                <tr>
                    <th>Инв. номер</th>
                    <th>Название</th>
                    <th>Автор</th>
                    <th>Читатель</th>
                    <th>Дата выдачи</th>
                    <th>Дней просрочки</th>
                </tr>';
        
        foreach ($overdueBooks as $book) {
            echo '<tr>
                    <td>' . htmlspecialchars($book['inventory_number']) . '</td>
                    <td>' . htmlspecialchars($book['title']) . '</td>
                    <td>' . htmlspecialchars($book['author']) . '</td>
                    <td>' . htmlspecialchars($book['reader_card']) . '</td>
                    <td>' . $book['date_taken'] . '</td>
                    <td class="overdue">' . $book['days_overdue'] . ' дней</td>
                  </tr>';
        }
        
        echo '</table>';
    } else {
        echo '<p>Просроченных книг нет!</p>';
    }
    
    echo '</body></html>';
    exit();
}

$proc = new XSLTProcessor();
$proc->importStyleSheet($xsl);

$xmlDoc = new DOMDocument();
$xmlDoc->loadXML($xml->asXML());

echo $proc->transformToXML($xmlDoc);
?>