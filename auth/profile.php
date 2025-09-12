<?php
include '../verify_token.php';

// This endpoint requires learner role
$user = requireRole('learner');

// If we get here, the user is authenticated and is an admin
http_response_code(200);
echo json_encode([
    'message' => 'Welcome learner!',
    'secret_data' => 'This is only visible to learner'
]);
?>