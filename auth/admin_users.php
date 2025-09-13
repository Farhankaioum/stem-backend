<?php
include '../verify_token.php';

// Verify admin role
$admin = requireRole('admin');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get all users (with pagination and filtering)
if ($method == 'GET') {
    try {

        $query = "SELECT id, username, email, role, created_at, is_active FROM users ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'users' => $users
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch users: ' . $e->getMessage()]);
    }
    exit;
}

// Create new user (Admin can create users with any role)
if ($method == 'POST') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $username = $data['username'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $role = $data['role'] ?? 'learner';

        if (!$username || !$email || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Username, email, and password are required']);
            exit;
        }

        if (!in_array($role, ['admin', 'learner', 'instructor'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role. Use: admin, learner, or instructor']);
            exit;
        }

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Username or email already exists']);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $role]);
        
        $userId = $pdo->lastInsertId();
        
        // Get the created user (without password)
        $stmt = $pdo->prepare("SELECT id, username, email, role, created_at, is_active FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(201);
        echo json_encode([
            'message' => 'User created successfully',
            'user' => $user
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create user: ' . $e->getMessage()]);
    }
    exit;
}

// Update user (including role change and activation)
if ($method == 'PUT') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $userId = $data['id'] ?? null;
        $username = $data['username'] ?? null;
        $email = $data['email'] ?? null;
        $role = $data['role'] ?? null;
        $is_active = $data['is_active'] ?? null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required']);
            exit;
        }

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        $updateFields = [];
        $params = [];
        
        if ($username) {
            // Check if username is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $userId]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Username already taken']);
                exit;
            }
            $updateFields[] = "username = ?";
            $params[] = $username;
        }
        
        if ($email) {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Email already taken']);
                exit;
            }
            $updateFields[] = "email = ?";
            $params[] = $email;
        }
        
        if ($role) {
            if (!in_array($role, ['admin', 'learner', 'instructor'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid role']);
                exit;
            }
            $updateFields[] = "role = ?";
            $params[] = $role;
        }
        
        if ($is_active !== null) {
            $updateFields[] = "is_active = ?";
            $params[] = (bool)$is_active;
        }

        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }

        $params[] = $userId;
        $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        // Get updated user
        $stmt = $pdo->prepare("SELECT id, username, email, role, created_at, is_active FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'message' => 'User updated successfully',
            'user' => $user
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update user: ' . $e->getMessage()]);
    }
    exit;
}

// Change user password
if ($method == 'PATCH') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $userId = $data['id'] ?? null;
        $newPassword = $data['new_password'] ?? null;

        if (!$userId || !$newPassword) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID and new password are required']);
            exit;
        }

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);

        http_response_code(200);
        echo json_encode(['message' => 'Password updated successfully']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update password: ' . $e->getMessage()]);
    }
    exit;
}

// Delete user
if ($method == 'DELETE') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $userId = $data['id'] ?? null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID is required']);
            exit;
        }

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        http_response_code(200);
        echo json_encode(['message' => 'User deleted successfully']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete user: ' . $e->getMessage()]);
    }
    exit;
}

// Get single user by ID
if ($method == 'GET' && isset($_GET['id'])) {
    try {
        $userId = $_GET['id'];

        $stmt = $pdo->prepare("SELECT id, username, email, role, created_at, updated_at, is_active FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            http_response_code(200);
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch user: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>