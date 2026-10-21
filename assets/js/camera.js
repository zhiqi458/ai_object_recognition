document.addEventListener('DOMContentLoaded', () => {
    const video = document.getElementById('cameraPreview');
    if (!video) {
        return;
    }

    const constraints = {
        video: {
            facingMode: { ideal: 'environment' },
            width: { ideal: 1280 },
            height: { ideal: 720 }
        },
        audio: false
    };

    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia(constraints)
            .then((stream) => {
                video.srcObject = stream;
                video.play().catch(() => undefined);
            })
            .catch(() => {
                const status = document.getElementById('statusMessage');
                if (status) {
                    status.textContent = 'Camera permission is required.';
                }
            });
    }
});
