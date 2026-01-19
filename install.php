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
        <meta charset='utf-8'>
        <style>
            body { font-family: sans-serif; padding: 50px; text-align: center; background: #f5f5f5; }
            .message { background: white; padding: 40px; border-radius: 10px; max-width: 500px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #27ae60; }
            .btn { display: inline-block; margin: 10px; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
            .btn-danger { background: #e74c3c; }
        </style>
    </head>
    <body>
        <div class='message'>
            <h1>✅ Already Installed</h1>
            <p>Database has been installed successfully.</p>
            <p><a href='index.php' class='btn'>Go to Application</a></p>
            <hr>
            <p><strong>To reinstall:</strong></p>
            <p><small>Delete file: <code>.installed.lock</code></small></p>
            <form method='post' action='?action=reset' style='display: inline;'>
                <button type='submit' class='btn btn-danger' onclick='return confirm(\"Are you sure? This will allow reinstallation.\")'>Reset Installation</button>
            </form>
        </div>
    </body>
    </html>
    ");
}

// Handle reset
if (isset($_GET['action']) && $_GET['action'] === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
    header('Location: install.php');
    exit;
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
            max-width: 700px;
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
            font-size: 11px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }
        .log-line { margin: 3px 0; padding: 2px 0; }
        .log-success { color: #2ecc71; }
        .log-error { color: #e74c3c; }
        .log-info { color: #3498db; }
        .log-warning { color: #f39c12; }
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
            <p>Installing database, please wait...</p>
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
            document.getElementById('log').innerHTML = '';
            
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
                        <p><strong>📝 Default Login Credentials:</strong></p>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                            <p>Username: <code style="background: #fff; padding: 5px 10px; border-radius: 4px;">admin</code></p>
                            <p>Password: <code style="background: #fff; padding: 5px 10px; border-radius: 4px;">admin123</code></p>
                        </div>
                        <p style="margin-top: 15px;">
                            <a href="index.php" class="btn" style="text-decoration: none; display: inline-block; padding: 12px 30px;">Go to Application →</a>
                        </p>
                    `;
                    addLog('Installation completed successfully!', 'success');
                    if (data.logs && data.logs.length > 0) {
                        data.logs.forEach(log => {
                            if (log.includes('Error') || log.includes('Failed')) {
                                addLog(log, 'error');
                            } else if (log.includes('Warning')) {
                                addLog(log, 'warning');
                            } else {
                                addLog(log, 'success');
                            }
                        });
                    }
                } else {
                    result.className = 'error';
                    result.innerHTML = `
                        <h3>❌ Installation Failed</h3>
                        <p>${data.message}</p>
                        <div style="background: #fff; padding: 15px; border-radius: 8px; margin: 10px 0; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto;">
                            ${data.error ? data.error.replace(/\n/g, '<br>') : 'Unknown error'}
                        </div>
                        <p><small>Check the log below for more details.</small></p>
                    `;
                    addLog('Installation failed: ' + data.error, 'error');
                    if (data.logs && data.logs.length > 0) {
                        data.logs.forEach(log => addLog(log, 'warning'));
                    }
                    btn.disabled = false;
                }
            } catch (error) {
                loading.style.display = 'none';
                result.style.display = 'block';
                result.className = 'error';
                result.innerHTML = `
                    <h3>❌ Installation Error</h3>
                    <p>An unexpected error occurred.</p>
                    <div style="background: #fff; padding: 15px; border-radius: 8px; margin: 10px 0; font-family: monospace; font-size: 12px;">
                        ${error.message}
                    </div>
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
        $logs[] = "Connecting to MySQL server...";
        $conn = new mysqli($host, $user, $pass, $dbname, $port);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        $logs[] = "✓ Connected to database successfully";
        
        // Read SQL file
        $sql_file = __DIR__ . '/Database Schema.sql';
        if (!file_exists($sql_file)) {
            throw new Exception("Database Schema.sql file not found in: " . __DIR__);
        }
        
        $sql = file_get_contents($sql_file);
        $logs[] = "✓ Loaded database schema file (" . number_format(strlen($sql)) . " bytes)";
        
        // Clean up SQL file - remove BOM and problematic characters
        $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql); // Remove UTF-8 BOM
        $sql = str_replace(["\r\n", "\r"], "\n", $sql); // Normalize line endings
        
        // Split SQL into individual statements more carefully
        $statements = [];
        $current_statement = '';
        $in_string = false;
        $string_char = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            // Handle string literals
            if (($char === '"' || $char === "'") && ($i === 0 || $sql[$i-1] !== '\\')) {
                if (!$in_string) {
                    $in_string = true;
                    $string_char = $char;
                } elseif ($char === $string_char) {
                    $in_string = false;
                }
            }
            
            // Split on semicolon only if not in string
            if ($char === ';' && !$in_string) {
                $statements[] = trim($current_statement);
                $current_statement = '';
                continue;
            }
            
            $current_statement .= $char;
        }
        
        // Add last statement if exists
        if (trim($current_statement)) {
            $statements[] = trim($current_statement);
        }
        
        $logs[] = "✓ Parsed " . count($statements) . " SQL statements";
        
        // Execute SQL statements
        $success_count = 0;
        $error_count = 0;
        
        foreach ($statements as $index => $statement) {
            if (empty($statement)) continue;
            
            // Skip comments
            if (substr($statement, 0, 2) === '--' || substr($statement, 0, 1) === '#') {
                continue;
            }
            
            try {
                if ($conn->query($statement)) {
                    // Count CREATE TABLE statements
                    if (stripos($statement, 'CREATE TABLE') !== false) {
                        $tables_created++;
                        preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches);
                        if (isset($matches[1])) {
                            $logs[] = "✓ Created table: " . $matches[1];
                            $success_count++;
                        }
                    } elseif (stripos($statement, 'INSERT INTO') !== false) {
                        $success_count++;
                    }
                } else {
                    $error_code = $conn->errno;
                    $error_msg = $conn->error;
                    
                    // Only log real errors, ignore "already exists" warnings
                    if ($error_code != 1050 && $error_code != 1062) {
                        $error_count++;
                        $logs[] = "⚠ Error in statement " . ($index + 1) . ": " . $error_msg;
                        
                        // Show problematic statement (first 100 chars)
                        $preview = substr($statement, 0, 100);
                        if (strlen($statement) > 100) $preview .= '...';
                        $logs[] = "   Statement: " . $preview;
                    }
                }
            } catch (Exception $e) {
                $error_count++;
                $logs[] = "✗ Exception in statement " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        $logs[] = "✓ Execution complete: $success_count successful, $error_count errors";
        
        $conn->close();
        
        // Create lock file to prevent re-installation
        file_put_contents($lock_file, date('Y-m-d H:i:s') . "\nTables: $tables_created");
        $logs[] = "✓ Created installation lock file";
        
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
