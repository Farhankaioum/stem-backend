<?php
include '../verify_token.php';

$method = $_SERVER['REQUEST_METHOD'];

$userFromToken = requireRole('learner');

if ($method == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get current user's profile
if ($method == 'GET') {
    try {
        // Get user ID from token
        $userId = $userFromToken['id'];
        
        $stmt = $pdo->prepare("SELECT id, username, email, role, created_at, is_active FROM users WHERE id = ?");
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
        echo json_encode(['error' => 'Failed to fetch user profile: ' . $e->getMessage()]);
    }
    exit;
}

// Update user profile (username, email)
if ($method == 'PUT') {
    try {
        // Get user ID from token
        $userId = $userFromToken['id'];
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        $username = $data['username'] ?? null;
        $email = $data['email'] ?? null;

        // Check if at least one field is provided
        if (!$username && !$email) {
            http_response_code(400);
            echo json_encode(['error' => 'At least one field (username or email) is required']);
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
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile: ' . $e->getMessage()]);
    }
    exit;
}

// Change password
if ($method == 'PATCH') {
    try {
        // Get user ID from token
        $userId = $userFromToken['id'];
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        $currentPassword = $data['current_password'] ?? null;
        $newPassword = $data['new_password'] ?? null;

        if (!$currentPassword || !$newPassword) {
            http_response_code(400);
            echo json_encode(['error' => 'Current password and new password are required']);
            exit;
        }

        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Current password is incorrect']);
            exit;
        }

        // Update password
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

// Delete own account
if ($method == 'DELETE') {
    try {
        // Get user ID from token
        $userId = $userFromToken['id'];
        
        $data = json_decode(file_get_contents("php://input"), true);
        $password = $data['password'] ?? null;

        if (!$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Password is required to delete account']);
            exit;
        }

        // Verify password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Password is incorrect']);
            exit;
        }

        // Delete user (or soft delete if preferred)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        http_response_code(200);
        echo json_encode(['message' => 'Account deleted successfully']);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete account: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>