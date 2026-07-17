const React = require('react');
const ReactDOM = require('react-dom');

let popupIndex = 0;

const PopupWindow = React.createClass({
    getInitialState() {
        return {ready: false};
    },

    componentDidMount() {
        this.container = document.createElement('div');
        this.container.className = 'keyrights-popup-content';

        if (!window.BX || !BX.PopupWindow) {
            this.container.className += ' keyrights-popup-fallback';
            document.body.appendChild(this.container);
            this.setState({ready: true});
            return;
        }

        this.popup = new BX.PopupWindow(
            `keyrights-popup-${++popupIndex}`,
            null,
            {
                content: this.container,
                className: `keyrights-bx-popup ${this.props.className || ''}`,
                overlay: {opacity: 28},
                autoHide: false,
                closeByEsc: true,
                closeIcon: false,
                padding: 0,
                contentPadding: 0,
                events: {
                    onPopupClose: () => {
                        if (!this.destroying && this.props.onClose) {
                            this.props.onClose();
                        }
                    }
                }
            }
        );

        this.popup.show();
        this.setState({ready: true});
    },

    componentDidUpdate() {
        if (this.popup && this.popup.adjustPosition) {
            this.popup.adjustPosition();
        }
    },

    componentWillUnmount() {
        this.destroying = true;

        if (this.popup) {
            this.popup.destroy();
            this.popup = null;
        } else if (this.container && this.container.parentNode) {
            this.container.parentNode.removeChild(this.container);
        }
    },

    render() {
        if (!this.state.ready || !this.container) {
            return null;
        }

        // A React 16 portal keeps the Redux Provider context. The old
        // unstable_renderSubtreeIntoContainer created a second root and was
        // the source of “Could not find store” in connected dialogs.
        return ReactDOM.createPortal(
            <div className="keyrights-popup-inner">{this.props.children}</div>,
            this.container
        );
    }
});

module.exports = PopupWindow;
