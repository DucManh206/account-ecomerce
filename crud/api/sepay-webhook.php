<?php
/**
 * SePay Webhook Handler
 * 
 * Endpoint nhận thông báo từ SePay khi có giao dịch mới
 * URL: https://yourdomain.com/api/sepay-webhook.php
 * 
 * Hoặc có thể dùng endpoint để đồng bộ thủ công:
 * https://yourdomain.com/api/sepay-webhook.php?action=sync
 * 
 * Lưu ý: Thêm webhook URL này vào SePay Dashboard
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../sepay/sepay_modules.php';

$response = ['success' => false, 'message' => ''];

try {
    // Lấy cấu hình
    $config = sepay_getConfig();
    
    if (!$config || $config['status'] != 1) {
        $response['message'] = 'SePay is disabled';
        echo json_encode($response);
        exit;
    }
    
    // Lấy action
    $action = $_GET['action'] ?? '';
    
    // Xử lý đồng bộ thủ công (khi gọi từ cron hoặc admin)
    if ($action === 'sync') {
        $limit = intval($_GET['limit'] ?? 100);
        
        // Verify authorization for manual sync
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            if ($token !== $config['api_token']) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
        }
        
        $result = sepay_syncAndProcess($limit);
        
        if ($result['success']) {
            $response['success'] = true;
            $response['message'] = 'Sync completed';
            $response['data'] = $result;
        } else {
            $response['message'] = $result['message'];
        }
        
        echo json_encode($response);
        exit;
    }
    
    // Xử lý webhook từ SePay (POST request)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['transactions'])) {
            // Thử parse dạng form-data hoặc query string
            if (isset($_POST['transactions'])) {
                $data = ['transactions' => is_string($_POST['transactions']) ? json_decode($_POST['transactions'], true) : $_POST['transactions']];
            } else {
                $response['message'] = 'Invalid payload';
                echo json_encode($response);
                exit;
            }
        }
        
        $transactions = $data['transactions'] ?? [];
        
        if (empty($transactions)) {
            $response['message'] = 'No transactions in payload';
            echo json_encode($response);
            exit;
        }
        
        $processed = 0;
        $saved = 0;
        $errors = [];
        
        foreach ($transactions as $tx) {
            // Lưu giao dịch
            $saveResult = sepay_saveTransaction($tx);
            
            if (isset($saveResult['existed']) && $saveResult['existed']) {
                continue; // Bỏ qua nếu đã tồn tại
            }
            
            if (isset($saveResult['error'])) {
                $errors[] = $saveResult['error'];
                continue;
            }
            
            if (isset($saveResult['id'])) {
                $saved++;
                
                // Xử lý tự động nếu được bật
                if ($config['auto_process'] == 1) {
                    $processResult = sepay_processTransaction($saveResult['id']);
                    if ($processResult['success']) {
                        $processed++;
                    }
                }
            }
        }
        
        $response['success'] = true;
        $response['message'] = 'Webhook processed';
        $response['data'] = [
            'received' => count($transactions),
            'saved' => $saved,
            'processed' => $processed,
            'errors' => $errors
        ];
        
        echo json_encode($response);
        exit;
    }
    
    // GET request - trả thông tin
    $response['success'] = true;
    $response['message'] = 'SePay Webhook is active';
    $response['config'] = [
        'enabled' => (bool)$config['status'],
        'auto_process' => (bool)$config['auto_process'],
        'last_sync' => $config['last_sync_at']
    ];
    
    // Nếu có API token, lấy thêm thống kê
    if (!empty($config['api_token'])) {
        $stats = sepay_getStats();
        $response['stats'] = $stats;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Server error: ' . $e->getMessage();
    echo json_encode($response);
}
