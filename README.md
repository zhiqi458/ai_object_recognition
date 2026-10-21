# AI + AR Smart Object Recognition System

A mobile-first PHP application that captures live camera frames from a smartphone, sends them to AI vision providers, validates the response, and overlays a lightweight augmented-reality detection box over the camera view.

## Project overview

This project is designed to work in a local XAMPP environment, on a cPanel host, or in a secure HTTPS deployment. The user opens the scanner page, grants camera access, points the device at an object, presses Scan, and receives a product identification result with an AR-style overlay and product detail card.

## Features

- Live mobile camera preview with rear-camera preference
- Capture and resize image before sending to the backend
- PHP image validation and security checks
- Gemini and Agnes provider support with Auto fallback logic
- AI JSON validation with defensive normalization
- AR overlay box and product detail panel
- Settings page for provider and API keys
- QR code to open the scanner from a phone
- Responsive mobile-first layout

## Technology stack

- PHP 8.3+
- MySQL 8+
- HTML5
- CSS3
- Vanilla JavaScript
- Bootstrap 5 CDN
- Font Awesome CDN
- Google Fonts CDN
- Browser MediaDevices API, Canvas API, and Fetch API

## Requirements

- XAMPP with PHP and Apache
- HTTPS for camera access on mobile devices
- A valid AI provider API key for real recognition

## XAMPP installation

1. Copy the project folder to `C:\xampp\htdocs\ai-ar-object-recognition` or a similar local web root folder.
2. Open `http://localhost/ai-ar-object-recognition/` in a browser.
3. For the best mobile experience, use HTTPS on a local secure server or test on a real device connected through a trusted secure context.
4. Visit the settings page to configure the AI provider.

## cPanel installation

1. Upload the project folder to `public_html` or a subfolder such as `public_html/ai-ar`.
2. Ensure file paths are relative to the application directory.
3. Load the application at `https://example.com/ai-ar/`.
4. Keep the `config/config.php` file protected using server-side access rules.

## HTTPS requirement

Modern browser camera APIs require a secure context. Use HTTPS whenever possible, especially for mobile devices. For local development, this may require a secure tunnel or a trusted local certificate. If camera access fails, check the browser console and ensure the page is loaded through HTTPS or localhost.

## AI API configuration

Copy the example config file and add your keys in the protected config file:

```php
<?php
return [
    'ai_provider' => 'auto',
    'gemini_api_key' => '',
    'gemini_model' => 'gemini-3.8-flash',
    'gemini_fallback_models' => 'gemini-3.8-flash-lite',
    'agnes_api_key' => '',
    'agnes_api_url' => '',
    'request_timeout' => 30,
];
```

The project includes a protected `config/config.php` file for local use. In production, keep it outside the public web root when possible, or restrict access with `.htaccess` rules.

## Database

This version does not use a database. Recognition results are returned directly to the browser and are not persisted.

## Camera permission

The browser will request camera permission when the app loads. If permission is denied, the UI will show a friendly message. On mobile devices, the app prefers the environment-facing camera when available.

## Testing

- Open the app on a desktop browser or mobile browser.
- Grant camera permissions.
- Point the camera at a simple object.
- Press Scan.
- Verify the recognition result and overlay box appear.
- Check the settings page and QR code.

## Troubleshooting

- Camera unavailable: verify browser support and secure context.
- AI provider not configured: add the API key in the config file.
- Invalid JSON from AI provider: verify the provider response and the prompt format.
- Large image errors: reduce the capture resolution or confirm base64 validation.

## Security notes

- Do not expose API keys in frontend JavaScript.
- Keep `config.php` outside the public web root whenever possible.
- Validate all image and JSON input before processing.
- Treat AI output as untrusted data.
- Log server errors without exposing secrets.

## Project folder structure

```text
ai-ar-object-recognition/
├── index.php
├── settings.php
├── README.md
├── api/
│   ├── recognize.php
│   └── settings.php
├── config/
│   ├── config.example.php
│   ├── config.php
│   └── .htaccess
├── includes/
│   ├── AppConfig.php
│   ├── AIProvider.php
│   ├── GeminiVision.php
│   ├── AgnesVision.php
│   ├── ObjectRecognizer.php
│   ├── ImageValidator.php
│   └── ProductResult.php
├── assets/
│   ├── css/
│   │   └── app.css
│   └── js/
│       ├── camera.js
│       ├── ar-overlay.js
│       ├── scanner.js
│       └── settings.js
└── .htaccess
```
