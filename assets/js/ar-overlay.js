function clearDetectionBox() {
    const overlay = document.getElementById('cameraOverlay');
    if (!overlay) {
        return;
    }

    overlay.querySelectorAll('.detection-box').forEach((box) => box.remove());
}

function renderDetectionBox(box) {
    if (!box || typeof box !== 'object') {
        clearDetectionBox();
        return;
    }

    const overlay = document.getElementById('cameraOverlay');
    const video = document.getElementById('cameraPreview');
    if (!overlay || !video) {
        return;
    }

    clearDetectionBox();

    const boxElement = document.createElement('div');
    const left = Math.max(0, Number(box.xmin ?? 0));
    const top = Math.max(0, Number(box.ymin ?? 0));
    const right = Math.max(0, Number(box.xmax ?? 1000));
    const bottom = Math.max(0, Number(box.ymax ?? 1000));

    const percentLeft = (left / 1000) * 100;
    const percentTop = (top / 1000) * 100;
    const percentWidth = ((right - left) / 1000) * 100;
    const percentHeight = ((bottom - top) / 1000) * 100;

    boxElement.className = 'detection-box';
    boxElement.style.left = percentLeft + '%';
    boxElement.style.top = percentTop + '%';
    boxElement.style.width = Math.max(percentWidth, 2) + '%';
    boxElement.style.height = Math.max(percentHeight, 2) + '%';
    overlay.appendChild(boxElement);
}
