document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('cameraPreview');
    const statusMessage = document.getElementById('statusMessage');
    const scanButton = document.getElementById('scanButton');
    const resultCard = document.getElementById('resultCard');
    const resultCardTitle = document.getElementById('resultCardTitle');
    const manufacturerValue = document.getElementById('manufacturerValue');
    const specificationValue = document.getElementById('specificationValue');
    const descriptionValue = document.getElementById('descriptionValue');
    const confidenceValue = document.getElementById('confidenceValue');
    const providerValue = document.getElementById('providerValue');
    const progressFill = document.getElementById('progressFill');
    const progressSteps = document.querySelectorAll('.progress-steps .step');

    const state = {
        stream: null,
        isProcessing: false,
        lastResult: null
    };

    function setStatus(message) {
        if (statusMessage) {
            statusMessage.textContent = message;
        }
    }

    function setProgress(stepIndex) {
        const total = progressSteps.length;
        const width = ((stepIndex + 1) / total) * 100;
        if (progressFill) {
            progressFill.style.width = Math.max(15, width) + '%';
        }

        progressSteps.forEach((step, index) => {
            step.classList.remove('done', 'active');
            if (index < stepIndex) {
                step.classList.add('done');
            } else if (index === stepIndex) {
                step.classList.add('active');
            }
        });
    }

    function setButtonState(label, disabled) {
        if (scanButton) {
            scanButton.textContent = label;
            scanButton.disabled = disabled;
        }
    }

    function toggleResultCard(show, result = null) {
        if (!resultCard) {
            return;
        }

        if (!show || !result) {
            resultCard.classList.add('hidden');
            resultCard.classList.remove('is-expanded');
            return;
        }

        resultCard.classList.remove('hidden');
        resultCardTitle.textContent = result.productName || 'Unknown product';
        manufacturerValue.textContent = result.manufacturer || 'Unknown';
        specificationValue.textContent = result.specification || 'Unavailable';
        descriptionValue.textContent = result.description || 'No description available.';
        confidenceValue.textContent = Math.round((Number(result.confidence || 0) * 100)) + '%';
        providerValue.textContent = result.provider || 'Unknown';
    }

    async function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setStatus('Camera is unavailable.');
            return;
        }

        try {
            const constraints = {
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            };

            state.stream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = state.stream;
            await video.play();
            setStatus('Point camera at an object');
            setProgress(1);
        } catch (error) {
            if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
                setStatus('Please use HTTPS to access the camera.');
            } else {
                setStatus('Camera permission is required.');
            }
        }
    }

    function captureFrame() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const frameWidth = video.videoWidth || 1280;
        const frameHeight = video.videoHeight || 720;
        const maxSide = 1280;

        const scale = Math.min(1, maxSide / Math.max(frameWidth, frameHeight));
        canvas.width = Math.max(1, Math.round(frameWidth * scale));
        canvas.height = Math.max(1, Math.round(frameHeight * scale));

        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        return canvas.toDataURL('image/jpeg', 0.7);
    }

    async function parseJsonResponse(response) {
        const text = await response.text();
        if (!text) {
            return { ok: false, error: 'The server returned an empty response.' };
        }

        try {
            return JSON.parse(text);
        } catch (error) {
            return { ok: false, error: 'The server returned an invalid JSON response.' };
        }
    }

    async function handleScan() {
        if (state.isProcessing) {
            return;
        }

        if (!video || video.readyState < 2) {
            setStatus('Camera is unavailable.');
            return;
        }

        state.isProcessing = true;
        setButtonState('SCANNING...', true);
        setStatus('Capturing image...');
        setProgress(0);

        const imageData = captureFrame();
        setStatus('AI identifying object...');
        setProgress(1);

        try {
            const response = await fetch('api/recognize.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image: imageData })
            });

            const payload = await parseJsonResponse(response);
            if (!response.ok || !payload.ok) {
                setStatus(payload.error || 'Unable to identify the object. Please try again.');
                clearDetectionBox();
                toggleResultCard(false);
                setButtonState('SCAN AGAIN', false);
                setProgress(3);
                state.isProcessing = false;
                return;
            }

            setStatus('Object identified');
            setProgress(3);
            toggleResultCard(true, payload.data);
            renderDetectionBox(payload.data.boundingBox || null);
            state.lastResult = payload.data;
            setButtonState('SCAN AGAIN', false);
        } catch (error) {
            setStatus('Unable to identify the object. Please try again.');
            toggleResultCard(false);
            clearDetectionBox();
            setButtonState('SCAN AGAIN', false);
            setProgress(0);
        } finally {
            state.isProcessing = false;
        }
    }

    if (resultCard) {
        resultCard.addEventListener('click', () => {
            if (!state.lastResult) {
                return;
            }
            resultCard.classList.toggle('is-expanded');
        });
    }

    if (scanButton) {
        scanButton.addEventListener('click', handleScan);
    }

    setButtonState('SCAN', false);
    setProgress(1);
    startCamera();
});
