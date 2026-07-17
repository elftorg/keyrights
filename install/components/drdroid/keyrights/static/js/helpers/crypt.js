const CryptoJS = require('crypto-js/core');
require('crypto-js/sha256');
require('crypto-js/hmac');
require('crypto-js/pbkdf2');

const _key = (window.CONST && window.CONST.key)
    || '';
const _salt = (window.CONST && window.CONST.keySalt) || '';
const _derivedKey = CryptoJS.PBKDF2(_key, CryptoJS.enc.Hex.parse(_salt), {
    keySize: 256 / 32,
    iterations: 210000,
    hasher: CryptoJS.algo.SHA256
}).toString(CryptoJS.enc.Hex);

if (window.CONST) {
    delete window.CONST.key;
    delete window.CONST.keySalt;
}

const help = require('./helpers');

const cryptHelper = {
    encrypt(obj) {
        return 'k2:' + Aes.Ctr.encrypt(JSON.stringify(obj), _derivedKey, 256);
    },
    decrypt(str) {
        var cryptedObj = {};

        if (typeof str === 'undefined' || 0 === str.length) {
            return cryptedObj;
        }

        try {
            const isKdfV2 = str.indexOf('k2:') === 0;
            const ciphertext = isKdfV2 ? str.slice(3) : str;
            cryptedObj = JSON.parse(Aes.Ctr.decrypt(ciphertext, isKdfV2 ? _derivedKey : _key, 256));
            if (typeof cryptedObj !== 'object' || cryptedObj == null) {
                cryptedObj = {};
            }
        } catch(err) {
            if (!(err instanceof SyntaxError)) {
                throw(err);
            }
        }

        return cryptedObj;
    },

    process(item) {
        return {
            NAME: item['Account'],
            SECTION: item['Password Groups'],
            PARENT_SECTION: item['Group Tree'],
            CRYPTED: this.encrypt({
                LOGIN: item['Login Name'],
                PASSWORD: item['Password'],
                URL: item['Web Site'],
                NOTE: item['Comments']
            })
        };
    },

    getLink(str) {
        const obj   = this.decrypt(str);
        let rawLink = obj.URL;

        if (!rawLink) return '';
        if (!rawLink.trim()) return rawLink;

        var url    = rawLink;
        var parts  = help.parseUrl(rawLink);
        var isLink = false;
        if ((parts.scheme == 'http') || (parts.scheme == 'https')) {
            isLink = true;
            url    = parts.scheme + '://' + parts.host;
        } else if (rawLink.indexOf('www.') === 0) {
            isLink  = true;
            url     = rawLink;
            rawLink = 'http://' + rawLink;
        }

        return {isLink, text: rawLink};
    }
};

module.exports = cryptHelper;
