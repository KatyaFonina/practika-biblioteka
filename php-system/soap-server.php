<?php
header('Content-Type: text/xml; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('soap.wsdl_cache_enabled', '0');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (isset($_GET['wsdl'])) {
    header('Content-Type: text/xml; charset=utf-8');
    readfile('library.wsdl');
    exit();
}

require_once __DIR__ . '/soap/LibraryDatabase.php';

class BookService {
    private $db;
    
    public function __construct() {
        $this->db = new LibraryDatabase();
    }
    
    public function searchBooksByAuthor($author_name) {
        try {
            $books = $this->db->searchBooksByAuthor($author_name);
            
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><books></books>');
            
            foreach ($books as $book) {
                $bookNode = $xml->addChild('book');
                $bookNode->addChild('inventory_number', htmlspecialchars($book['inventory_number']));
                $bookNode->addChild('title', htmlspecialchars($book['title']));
                $bookNode->addChild('author', htmlspecialchars($book['author']));
                $bookNode->addChild('year', $book['year']);
                $bookNode->addChild('location', htmlspecialchars($book['location']));
                $bookNode->addChild('status', htmlspecialchars($book['status']));
            }
            
            return $xml->asXML();
            
        } catch (Exception $e) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><error></error>');
            $xml->addChild('message', htmlspecialchars($e->getMessage()));
            return $xml->asXML();
        }
    }
    
    public function getBookByInventory($inventory_number) {
        try {
            $book = $this->db->getBookByInventory($inventory_number);
            
            if (!$book) {
                $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><error></error>');
                $xml->addChild('message', "Книга с инвентарным номером $inventory_number не найдена");
                return $xml->asXML();
            }
            
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><book></book>');
            $xml->addChild('inventory_number', htmlspecialchars($book['inventory_number']));
            $xml->addChild('title', htmlspecialchars($book['title']));
            $xml->addChild('author', htmlspecialchars($book['author']));
            $xml->addChild('year', $book['year']);
            $xml->addChild('location', htmlspecialchars($book['location']));
            $xml->addChild('status', htmlspecialchars($book['status']));
            
            return $xml->asXML();
            
        } catch (Exception $e) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><error></error>');
            $xml->addChild('message', htmlspecialchars($e->getMessage()));
            return $xml->asXML();
        }
    }
    
    public function registerLoan($inventory_number, $reader_card) {
        try {
            $result = $this->db->registerLoan($inventory_number, $reader_card);
            
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><result></result>');
            $xml->addChild('success', $result['success'] ? 'true' : 'false');
            $xml->addChild('message', htmlspecialchars($result['message']));
            
            if ($result['success']) {
                $xml->addChild('loan_id', $result['loan_id']);
                $xml->addChild('inventory_number', htmlspecialchars($result['inventory_number']));
                $xml->addChild('date_taken', $result['date_taken']);
            }
            
            return $xml->asXML();
            
        } catch (Exception $e) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><result></result>');
            $xml->addChild('success', 'false');
            $xml->addChild('message', htmlspecialchars($e->getMessage()));
            return $xml->asXML();
        }
    }
    
    public function returnBook($inventory_number) {
        try {
            $result = $this->db->returnBook($inventory_number);
            
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><result></result>');
            $xml->addChild('success', $result['success'] ? 'true' : 'false');
            $xml->addChild('message', htmlspecialchars($result['message']));
            
            return $xml->asXML();
            
        } catch (Exception $e) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><result></result>');
            $xml->addChild('success', 'false');
            $xml->addChild('message', htmlspecialchars($e->getMessage()));
            return $xml->asXML();
        }
    }
}

try {
    if (!extension_loaded('soap')) {
        throw new Exception('SOAP расширение не загружено');
    }
    
    $server = new SoapServer('library.wsdl', [
        'uri' => 'urn:LibraryService',
        'encoding' => 'UTF-8',
        'soap_version' => SOAP_1_2
    ]);
    
    $server->setClass('BookService');
    
    $server->handle();
    
} catch (Exception $e) {
    header('Content-Type: text/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">';
    echo '<SOAP-ENV:Body>';
    echo '<SOAP-ENV:Fault>';
    echo '<faultcode>SOAP-ENV:Server</faultcode>';
    echo '<faultstring>' . htmlspecialchars($e->getMessage()) . '</faultstring>';
    echo '</SOAP-ENV:Fault>';
    echo '</SOAP-ENV:Body>';
    echo '</SOAP-ENV:Envelope>';
}
?>