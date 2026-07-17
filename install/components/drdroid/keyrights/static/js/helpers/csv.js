const escapeCell = value => {
    let text = value === null || typeof value === 'undefined' ? '' : String(value);
    // Spreadsheet applications execute cells beginning with these characters
    // as formulas. Prefixing an apostrophe keeps exported secrets as text.
    if (/^[\s]*[=+\-@]/.test(text)) {
        text = "'" + text;
    }
    return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
};

module.exports = (rows, fields, headers = fields) => {
    const lines = [headers.map(escapeCell).join(',')];
    (rows || []).forEach(row => {
        lines.push(fields.map(field => escapeCell(row && row[field])).join(','));
    });
    return '\uFEFF' + lines.join('\r\n');
};
