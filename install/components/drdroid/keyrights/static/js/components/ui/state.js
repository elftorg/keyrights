const React = require('react');
const classes = require('classnames');

module.exports = ({type = 'empty', title, text, compact = false, action, actionText}) => (
    <div className={classes('keyrights-state', `keyrights-state-${type}`, {compact})}>
        <span className="keyrights-state-icon" aria-hidden="true"></span>
        {title ? <div className="keyrights-state-title">{title}</div> : null}
        {text ? <div className="keyrights-state-text">{text}</div> : null}
        {action && actionText
            ? <button type="button" className="ui-btn ui-btn-primary ui-btn-round" onClick={action}>{actionText}</button>
            : null}
    </div>
);
