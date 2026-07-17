const React = require('react');
const help = require('../helpers/helpers');

module.exports = () => (
    <div className="loading-overlay l-overlay" role="status" aria-live="polite">
        <div className="keyrights-loader">
            <span className="keyrights-loader-spinner"></span>
            <span className="keyrights-loader-title">KeyRights</span>
            <span className="keyrights-loader-text">{help.t('LOADING_DATA')}</span>
        </div>
    </div>
);
