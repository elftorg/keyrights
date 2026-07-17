const React        = require('react');
const classes      = require('classnames');
const AddFolder    = require('./modals/add-folder');
const AddUsers     = require('./modals/add-users');
const ChangeOwner  = require('./modals/change-owner');
const Confirm      = require('./modals/confirm');
const Alert        = require('./modals/alert');
const Import       = require('./modals/import');
const History      = require('./modals/history');
const ViewUser     = require('./modals/view-user.js');
const RemoveRights = require('./modals/remove-rights.js');
const PopupWindow  = require('./modals/popup-window');
const Actions      = require('../actions/');
const help         = require('../helpers/helpers');
const {connect} = require('react-redux');

const Modals = React.createClass({
    render() {
        const {addFolder, removeFolder, currentUser, removeItem, openedModal, saveRights, closeModal, editFolder} = this.props;
        let modalContent = null;
        let nativeDialog = false;

        switch (openedModal === false ? false : openedModal.type) {
                        case 'ADD_FOLDER':
                            modalContent = <AddFolder
                                tree={this.props.tree}
                                currentUser={currentUser}
                                addFolder={addFolder}
                                closeModal={closeModal}
                                modal={openedModal}/>;
                            break;

                        case 'EDIT_FOLDER':
                            modalContent = <AddFolder
                                tree={this.props.tree}
                                editFolder={editFolder}
                                closeModal={closeModal}
                                modal={openedModal}/>;
                            break;

                        case 'ADD_USERS':
                            modalContent = <AddUsers
                                submit={(data) => saveRights(data, currentUser)}
                                groups={this.props.groups}
                                users={this.props.users}
                                closeModal={closeModal}
                                modal={openedModal}/>;
                            break;

                        case 'CHANGE_OWNER':
                            modalContent = <ChangeOwner
                                submit={(data) => this.props.changeOwner(data, currentUser)}
                                users={this.props.users}
                                closeModal={closeModal}
                                modal={openedModal}/>;
                            break;

                        case 'REMOVE_FOLDER_CONFIRM':
                            const text = help.t('FOLDER_DEL_CONFIRM_1') + ' \'' + openedModal.item.NAME + '\' ' + help.t('FOLDER_DEL_CONFIRM_2');

                            nativeDialog = true;
                            modalContent = <Confirm
                                text={text}
                                onOk={() => removeFolder(openedModal.item)}
                                onCancel={closeModal}
                                closeModal={closeModal}
                                modal={openedModal}/>;
                            break;

                        case 'REMOVE_ITEM_CONFIRM':
                            nativeDialog = true;
                            modalContent = <Confirm
                                text={help.t('PASS_DEL_CONFIRM')}
                                onOk={() => removeItem(openedModal.item)}
                                onCancel={closeModal}
                                closeModal={closeModal}
                                modal={openedModal}/>;
                            break;

                        case 'IMPORT':
                            modalContent = <Import
                                import={this.props.importData}
                                showAlert={this.props.showAlert}
                                closeModal={closeModal}
                            />;
                            break;

                        case 'HISTORY':
                            modalContent = <History
                                history={this.props.getHistory}
                                showAlert={this.props.showAlert}
                                closeModal={closeModal}
                            />;
                            break;

                        case 'VIEW_USER':
                            modalContent = <ViewUser
                                currentUser={currentUser}
                                groups={this.props.groups}
                                users={this.props.users}
                                view={this.props.viewUser}
                                showAlert={this.props.showAlert}
                                closeModal={closeModal}
                            />;
                            break;
                        case 'REMOVE_RIGHTS':
                            modalContent = <RemoveRights
                                currentUser={currentUser}
                                groups={this.props.groups}
                                users={this.props.users}
                                removeRights={this.props.removeRights}
                                showAlert={this.props.showAlert}
                                closeModal={closeModal}
                            />;
                            break;

                        default:
                            break;
        }

        return (
            <div className="modals-wrap">
                {this.props.alert ? <Alert closeModal={this.props.closeAlert} text={this.props.alert}/> : null}
                {modalContent
                    ? nativeDialog
                        ? modalContent
                        : <PopupWindow onClose={closeModal} className={`keyrights-popup-${openedModal.type.toLowerCase()}`}>{modalContent}</PopupWindow>
                    : null}
            </div>
        )
    }
});

function mapStateToProps(state) {
    const {openedModal} = state.modal;

    if (openedModal.type === 'ADD_USERS' || openedModal.type === 'VIEW_USER' || openedModal.type === 'REMOVE_RIGHTS' || openedModal.type === 'CHANGE_OWNER') {
        openedModal.item = openedModal.isSection
            ? state.tree.tree.sections[state.tree.tree.index[openedModal.id]]
            : state.items.items.entities[state.items.items.index[openedModal.id]]

        return {
            openedModal,
            currentUser: state.currentUser,
            groups: state.groups,
            users: state.users
        }
    }

    if (openedModal.type === 'REMOVE_FOLDER_CONFIRM') {
        openedModal.item = state.tree.tree.sections[state.tree.tree.index[openedModal.id]];

        return {
            openedModal,
            currentUser: state.currentUser
        }
    }

    if (openedModal.type === 'REMOVE_ITEM_CONFIRM') {
        return {
            openedModal,
            currentUser: state.currentUser
        }
    }

    if (openedModal.SECTION !== undefined) {
        openedModal.parent = state.tree.tree.sections[state.tree.tree.index[openedModal.SECTION]];
    }

    return {
        tree: state.tree.tree,
        alert: state.modal.alert,
        currentUser: state.currentUser,
        openedModal,
    }
}

function mapDispatchToProps(dispatch) {
    return {
        closeModal: () => dispatch(Actions.closeModal()),
        addFolder: (data, user) => dispatch(Actions.addFolder(data, user)),
        changeOwner: (data, user) => dispatch(Actions.changeOwner(data, user)),
        removeFolder: (item) => dispatch(Actions.removeFolder(item)),
        showAlert: (text) => dispatch(Actions.showAlert(text)),
        importData: (data) => dispatch(Actions.importData(data)),
        closeAlert: () => dispatch(Actions.closeAlert()),
        editFolder: (data) => dispatch(Actions.editFolder(data)),
        removeItem: (item) => dispatch(Actions.removeItem(item)),
        saveRights: (data, user) => dispatch(Actions.saveRights(data, user)),
        getHistory: (data) => dispatch(Actions.getHistory(data)),
        viewUser: (item) => dispatch(Actions.viewUser(item)),
        removeRights: (userId) => dispatch(Actions.removeRights(userId)),
    }
}

module.exports = connect(mapStateToProps, mapDispatchToProps)(Modals);
