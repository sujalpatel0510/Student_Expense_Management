<?php
/**
 * Database Connection & Initialization
 * Student Expense Tracker - College DBMS Project
 * Uses PHP Data Objects (PDO) with prepared statements for security.
 */

// Database Configuration parameters (Standard XAMPP Defaults)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'student_expense_tracker');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO Database Connection
 *
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // If database does not exist yet, attempt automatic creation for seamless demonstration
        if ($e->getCode() == 1049) { // 1049 = Unknown database
            try {
                $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
                $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                
                // Reconnect to newly created database
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                
                // Auto-seed schema and sample data if files exist
                $schemaFile = __DIR__ . '/../database/schema.sql';
                $sampleDataFile = __DIR__ . '/../database/sample_data.sql';
                
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $pdo->exec($sql);
                }
                
                if (file_exists($sampleDataFile)) {
                    $sampleSql = file_get_contents($sampleDataFile);
                    $pdo->exec($sampleSql);
                }
            } catch (PDOException $initEx) {
                die(renderDbError($initEx->getMessage()));
            }
        } else {
            die(renderDbError($e->getMessage()));
        }
    }

    return $pdo;
}

/**
 * Render user-friendly error page if MySQL is not running or credentials failed
 */
function renderDbError(string $error): string {
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error - Student Expense Tracker</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center min-vh-100">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-sm border-danger">
                        <div class="card-header bg-danger text-white">
                            <h4 class="mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i> Database Connection Error</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-danger fw-semibold">Could not establish connection to the MySQL database.</p>
                            <div class="alert alert-secondary text-monospace small">
                                ' . htmlspecialchars($error) . '
                            </div>
                            <h6 class="fw-bold mt-4">Troubleshooting Steps for College Demonstration:</h6>
                            <ol class="small text-muted">
                                <li>Ensure <strong>XAMPP Control Panel</strong> is open and <strong>Apache</strong> &amp; <strong>MySQL</strong> services are started (green).</li>
                                <li>Verify database credentials in <code>includes/db.php</code> (Default: Host=127.0.0.1, Port=3306, User=root, Password=&quot;&quot;).</li>
                                <li>Import <code>database/schema.sql</code> and <code>database/sample_data.sql</code> in phpMyAdmin if not already initialized.</li>
                            </ol>
                            <a href="" class="btn btn-outline-danger w-100 mt-2" onclick="location.reload();">Retry Connection</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';
}
