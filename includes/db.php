<?php
/**
 * Database Layer — PDO Connection & Helpers
 * InfinityFree Compatible with Enhanced Error Handling
 * FIXED: Added db() and db_run() functions
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('DB_HOST')) {
    die('Configuration not loaded. Include config.php first.');
}

// ─────────────────────────────────────────────────────────────
//  GLOBAL PDO INSTANCE
// ─────────────────────────────────────────────────────────────
$db = null;

/**
 * Get or create PDO connection (Singleton pattern)
 * Enhanced with InfinityFree-specific error handling
 * 
 * @return PDO
 * @throws Exception if connection fails after retries
 */
function db_connect(): PDO
{
    global $db;
    
    if ($db !== null) {
        return $db;
    }
    
    $maxRetries = 3;
    $retryDelay = 2; // seconds
    $lastError = null;
    
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET ?? 'utf8mb4'
            );
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 10,  // 10 second timeout for InfinityFree
                PDO::MYSQL_ATTR_INIT_COMMAND => sprintf(
                    "SET NAMES %s COLLATE %s_unicode_ci",
                    DB_CHARSET ?? 'utf8mb4',
                    DB_CHARSET ?? 'utf8mb4'
                ),
            ];
            
            $db = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Test the connection with a simple query
            $db->query('SELECT 1');
            
            // Success! Log if this wasn't first attempt
            if ($attempt > 1) {
                error_log("[DB] Connection succeeded on attempt $attempt/$maxRetries");
            }
            
            return $db;
            
        } catch (PDOException $e) {
            $lastError = $e;
            
            // Log the error with sanitized info (no password)
            $errorMsg = sprintf(
                "[DB] Connection attempt %d/%d failed. Host: %s, DB: %s, User: %s, Error: %s",
                $attempt,
                $maxRetries,
                DB_HOST,
                DB_NAME,
                DB_USER,
                $e->getMessage()
            );
            error_log($errorMsg);
            
            // Don't retry on certain errors
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            // Authentication failure - no point retrying
            if (
                $errorCode === 1045 || 
                strpos($errorMessage, 'Access denied') !== false ||
                strpos($errorMessage, 'Unknown database') !== false
            ) {
                break;
            }
            
            // Wait before retry (except on last attempt)
            if ($attempt < $maxRetries) {
                sleep($retryDelay);
            }
        }
    }
    
    // All attempts failed - provide helpful error message
    $userMessage = 'Database connection error. ';
    
    if ($lastError) {
        $errorMsg = $lastError->getMessage();
        $errorCode = $lastError->getCode();
        
        // Provide specific guidance based on error type
        if ($errorCode === 1045 || strpos($errorMsg, 'Access denied') !== false) {
            $userMessage .= 'Invalid database credentials. Please check your config.php file.';
        } elseif (strpos($errorMsg, 'Unknown database') !== false) {
            $userMessage .= 'Database does not exist. Please create it in your hosting control panel.';
        } elseif (strpos($errorMsg, 'Connection refused') !== false) {
            $userMessage .= 'Cannot reach database server. Please verify the hostname in config.php.';
        } elseif (strpos($errorMsg, 'Connection timed out') !== false) {
            $userMessage .= 'Database server not responding. This might be a temporary hosting issue.';
        } else {
            $userMessage .= 'Technical error: ' . htmlspecialchars($errorMsg);
        }
        
        // Add troubleshooting tip
        $userMessage .= '<br><br><strong>Troubleshooting Tips:</strong><ul style="text-align:left;margin:10px 20px;">';
        $userMessage .= '<li>Upload and run <code>db_test.php</code> to diagnose the issue</li>';
        $userMessage .= '<li>Verify database credentials in your hosting control panel</li>';
        $userMessage .= '<li>Try changing DB_HOST to "localhost" in config.php</li>';
        $userMessage .= '<li>Wait 5-10 minutes if this is a temporary hosting issue</li>';
        $userMessage .= '<li>Check if your database user has been added to the database with ALL PRIVILEGES</li>';
        $userMessage .= '</ul>';
    }
    
    // Display error page
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error</title>
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
            .error-box {
                background: white;
                border-radius: 16px;
                padding: 2.5rem;
                max-width: 600px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                text-align: center;
            }
            .error-icon {
                width: 80px;
                height: 80px;
                background: #fee;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 1.5rem;
                font-size: 2.5rem;
            }
            h1 {
                color: #dc2626;
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            p {
                color: #6b7280;
                line-height: 1.6;
                margin-bottom: 1.5rem;
            }
            code {
                background: #f3f4f6;
                padding: 2px 8px;
                border-radius: 4px;
                font-family: 'Courier New', monospace;
                font-size: 0.9em;
                color: #dc2626;
            }
            ul {
                color: #374151;
                font-size: 0.9rem;
                line-height: 1.8;
            }
            ul li {
                margin-bottom: 0.5rem;
            }
            .btn {
                display: inline-block;
                background: #667eea;
                color: white;
                padding: 0.75rem 1.5rem;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                margin-top: 1rem;
                transition: background 0.2s;
            }
            .btn:hover {
                background: #5568d3;
            }
            .debug-info {
                background: #fef3c7;
                border: 1px solid #fbbf24;
                border-radius: 8px;
                padding: 1rem;
                margin-top: 1.5rem;
                font-size: 0.85rem;
                color: #78350f;
                text-align: left;
            }
            .debug-info strong {
                display: block;
                margin-bottom: 0.5rem;
            }
        </style>
    </head>
    <body>
        <div class="error-box">
            <div class="error-icon">🔌</div>
            <h1>Database Connection Error</h1>
            <p><?php echo $userMessage; ?></p>
            
            <?php if (ini_get('display_errors') === '1'): ?>
            <div class="debug-info">
                <strong>⚠️ Debug Information (visible because display_errors is ON):</strong>
                Host: <?php echo htmlspecialchars(DB_HOST); ?><br>
                Database: <?php echo htmlspecialchars(DB_NAME); ?><br>
                Username: <?php echo htmlspecialchars(DB_USER); ?><br>
                Attempts: <?php echo $maxRetries; ?><br>
                Last Error Code: <?php echo htmlspecialchars((string)($lastError ? $lastError->getCode() : 'N/A')); ?>
            </div>
            <?php endif; ?>
            
            <a href="javascript:location.reload()" class="btn">🔄 Try Again</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ─────────────────────────────────────────────────────────────
//  HELPER FUNCTIONS
// ─────────────────────────────────────────────────────────────

/**
 * Shorthand for db_connect() - returns PDO instance
 * FIXED: Added this function
 */
function db(): PDO
{
    return db_connect();
}

/**
 * Execute query and return single row (or null)
 */
function db_row(string $sql, array $params = []): ?array
{
    $pdo = db_connect();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Execute query and return all rows
 */
function db_all(string $sql, array $params = []): array
{
    $pdo = db_connect();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Execute INSERT/UPDATE/DELETE and return affected rows
 * FIXED: Renamed from db_exec to db_run (primary function)
 */
function db_run(string $sql, array $params = []): int
{
    $pdo = db_connect();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Backward compatibility alias for db_run
 */
function db_exec(string $sql, array $params = []): int
{
    return db_run($sql, $params);
}

/**
 * Get last insert ID after INSERT
 */
function db_insert_id(): int
{
    $pdo = db_connect();
    return (int) $pdo->lastInsertId();
}

/**
 * Alias for db_insert_id (common naming convention)
 */
function db_last_id(): int
{
    return db_insert_id();
}

/**
 * Begin transaction
 */
function db_begin(): void
{
    $pdo = db_connect();
    $pdo->beginTransaction();
}

/**
 * Commit transaction
 */
function db_commit(): void
{
    $pdo = db_connect();
    $pdo->commit();
}

/**
 * Rollback transaction
 */
function db_rollback(): void
{
    $pdo = db_connect();
    $pdo->rollBack();
}

// ─────────────────────────────────────────────────────────────
//  AUTO-CONNECT (with error handling)
// ─────────────────────────────────────────────────────────────
// Attempt to establish connection when this file is included
// This will trigger the error page immediately if connection fails
try {
    db_connect();
} catch (Exception $e) {
    // Error page already displayed in db_connect()
    exit;
}
