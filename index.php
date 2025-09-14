<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KOI Education API</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .api-info {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #3498db;
        }
        
        .endpoints {
            margin-bottom: 30px;
        }
        
        .endpoint-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .endpoint-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .method {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.9em;
            margin-right: 10px;
        }
        
        .method.get { background: #61affe; color: white; }
        .method.post { background: #49cc90; color: white; }
        .method.put { background: #fca130; color: white; }
        .method.delete { background: #f93e3e; color: white; }
        
        .url {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 4px;
            margin: 10px 0;
            display: block;
        }
        
        .footer {
            text-align: center;
            padding: 30px;
            background: #2c3e50;
            color: white;
        }
        
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-left: 10px;
        }
        
        .status.live { background: #28a745; color: white; }
        .status.dev { background: #ffc107; color: black; }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 KOI Education API</h1>
            <p>RESTful API for Educational Program Management System</p>
            <span class="status live">LIVE</span>
        </div>
        
        <div class="content">
            <div class="api-info">
                <h2>📋 API Overview</h2>
                <p>This API provides complete management system for educational programs, user enrollment, and administration. Built with PHP, MySQL, and JWT authentication.</p>
                
                <p><strong>Base URL:</strong> <code><?php echo 'https://' . $_SERVER['HTTP_HOST']; ?>/</code></p>
                <p><strong>PHP Version:</strong> <code><?php echo phpversion(); ?></code></p>
                <p><strong>Server:</strong> <code><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></code></p>
            </div>
            
            <div class="endpoints">
                <h2>🔗 Available Endpoints</h2>
                
                <div class="endpoint-card">
                    <span class="method get">GET</span>
                    <strong>Program Management</strong>
                    <span class="url">/backend/features/program/</span>
                    <p>Get all programs or specific program by ID</p>
                </div>
                
                <div class="endpoint-card">
                    <span class="method post">POST</span>
                    <strong>Create/Update Program</strong>
                    <span class="url">/backend/features/program/</span>
                    <p>Create new program or update existing program (Admin only)</p>
                </div>
                
                <div class="endpoint-card">
                    <span class="method delete">DELETE</span>
                    <strong>Delete Program</strong>
                    <span class="url">/backend/features/program/</span>
                    <p>Delete a program (Admin only)</p>
                </div>
                
                <div class="endpoint-card">
                    <span class="method post">POST</span>
                    <strong>User Enrollment</strong>
                    <span class="url">/backend/features/enroll/</span>
                    <p>Enroll user in a program (One-time enrollment per program)</p>
                </div>
                
                <div class="endpoint-card">
                    <span class="method get">GET</span>
                    <strong>User Management</strong>
                    <span class="url">/backend/features/user/</span>
                    <p>User registration and profile management</p>
                </div>
                
                <div class="endpoint-card">
                    <span class="method get">GET</span>
                    <strong>Admin Management</strong>
                    <span class="url">/backend/features/admin/</span>
                    <p>Admin user management endpoints (Admin only)</p>
                </div>
            </div>
            
            <div class="api-info">
                <h2>🔐 Authentication</h2>
                <p>Protected endpoints require JWT authentication token in the header:</p>
                <p><code>Authorization: Bearer &lt;your_token&gt;</code></p>
                
                <h2>📝 Response Format</h2>
                <p>All responses are in JSON format with consistent structure:</p>
                <pre>{
    "status": "success|error",
    "message": "Descriptive message",
    "data": { ... }
}</pre>
            </div>
        </div>
        
        <div class="footer">
            <p>🚀 Built with PHP & MySQL | 📧 Support: admin@koi-education.com</p>
            <p>© 2024 KOI Education. All rights reserved.</p>
        </div>
    </div>
</body>
</html>