const escapeCell = value => {
    const text = value === null || typeof value === 'undefined' ? '' : String(value);
    return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
};

module.exports = (rows, fields, headers = fields) => {
    const lines = [headers.map(escapeCell).join(',')];
    (rows || []).forEach(row => {
        lines.push(fields.map(field => escapeCell(row && row[field])).join(','));
    });
    return '\uFEFF' + lines.join('\r\n');
};
