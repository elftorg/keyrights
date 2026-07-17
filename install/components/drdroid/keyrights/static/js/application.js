const React = require('react');
// React 16 moved these legacy APIs to separate packages. The application
// still uses the old createClass style, so keep a small compatibility bridge
// while the components are migrated incrementally.
React.createClass = require('create-react-class');
React.PropTypes = require('prop-types');
const {render} = require('react-dom');
const {Provider} = require('react-redux');

const store = require('./store/configure-store');
const Root = require('./components/root');

let started = false;

function fitToViewport(app) {
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
    const top = Math.max(0, app.getBoundingClientRect().top);

    app.style.height = Math.max(480, viewportHeight - top) + 'px';
}

function start() {
    if (started) {
        return;
    }

    const app = document.getElementById('keyrights');
    if (!app) {
        return;
    }

    started = true;
    fitToViewport(app);
    window.addEventListener('resize', () => fitToViewport(app));

    render(
        <Provider store={store}>
            <Root />
        </Provider>,
        app
    );
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, {once: true});
} else {
    start();
}
