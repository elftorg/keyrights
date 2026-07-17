const fallbackCopy = (text) => {
    const textarea = document.createElement('textarea');

    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.top = '-1000px';
    textarea.style.left = '-1000px';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    let copied = false;
    try {
        copied = document.execCommand('copy');
    } catch (error) {
        copied = false;
    }

    document.body.removeChild(textarea);
    return copied;
};

const copy = (value) => {
    const text = value == null ? '' : String(value);

    if (!text) {
        return Promise.resolve(false);
    }

    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        return navigator.clipboard.writeText(text)
            .then(() => true)
            .catch(() => fallbackCopy(text));
    }

    return Promise.resolve(fallbackCopy(text));
};

module.exports = {copy};
