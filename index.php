<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/AppConfig.php';
$config = AppConfig::load();
$siteName = 'AI + AR Object Recognition System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; media-src 'self' blob: https:; connect-src 'self' https:;">
    <title><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <div class="brand-wrap">
                <span class="brand-text">AI + AR</span>
            </div>
            <a href="settings.php" class="settings-button" aria-label="Open settings">
                <i class="fa-solid fa-gear"></i>
            </a>
        </header>

        <main class="camera-stage">
            <div class="camera-wrapper">
                <video id="cameraPreview" autoplay playsinline muted aria-label="Camera preview"></video>
                <div id="cameraOverlay" class="camera-overlay" aria-live="polite"></div>
                <div id="resultCard" class="result-card hidden" tabindex="0" aria-live="polite">
                    <div id="resultCardSummary" class="result-card-summary">
                        <span class="result-label">Product Name</span>
                        <strong id="resultCardTitle">Tap for details</strong>
                    </div>
                    <div id="resultCardDetails" class="result-card-details">
                        <div class="detail-row"><span>Manufacturer</span><strong id="manufacturerValue">-</strong></div>
                        <div class="detail-row"><span>Specification</span><strong id="specificationValue">-</strong></div>
                        <div class="detail-row"><span>Description</span><strong id="descriptionValue">-</strong></div>
                        <div class="detail-row"><span>Confidence</span><strong id="confidenceValue">0%</strong></div>
                        <div class="detail-row"><span>Provider</span><strong id="providerValue">-</strong></div>
                    </div>
                </div>
            </div>

            <div class="controls-panel">
                <div id="statusMessage" class="status-message" aria-live="assertive">Point camera at an object</div>
                <div class="progress-block" aria-live="polite">
                    <div class="progress-header">
                        <span id="progressTitle">Identifying Object</span>
                    </div>
                    <div class="progress-bar">
                        <div id="progressFill" class="progress-fill"></div>
                    </div>
                    <div id="progressSteps" class="progress-steps">
                        <span class="step done">Capture image</span>
                        <span class="step active">AI identification</span>
                        <span class="step">Product lookup</span>
                        <span class="step">Display result</span>
                    </div>
                </div>
                <button id="scanButton" class="scan-button" type="button">SCAN</button>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="assets/js/ar-overlay.js"></script>
    <script src="assets/js/scanner.js"></script>
</body>
</html>
