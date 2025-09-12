# Readme for STEM Backend

🛠️ Prerequisites
Before running this project, ensure you have the following installed:

XAMPP (Apache + MySQL + PHP)

Composer (PHP dependency manager)

Postman (for API testing - optional but recommended)

📋 Installation Guide
1. Install XAMPP
Download XAMPP from https://www.apachefriends.org/

Install it in your preferred directory (usually C:\xampp)

Start Apache and MySQL from the XAMPP Control Panel

2. Install Composer
Download Composer from https://getcomposer.org/

Run the installer and follow the instructions

Verify installation by opening command prompt and typing using bash
composer --version

3. Project Setup
Clone or download the project files

Place the project in your XAMPP htdocs folder:

C:\xampp\htdocs\your-project-folder\
Or create a virtual host if preferred

4. Database Setup
Open phpMyAdmin (http://localhost/phpmyadmin)

Create a new database called stem_db

Import the SQL schema: You will find all the schema in every feature's folder

5. Install Dependencies
Open command prompt in your project root directory

Run using bash
composer install
This will install all required PHP packages including: Firebase JWT for authentication

PHP Dotenv for environment variables (if used)

6. Configuration
Update database credentials in your connection file (usually in config/database.php or similar):

$host = 'localhost';
$dbname = 'stem_db';
$username = 'root';
$password = ''; // XAMPP default is empty
Create uploads directory with proper permissions using bash
mkdir uploads
chmod 755 uploads

🚀 Running the Project
Start Services
Open XAMPP Control Panel

Start Apache and MySQL services

Verify Apache is running by visiting: http://localhost/

Access the API
The API endpoints will be available at:

http://localhost/your-project-folder/features/program/
📊 API Endpoints
Public Endpoints (No authentication required)
GET /program/ - Get all programs

GET /program/?id=1 - Get specific program

Protected Endpoints (Require JWT token)
POST /program/ - Create or update program

DELETE /program/ - Delete program

🔐 Authentication
This API uses JWT (JSON Web Tokens) for authentication. Include the token in the Authorization header:

text
Authorization: Bearer your-jwt-token-here
🧪 Testing with Postman
1. Import Postman Collection
Open Postman

Click "Import" and select your collection JSON file (if available)

2. Manual Testing
Create a Program:

Method: POST

URL: http://localhost/your-project-folder/features/program/

Headers:

Authorization: Bearer your-token

Body: form-data with:

3. Example Request Body
json
{
    "title": "Web Development Bootcamp",
    "description": "Learn full-stack development",
    "min_age": 16,
    "max_age": 25,
    "duration_value": 12,
    "duration_unit": "weeks",
    "price": 499.99,
    "schedule": "Mon, Wed, Fri 6-9PM",
    "learning_outcomes": "[\"HTML/CSS\", \"JavaScript\", \"React\", \"Node.js\"]"
}
🗂️ Project Structure

your-project-folder/
├── backend/
│   ├── features/
│   │   └── program/
│   │       ├── program.php (main API file)
│   │       ├── verify_token.php
│   │       └── uploads/ (image storage)
├── vendor/ (composer dependencies)
├── composer.json
└── composer.lock
⚠️ Troubleshooting
Common Issues:
"Access denied for user 'root'@'localhost'"

Check MySQL credentials in your connection file

Verify MySQL is running in XAMPP

"File upload failed"

Check uploads directory permissions

Verify directory exists and is writable

"Composer not recognized"

Restart command prompt after Composer installation

Check Composer is in your system PATH

"JWT token invalid"

Verify token generation and validation logic

Check token expiration

"404 Not Found"

Verify Apache is running

Check project is in correct htdocs folder

Verify file paths in your requests

Debug Mode
For development, you might want to enable error reporting by adding this to your PHP files:

error_reporting(E_ALL);
ini_set('display_errors', 1);
📞 Support
If you encounter any issues:

Check the troubleshooting section above

Verify all prerequisites are installed correctly

Ensure file paths and database credentials are correct

Check XAMPP error logs: C:\xampp\apache\logs\error.log

🔄 Updates
To update dependencies using bash
composer update

📝 License
This project is for educational purposes as part of the Group-Project-KOI.
