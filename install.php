<?php
/**
 * Smart Resto POS - Auto Database Installer for Railway
 * Visit: https://your-app.railway.app/install.php
 */

// Prevent running if already installed
$lock_file = __DIR__ . '/.installed.lock';
if (file_exists($lock_file)) {
    die("
    <!DOCTYPE html>
    <html>
    <head>
        <title>Already Installed</title>
        <style>
            body { font-family: sans-serif; padding: 50px; text-align: center; background: #f5f5f5; }
            .message { background: white; padding: 40px; border-radius: 10px; max-width: 500px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #27ae60; }
        </style>
    </head>
    <body>
        <div class='message'>
            <h1>✅ Already Installed</h1>
            <p>Database has been installed successfully.</p>
            <p><a href='index.php'>Go to Application</a></p>
            <hr>
            <p><small>To reinstall, delete file: <code>.installed.lock</code></small></p>
        </div>
    </body>
    </html>
    ");
}

// Get Railway MySQL credentials
$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$user = getenv('MYSQLUSER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: '';
$dbname = getenv('MYSQLDATABASE') ?: 'railway';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Resto POS - Database Installer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        h1 { color: #333; margin-bottom: 10px; font-size: 28px; }
        h2 { color: #666; font-size: 16px; font-weight: normal; margin-bottom: 30px; }
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        .info-box strong { color: #2c3e50; display: block; margin-bottom: 10px; }
        .info-box code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            color: #495057;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            text-align: center;
        }
        .btn:hover { background: #2980b9; transform: translateY(-2px); }
        .btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        #result {
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
            display: none;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .loading {
            display: none;
            text-align: center;
            margin-top: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .log { 
            background: #2c3e50; 
            color: #ecf0f1; 
            padding: 15px; 
            border-radius: 8px; 
            margin-top: 15px; 
            font-family: 'Courier New', monospace; 
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        .log-line { margin: 5px 0; }
        .log-success { color: #2ecc71; }
        .log-error { color: #e74c3c; }
        .log-info { color: #3498db; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🍽️ Smart Resto POS</h1>
        <h2>Database Installation Wizard</h2>

        <div class="info-box">
            <strong>📊 Database Configuration:</strong>
            Host: <code><?php echo htmlspecialchars($host); ?></code><br>
            Port: <code><?php echo htmlspecialchars($port); ?></code><br>
            Database: <code><?php echo htmlspecialchars($dbname); ?></code><br>
            User: <code><?php echo htmlspecialchars($user); ?></code>
        </div>

        <div class="info-box" style="border-left-color: #e74c3c;">
            <strong>⚠️ Important:</strong>
            This will create all necessary database tables. Make sure you have backup if reinstalling.
        </div>

        <button class="btn" id="installBtn" onclick="installDatabase()">
            🚀 Install Database
        </button>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Installing database...</p>
        </div>

        <div id="result"></div>
        <div class="log" id="log"></div>
    </div>

    <script>
        function addLog(message, type = 'info') {
            const log = document.getElementById('log');
            log.style.display = 'block';
            const line = document.createElement('div');
            line.className = 'log-line log-' + type;
            line.textContent = '> ' + message;
            log.appendChild(line);
            log.scrollTop = log.scrollHeight;
        }

        async function installDatabase() {
            const btn = document.getElementById('installBtn');
            const loading = document.getElementById('loading');
            const result = document.getElementById('result');
            
            btn.disabled = true;
            loading.style.display = 'block';
            result.style.display = 'none';
            
            addLog('Starting database installation...', 'info');

            try {
                const response = await fetch('?action=install', {
                    method: 'POST'
                });
                
                const data = await response.json();
                
                loading.style.display = 'none';
                result.style.display = 'block';
                
                if (data.success) {
                    result.className = 'success';
                    result.innerHTML = `
                        <h3>✅ Installation Successful!</h3>
                        <p>${data.message}</p>
                        <p><strong>Tables created:</strong> ${data.tables_created}</p>
                        <hr style="margin: 15px 0;">
                        <p><strong>Default Login:</strong></p>
                        <p>Username: <code>admin</code></p>
                        <p>Password: <code>admin123</code></p>
                        <p style="margin-top: 15px;">
                            <a href="index.php" class="btn">Go to Application →</a>
                        </p>
                    `;
                    addLog('Installation completed successfully!', 'success');
                    data.logs.forEach(log => addLog(log, 'success'));
                } else {
                    result.className = 'error';
                    result.innerHTML = `
                        <h3>❌ Installation Failed</h3>
                        <p>${data.message}</p>
                        <p><small>${data.error}</small></p>
                    `;
                    addLog('Installation failed: ' + data.error, 'error');
                    btn.disabled = false;
                }
            } catch (error) {
                loading.style.display = 'none';
                result.style.display = 'block';
                result.className = 'error';
                result.innerHTML = `
                    <h3>❌ Installation Error</h3>
                    <p>An unexpected error occurred.</p>
                    <p><small>${error.message}</small></p>
                `;
                addLog('Error: ' + error.message, 'error');
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>

<?php
// Handle installation request
if (isset($_GET['action']) && $_GET['action'] === 'install') {
    header('Content-Type: application/json');
    
    $logs = [];
    $tables_created = 0;
    
    try {
        // Connect to database
        $conn = new mysqli($host, $user, $pass, $dbname, $port);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        $logs[] = "Connected to database successfully";
        
        // Read SQL file
        $sql_file = __DIR__ . '/Database Schema.sql';
        if (!file_exists($sql_file)) {
            throw new Exception("Database Schema.sql file not found!");
        }
        
        $sql = file_get_contents($sql_file);
        $logs[] = "Loaded database schema";
        
        // Execute SQL statements
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            if ($conn->query($statement)) {
                // Count CREATE TABLE statements
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    $tables_created++;
                    preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                    if (isset($matches[1])) {
                        $logs[] = "Created table: " . $matches[1];
                    }
                }
            } else {
                // Only log actual errors, not warnings
                if ($conn->errno != 1050) { // Ignore "table already exists" error
                    $logs[] = "Warning: " . $conn->error;
                }
            }
        }
        
        $conn->close();
        
        // Create lock file to prevent re-installation
        file_put_contents($lock_file, date('Y-m-d H:i:s'));
        $logs[] = "Created installation lock file";
        
        echo json_encode([
            'success' => true,
            'message' => 'Database installed successfully!',
            'tables_created' => $tables_created,
            'logs' => $logs
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Installation failed',
            'error' => $e->getMessage(),
            'logs' => $logs
        ]);
    }
    
    exit;
}
?>
