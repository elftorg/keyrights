const request = require('superagent');

require('es6-promise').polyfill();

const DEFAULT_ERROR = 'Не удалось выполнить запрос к KeyRights';

const getBaseUrl = () => {
    const configured = window.CONST && window.CONST.baseUrl
        ? String(window.CONST.baseUrl)
        : '/keyrights/';

    return configured.replace(/\/+$/, '') + '/';
};

const buildUrl = path => getBaseUrl() + String(path || '').replace(/^\/+/, '');

const getEndpoint = path => {
    if (window.CONST && window.CONST.apiUrl) {
        return {
            url: String(window.CONST.apiUrl),
            action: String(path || '').replace(/^\/+|\/+$/g, '')
        };
    }

    return {url: buildUrl(path), action: null};
};

const parsePayload = (error, response) => {
    if (response && response.body && typeof response.body === 'object') {
        return response.body;
    }

    const raw = (response && response.text)
        || (error && error.rawResponse)
        || '';

    if (!raw) {
        throw new Error((error && error.message) || DEFAULT_ERROR);
    }

    try {
        return JSON.parse(raw.replace(/^\uFEFF/, ''));
    } catch (parseError) {
        const invalidResponseError = new Error('Сервер KeyRights вернул некорректный ответ');
        invalidResponseError.cause = parseError;
        invalidResponseError.responseText = raw.slice(0, 1000);
        throw invalidResponseError;
    }
};

const call = (method, path, data) => new Promise((resolve, reject) => {
    const endpoint = getEndpoint(path);
    const query = {csrf_token: window.csrfToken || ''};

    if (endpoint.action) {
        query.action = endpoint.action;
    }

    const req = request(method, endpoint.url)
        .set('Accept', 'application/json')
        .query(query);

    if (data) {
        if (method === 'GET') {
            req.query(data);
        } else {
            req.send(data);
        }
    }

    req.end((error, response) => {
        let payload;

        try {
            payload = parsePayload(error, response);
        } catch (parseError) {
            reject(parseError);
            return;
        }

        if (payload.result !== 'ok') {
            const apiError = new Error(payload.error || DEFAULT_ERROR);
            apiError.status = response && response.status;
            apiError.payload = payload;
            reject(apiError);
            return;
        }

        resolve(payload.data);
    });
});

module.exports = {
    get: (path, params) => call('GET', path, params),
    post: (path, data) => call('POST', path, data),
};