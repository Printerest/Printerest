
<?php
// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'No file uploaded or upload error';
    if (isset($_FILES['file']['error'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'File too large';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'File upload was interrupted';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = 'Missing temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = 'Upload stopped by extension';
                break;
        }
    }
    echo json_encode(['success' => false, 'error' => $error_message]);
    exit();
}

// Get file information
$file = $_FILES['file'];
$original_name = basename($file['name']);
$file_size = $file['size'];
$temp_path = $file['tmp_name'];

// Get target directory from POST data, default to 'uploads/files'
$target_directory = isset($_POST['target_directory']) ? $_POST['target_directory'] : 'uploads/files';

// Validate file extension
$allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
$file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    echo json_encode(['success' => false, 'error' => 'File type not allowed. Only PDF, DOC, DOCX, PPT, PPTX files are allowed.']);
    exit();
}

// Validate file size (max 10MB)
$max_file_size = 10 * 1024 * 1024; // 10MB in bytes
if ($file_size > $max_file_size) {
    echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 10MB.']);
    exit();
}

// Ensure the target directory exists
if (!file_exists($target_directory)) {
    if (!mkdir($target_directory, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
        exit();
    }
}

// Generate unique filename to prevent conflicts
$file_info = pathinfo($original_name);
$base_name = $file_info['filename'];
$extension = $file_info['extension'];
$unique_name = $base_name . '_' . time() . '_' . uniqid() . '.' . $extension;
$target_path = $target_directory . '/' . $unique_name;

// Move uploaded file to target location
if (move_uploaded_file($temp_path, $target_path)) {
    // File uploaded successfully
    $response = [
        'success' => true,
        'message' => 'File uploaded successfully',
        'file_path' => $target_path,
        'original_name' => $original_name,
        'unique_name' => $unique_name,
        'file_size' => $file_size,
        'upload_time' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file']);
}
?>
