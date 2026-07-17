const React = require('react');
const help = require('../../helpers/helpers');

module.exports = React.createClass({
    componentDidMount() {
        if (!window.BX || !BX.UI || !BX.UI.Dialogs || !BX.UI.Dialogs.MessageBox) {
            return;
        }

        this.dialog = BX.UI.Dialogs.MessageBox.create({
            message: this.props.text,
            buttons: BX.UI.Dialogs.MessageBoxButtons.OK,
            okCaption: help.t('CLOSE'),
            useAirDesign: true,
            onOk: () => {
                this.props.closeModal();
                return false;
            },
            popupOptions: {
                closeByEsc: true,
                events: {
                    onPopupClose: () => {
                        if (!this.destroying) {
                            this.props.closeModal();
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

    render() {
        if (window.BX && BX.UI && BX.UI.Dialogs && BX.UI.Dialogs.MessageBox) {
            return null;
        }

        return (
            <div className="modal confirm-dialog alert">
                <div className="modal-dialog">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h4 className="modal-title">{this.props.text}</h4>
                        </div>
                        <div className="modal-footer">
                            <button onClick={this.props.closeModal} type="button" className="ui-btn ui-btn-primary">{help.t('CLOSE')}</button>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
});
