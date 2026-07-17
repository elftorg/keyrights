const React      = require('react');
const helper = require('../../helpers/helpers');

module.exports = React.createClass({
    getInitialState() {
        return {};
    },

    showEdit(e) {
        e.stopPropagation();
        e.preventDefault();
        this.props.showEditFolderPopup(Object.assign({}, this.props.folder, {focusNote: true}));
        return false;
    },

    render() {

        const note = typeof this.props.folder.NOTE === 'string' ? this.props.folder.NOTE.trim() : '';
        return <div className="folder-note-wrapper">{note
            ? <div onDoubleClick={this.showEdit}
                   title={helper.t('DBLCLICK_TO_EDIT')}
                   className="folder-note-text">{note}</div>
            : (this.props.folder.CAN_WRITE ? <a href="javascript:void(0)"
                                                onClick={this.showEdit}>{helper.t('ADD_NOTE')}</a> : null)}</div>;
    }
});
