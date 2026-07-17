const assert = require('assert');
const makeCsv = require('../static/js/helpers/csv');

const csv = makeCsv([
    {name: '=HYPERLINK("https://example.invalid")'},
    {name: '  +SUM(1,2)'},
    {name: 'regular value'}
], ['name']);

assert(csv.includes("'=HYPERLINK"), 'formula prefix must be neutralized');
assert(csv.includes("'  +SUM"), 'formula prefix after whitespace must be neutralized');
assert(csv.includes('regular value'), 'ordinary text must remain unchanged');
