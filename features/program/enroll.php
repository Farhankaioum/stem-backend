<?php
include '../../verify_token.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$learnerRole = requireRole('admin');

// Get all enrollments for a user
if ($method == 'GET' && isset($_GET['user_enrollments'])) {
    try {
        $user_id = $_GET['user_id'] ?? null;

        if (!$user_id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT pe.*, p.title, p.description, p.image_filename, p.price, p.schedule
            FROM program_enrollments pe 
            JOIN programs p ON pe.program_id = p.id 
            WHERE pe.user_id = ? 
            ORDER BY pe.enrolled_at DESC
        ");
        $stmt->execute([$user_id]);
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate image URLs
        foreach ($enrollments as &$enrollment) {
            if ($enrollment['image_filename']) {
                $enrollment['image_url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $enrollment['image_filename'];
            }
            unset($enrollment['image_filename']);
        }

        http_response_code(200);
        echo json_encode([
            'user_id' => $user_id,
            'total_enrollments' => count($enrollments),
            'enrollments' => $enrollments
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch user enrollments: ' . $e->getMessage()]);
    }
    exit;
}

// Get user enrollment status for a program
if ($method == 'GET') {
    try {
        $user_id = $_GET['user_id'] ?? null;
        $program_id = $_GET['program_id'] ?? null;

        if (!$user_id || !$program_id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID and Program ID are required']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT pe.*, p.title as program_title 
            FROM program_enrollments pe 
            JOIN programs p ON pe.program_id = p.id 
            WHERE pe.user_id = ? AND pe.program_id = ?
        ");
        $stmt->execute([$user_id, $program_id]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($enrollment) {
            http_response_code(200);
            echo json_encode([
                'is_enrolled' => true,
                'enrollment' => $enrollment
            ]);
        } else {
            http_response_code(200);
            echo json_encode([
                'is_enrolled' => false,
                'message' => 'User is not enrolled in this program'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to check enrollment: ' . $e->getMessage()]);
    }
    exit;
}

// Enroll user in a program
if ($method == 'POST') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $user_id = $data['user_id'] ?? null;
        $program_id = $data['program_id'] ?? null;

        if (!$user_id || !$program_id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID and Program ID are required']);
            exit;
        }

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        // Check if program exists
        $stmt = $pdo->prepare("SELECT id FROM programs WHERE id = ?");
        $stmt->execute([$program_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Program not found']);
            exit;
        }

        // Check if already enrolled
        $stmt = $pdo->prepare("SELECT id FROM program_enrollments WHERE user_id = ? AND program_id = ?");
        $stmt->execute([$user_id, $program_id]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'User is already enrolled in this program']);
            exit;
        }  

        // Create enrollment
        $stmt = $pdo->prepare("INSERT INTO program_enrollments (user_id, program_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $program_id]);
        
        $enrollment_id = $pdo->lastInsertId();
        
        // Get full enrollment details
        $stmt = $pdo->prepare("
            SELECT pe.*, p.title as program_title, p.description as program_description
            FROM program_enrollments pe 
            JOIN programs p ON pe.program_id = p.id 
            WHERE pe.id = ?
        ");
        $stmt->execute([$enrollment_id]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(201);
        echo json_encode([
            'message' => 'Successfully enrolled in program',
            'enrollment' => $enrollment
        ]);

    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            http_response_code(409);
            echo json_encode(['error' => 'User is already enrolled in this program']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to enroll: ' . $e->getMessage()]);
        }
    }
    exit;
}

// Cancel enrollment
if ($method == 'DELETE') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $user_id = $data['user_id'] ?? null;
        $program_id = $data['program_id'] ?? null;

        if (!$user_id || !$program_id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID and Program ID are required']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM program_enrollments WHERE user_id = ? AND program_id = ?");
        $stmt->execute([$user_id, $program_id]);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['message' => 'Successfully canceled enrollment']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Enrollment not found']);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to cancel enrollment: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>