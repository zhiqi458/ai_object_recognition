document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('settingsForm');
    const toast = document.getElementById('settingsToast');
    const testButton = document.getElementById('testConnectionBtn');
    const connectionStatus = document.getElementById('connectionStatus');
    const toggleButtons = document.querySelectorAll('.toggle-password');

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

    function showToast(message) {
        if (!toast) {
            return;
        }

        toast.textContent = message;
        toast.classList.add('visible');
        clearTimeout(window.__toastTimer);
        window.__toastTimer = setTimeout(() => {
            toast.classList.remove('visible');
        }, 2600);
    }

    function showConnectionResults(data) {
        if (!connectionStatus) {
            return;
        }

        connectionStatus.replaceChildren();
        Object.entries(data).forEach(([provider, detail]) => {
            const item = document.createElement('div');
            item.className = 'connection-status-item ' + (detail.ok ? 'is-success' : 'is-failure');

            const label = document.createElement('strong');
            label.textContent = provider.charAt(0).toUpperCase() + provider.slice(1);
            item.appendChild(label);

            const status = document.createElement('span');
            status.textContent = detail.ok ? 'Connected' : 'Failed';
            item.appendChild(status);

            if (!detail.ok && detail.error) {
                const error = document.createElement('small');
                error.textContent = detail.error;
                item.appendChild(error);
            }

            connectionStatus.appendChild(item);
        });
        connectionStatus.hidden = false;
    }

    toggleButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const target = document.getElementById(button.dataset.target);
            if (!target) {
                return;
            }

            const isPassword = target.type === 'password';
            target.type = isPassword ? 'text' : 'password';
            button.textContent = isPassword ? 'Hide' : 'Show';
        });
    });

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());
            payload.action = 'save';

            const response = await fetch('api/settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await parseJsonResponse(response);

            if (!response.ok || !result.ok) {
                showToast(result.error || 'Unable to save settings.');
                return;
            }

            showToast('Settings saved successfully.');
            document.querySelectorAll('[data-field-status]').forEach((statusEl) => {
                const fieldName = statusEl.dataset.fieldStatus;
                const value = result.data[fieldName] || 'Not configured';
                statusEl.textContent = value === 'Key saved' ? 'Key saved' : 'Not configured';
            });
        });
    }

    if (testButton) {
        testButton.addEventListener('click', async () => {
            testButton.disabled = true;
            testButton.textContent = 'Testing...';

            try {
                const response = await fetch('api/settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'test' })
                });
                const result = await parseJsonResponse(response);

                if (!response.ok || !result.ok) {
                    showToast(result.error || 'Unable to test provider connections.');
                    return;
                }

                showConnectionResults(result.data);
            } catch (error) {
                showToast('Unable to reach the settings server.');
            } finally {
                testButton.disabled = false;
                testButton.textContent = 'Test Connection';
            }
        });
    }
});
