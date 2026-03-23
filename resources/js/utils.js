export function t(key) {
    return window.translations?.[key] || key;
}

export function formatFileSize(bytes) {
    if (!bytes && bytes !== 0) {
        return '';
    }
    if (bytes < 1024) {
        return bytes + ' ' + t('unit_bytes');
    }
    if (bytes < 1024 * 1024) {
        return (bytes / 1024).toFixed(1) + ' ' + t('unit_kilobytes');
    }

    return (bytes / (1024 * 1024)).toFixed(1) + ' ' + t('unit_megabytes');
}

export function fetchHeaders() {
    return {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
    };
}
