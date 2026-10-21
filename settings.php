<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/AppConfig.php';
$config = AppConfig::load();
$provider = (string)($config['ai_provider'] ?? 'auto');
$geminiModel = (string)($config['gemini_model'] ?? 'gemini-3.8-flash');
$geminiFallback = (string)($config['gemini_fallback_models'] ?? 'gemini-3.8-flash-lite');
$agnesModel = (string)($config['agnes_model'] ?? 'agnes-vision');
$agnesFallback = (string)($config['agnes_fallback_models'] ?? '');
$timeout = (int)($config['request_timeout'] ?? 30);
$geminiKey = (string)($config['gemini_api_key'] ?? '');
$agnesKey = (string)($config['agnes_api_key'] ?? '');
$geminiStatus = $geminiKey !== '' ? 'Key saved' : 'Not configured';
$agnesStatus = $agnesKey !== '' ? 'Key saved' : 'Not configured';
$baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? '/')), '/') . '/index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Scanner Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="page-body">
    <div class="settings-page container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="index.php" class="back-link">← Back to scanner</a>
                <h1 class="page-title mt-2">Settings</h1>
            </div>
        </div>

        <div class="settings-card">
            <form id="settingsForm" class="settings-form">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="aiProvider" class="form-label">AI Provider</label>
                        <select id="aiProvider" class="form-select" name="ai_provider">
                            <option value="auto" <?php echo $provider === 'auto' ? 'selected' : ''; ?>>Auto</option>
                            <option value="gemini" <?php echo $provider === 'gemini' ? 'selected' : ''; ?>>Gemini</option>
                            <option value="agnes" <?php echo $provider === 'agnes' ? 'selected' : ''; ?>>Agnes</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="requestTimeout" class="form-label">Request Timeout (seconds)</label>
                        <input id="requestTimeout" type="number" min="10" max="120" step="1" class="form-control" name="request_timeout" value="<?php echo htmlspecialchars((string)$timeout, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="geminiModel" class="form-label">Gemini Model</label>
                        <input id="geminiModel" type="text" class="form-control" name="gemini_model" value="<?php echo htmlspecialchars($geminiModel, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="geminiFallback" class="form-label">Gemini Fallback Models</label>
                        <textarea id="geminiFallback" class="form-control" name="gemini_fallback_models" rows="2"><?php echo htmlspecialchars($geminiFallback, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="agnesModel" class="form-label">Agnes Model</label>
                        <input id="agnesModel" type="text" class="form-control" name="agnes_model" value="<?php echo htmlspecialchars($agnesModel, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="agnesFallback" class="form-label">Agnes Fallback Models</label>
                        <textarea id="agnesFallback" class="form-control" name="agnes_fallback_models" rows="2"><?php echo htmlspecialchars($agnesFallback, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="geminiKey" class="form-label">Gemini API Key</label>
                        <div class="password-wrap">
                            <input id="geminiKey" type="password" class="form-control" name="gemini_api_key" value="<?php echo htmlspecialchars($geminiKey, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Paste key here">
                            <button type="button" class="toggle-password" data-target="geminiKey">Show</button>
                        </div>
                        <div class="field-status" data-field-status="gemini_api_key"><?php echo htmlspecialchars($geminiStatus, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>

                    <div class="col-md-6">
                        <label for="agnesKey" class="form-label">Agnes API Key</label>
                        <div class="password-wrap">
                            <input id="agnesKey" type="password" class="form-control" name="agnes_api_key" value="<?php echo htmlspecialchars($agnesKey, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Paste key here">
                            <button type="button" class="toggle-password" data-target="agnesKey">Show</button>
                        </div>
                        <div class="field-status" data-field-status="agnes_api_key"><?php echo htmlspecialchars($agnesStatus, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>

                <div class="settings-actions mt-4 d-flex flex-wrap gap-3">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                    <button type="button" id="testConnectionBtn" class="btn btn-outline-light">Test Connection</button>
                </div>
                <div id="connectionStatus" class="connection-status" aria-live="polite" hidden></div>
            </form>

            <div class="qr-panel mt-4">
                <h3>Open on Phone</h3>
                <div class="qr-box">
                    <img id="qrCode" alt="QR code for mobile scanner" src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?php echo rawurlencode($baseUrl); ?>">
                </div>
                <p>Scan this QR code using your phone.</p>
            </div>
        </div>
    </div>

    <div id="settingsToast" class="toast-message" aria-live="polite"></div>

    <script src="assets/js/settings.js"></script>
</body>
</html>
