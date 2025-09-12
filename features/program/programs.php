<?php
include '../../verify_token.php';


$method = $_SERVER['REQUEST_METHOD'];
$generic_file_path = '/web-development/group_project/Group-Project-KOI/backend/features/program/';

if ($method == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Handle GET request (public access)
if ($method == 'GET') {
    try {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $stmt = $pdo->prepare("SELECT id, title, description, image_filename, created_at, updated_at FROM programs WHERE id = ?");
            $stmt->execute([$id]);
            $program = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($program) {
                if ($program['image_filename']) {
                    $program['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . $generic_file_path . UPLOAD_DIR . $program['image_filename'];
                }
                unset($program['image_filename']);
                
                http_response_code(200);
                echo json_encode($program);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Program not found']);
            }
        } else {
            // Get all programs
            $stmt = $pdo->query("SELECT id, title, description, image_filename, created_at, updated_at FROM programs ORDER BY created_at DESC");
            $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate full image URLs
            foreach ($programs as &$program) {
                if ($program['image_filename']) {
                    $program['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . $generic_file_path . UPLOAD_DIR . $program['image_filename'];
                }
                unset($program['image_filename']);
            }
            
            http_response_code(200);
            echo json_encode($programs);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch programs: ' . $e->getMessage()]);
    }
    exit;
}

$admin = requireRole('learner');
// Handle POST request (create new program)
if ($method == 'POST') {
    
    $id = $_POST['id'] ?? null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $image_filename = null;
    
    // Check if it's an update or create operation
    $is_update = !empty($id);
    
    if (empty($title) || empty($description)) {
        http_response_code(400);
        echo json_encode(['error' => 'Title and description are required']);
        exit;
    }
    
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['image'];
        $validation = validateFile($file);
        
        if (isset($validation['error'])) {
            http_response_code(400);
            echo json_encode(['error' => $validation['error']]);
            exit;
        }
        
        // Generate unique filename and move file
        $image_filename = generateFilename($file['name'], $validation['mime_type']);
        $destination = UPLOAD_DIR . $image_filename;
        
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save uploaded file']);
            exit;
        }
    }
    
    try {
        if ($is_update) {
            $current_image_filename = null;
            
            // Get current image filename if exists
            $stmt = $pdo->prepare("SELECT image_filename FROM programs WHERE id = ?");
            $stmt->execute([$id]);
            $currentProgram = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentProgram) {
                http_response_code(404);
                echo json_encode(['error' => 'Program not found']);
                exit;
            }
            
            $current_image_filename = $currentProgram['image_filename'];
            
            $final_image_filename = $image_filename ? $image_filename : $current_image_filename;
            
            $stmt = $pdo->prepare("UPDATE programs SET title = ?, description = ?, image_filename = ? WHERE id = ?");
            $stmt->execute([$title, $description, $final_image_filename, $id]);
            
            if ($stmt->rowCount() > 0) {
                if ($image_filename && $current_image_filename && file_exists(UPLOAD_DIR . $current_image_filename)) {
                    unlink(UPLOAD_DIR . $current_image_filename);
                }
                
                $stmt = $pdo->prepare("SELECT id, title, description, image_filename, created_at, updated_at FROM programs WHERE id = ?");
                $stmt->execute([$id]);
                $program = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Generate full image URL if image exists
                if ($program['image_filename']) {
                    $program['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . $generic_file_path . UPLOAD_DIR . $program['image_filename'];
                }
                unset($program['image_filename']);
                
                http_response_code(200);
                echo json_encode(['message' => 'Program updated successfully', 'program' => $program]);
            } else {
                if ($image_filename && file_exists(UPLOAD_DIR . $image_filename)) {
                    unlink(UPLOAD_DIR . $image_filename);
                }
                
                http_response_code(404);
                echo json_encode(['error' => 'Program not found or no changes made']);
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO programs (title, description, image_filename) VALUES (?, ?, ?)");
            $stmt->execute([$title, $description, $image_filename]);
            
            $programId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT id, title, description, image_filename, created_at, updated_at FROM programs WHERE id = ?");
            $stmt->execute([$programId]);
            $program = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($program['image_filename']) {
                $program['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . $generic_file_path . UPLOAD_DIR . $program['image_filename'];
            }
            unset($program['image_filename']);
            
            http_response_code(201);
            echo json_encode(['message' => 'Program created successfully', 'program' => $program]);
        }
    } catch (PDOException $e) {
        if ($image_filename && file_exists(UPLOAD_DIR . $image_filename)) {
            unlink(UPLOAD_DIR . $image_filename);
        }
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to ' . ($is_update ? 'update' : 'create') . ' program: ' . $e->getMessage()]);
    }
    exit;
}


// Handle DELETE request (delete program)
if ($method == 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Program ID is required']);
        exit;
    }
    
    $id = $data['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT image_filename FROM programs WHERE id = ?");
        $stmt->execute([$id]);
        $program = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($program) {
            $stmt = $pdo->prepare("DELETE FROM programs WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                // Delete associated image if it exists
                if ($program['image_filename'] && file_exists(UPLOAD_DIR . $program['image_filename'])) {
                    unlink(UPLOAD_DIR . $program['image_filename']);
                }
                
                http_response_code(200);
                echo json_encode(['message' => 'Program deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Program not found']);
            }
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Program not found']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete program: ' . $e->getMessage()]);
    }
    exit;
}

// If method not supported
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>