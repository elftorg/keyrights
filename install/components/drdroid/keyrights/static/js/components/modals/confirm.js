const React = require('react');
const help = require('../../helpers/helpers');

module.exports = React.createClass({
    componentDidMount() {
        if (!window.BX || !BX.UI || !BX.UI.Dialogs || !BX.UI.Dialogs.MessageBox) {
            return;
        }

        this.dialog = BX.UI.Dialogs.MessageBox.create({
            message: this.props.text,
            buttons: BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
            okCaption: 'OK',
            cancelCaption: help.t('CANCEL'),
            useAirDesign: true,
            onOk: (messageBox, button) => {
                const result = this.props.onOk();

                if (result && typeof result.then === 'function') {
                    button.setWaiting();
                    result.then(() => {
                        if (!this.destroying) {
                            button.setWaiting(false);
                        }
                    });
                }

                return false;
            },
            onCancel: () => {
                this.props.onCancel();
                return false;
            },
            popupOptions: {
                closeByEsc: true,
                events: {
                    onPopupClose: () => {
                        if (!this.destroying) {
                            this.props.onCancel();
                        }
                    }
                }
            }
        });
        this.dialog.show();
    },

    componentWillUnmount() {
        this.destroying = true;
        if (this.dialog) {
            this.dialog.getPopupWindow().destroy();
        }
    },

    handleOk() {
        this.props.onOk();
    },

    render() {
        if (window.BX && BX.UI && BX.UI.Dialogs && BX.UI.Dialogs.MessageBox) {
            return null;
        }

        return (
            <div className="modal confirm-dialog">
                <div className="modal-dialog">
                    <div className="modal-content">
                        <div className="modal-header">
                            <button onClick={this.props.onCancel} type="button" className="close" aria-label={help.t('CLOSE')}>&times;</button>
                            <h4 className="modal-title">{this.props.text}</h4>
                        </div>
                        <div className="modal-footer">
                            <button onClick={this.props.onCancel} type="button" className="ui-btn ui-btn-light-border">{help.t('CANCEL')}</button>
                            <button onClick={this.handleOk} type="button" className="ui-btn ui-btn-danger">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
});
