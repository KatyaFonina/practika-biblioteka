<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/soap/LibraryDatabase.php';

$db = new LibraryDatabase();

$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$request_uri = str_replace('/rest-api.php/rest-api.php', '/rest-api.php', $request_uri);

$route = str_replace('/rest-api.php', '', $request_uri);
$route = trim($route, '/');

switch (true) {
    case $method == 'GET' && ($route == 'books' || $route == ''):
        try {
            $books = $db->getAllPhysicalBooks(); // Используем правильный метод
            
            echo json_encode([
                'success' => true,
                'count' => count($books),
                'data' => $books
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    case $method == 'POST' && $route == 'loan':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['inventory_number']) || !isset($input['reader_card'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Требуются inventory_number и reader_card'
            ]);
            break;
        }
        
        try {
            $result = $db->registerLoan($input['inventory_number'], $input['reader_card']);
            
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['message'],
                'loan_id' => $result['loan_id'] ?? null,
                'date_taken' => $result['date_taken'] ?? null
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    case $method == 'POST' && $route == 'return':
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['inventory_number'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Требуется inventory_number'
            ]);
            break;
        }
        
        try {
            $result = $db->returnBook($input['inventory_number']);
            
            echo json_encode([
                'success' => $result['success'],
                'message' => $result['message']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;
        
    default:
        if ($route == '') {
            echo json_encode([
                'service' => 'PHP REST API for Library System',
                'version' => '1.0',
                'endpoints' => [
                    'GET /books' => 'Get all physical books',
                    'POST /loan' => 'Borrow a book',
                    'POST /return' => 'Return a book'
                ]
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Endpoint not found',
                'requested' => $method . ' /' . $route
            ]);
        }
        break;
}
?>