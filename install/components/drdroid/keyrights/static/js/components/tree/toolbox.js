const React   = require('react');
const classes = require('classnames');
const help    = require('../../helpers/helpers');

const F_KEY = 70;

const Toolbox = React.createClass({
    getInitialState() {
        return {
            searchIsOpened: false,
            isSettingsOpened: false
        };
    },

    listenKeys(e) {
        if (e.ctrlKey && e.keyCode === F_KEY) {
            e.preventDefault();
            this.setState({searchIsOpened: true});

            return false;
        }
    },

    componentDidMount() {
        window.addEventListener('keydown', this.listenKeys);
    },

    componentWillUnmount() {
        window.removeEventListener('keydown', this.listenKeys);
    },

    componentWillReceiveProps(props) {
        if (!this.props.forceSearchClose && props.forceSearchClose) {
            this.setState({searchIsOpened: false});
        }
    },

    componentDidUpdate(prevProps, prevState) {
        if (!prevState.searchIsOpened && this.state.searchIsOpened) setTimeout(() => {this.searchInput.focus()}, 300);
    },

    openRoot() {
        this.setState({isSettingsOpened: false});
        this.props.openRoot();
    },

    onSearchChange() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            const val = this.searchInput.value;
            if (val.trim().length > 1) {
                this.props.searchInput(val);
            } else {
                this.props.toggleSearch(false);
            }
        }, 500);
    },

    onInputKeyup(e) {
        if (e.which == 13) {
            const val = this.searchInput.value;
            if (val.trim().length > 1) {
                this.props.searchInput(val);
            } else {
                this.props.toggleSearch(false);
            }
        } else if (e.which == 27) {
            this.setState({searchIsOpened: false});
        }
    },

    render() {
        const {
            addFolderPopup, showImportPopup, canEditFolder, newItem, currentUser, activeFolder,
            exportData, showHistoryPopup, showViewUserPopup, showRemoveRightsPopup
        } = this.props;
        const {isSettingsOpened, searchIsOpened} = this.state;
        const settingsAvailable = currentUser.admin || this.props.view;
        const passwordDisabled = parseInt(activeFolder) === 0 || !canEditFolder;

        const settingClasses = classes(
            'toolbox-menu',
            {
                open: isSettingsOpened,
                disabled: !settingsAvailable
            }
        );

        return (
            <div className="toolbox">
                <div className="toolbox-actions" role="toolbar" aria-label={help.t('TOOLBOX_LABEL')}>
                    <button type="button"
                        type="button"
                        onClick={() => addFolderPopup(activeFolder)}
                        className="tool add-folder"
                        disabled={!canEditFolder}
                        title={help.t('FOLDER_ADD')}
                        aria-label={help.t('FOLDER_ADD')}>
                        <span className="tool-icon"></span>
                    </button>

                    <button type="button"
                        type="button"
                        onClick={newItem}
                        className="tool add-password"
                        disabled={passwordDisabled}
                        title={help.t('PASSWORD_ADD')}
                        aria-label={help.t('PASSWORD_ADD')}>
                        <span className="tool-icon"></span>
                    </button>

                <div className={settingClasses}>
                        <button type="button"
                            type="button"
                            onClick={() => settingsAvailable && this.setState({isSettingsOpened: !isSettingsOpened})}
                            className="tool setting"
                            disabled={!settingsAvailable}
                            title={help.t('TOOLBOX_ACTIONS')}
                            aria-label={help.t('TOOLBOX_ACTIONS')}
                            aria-haspopup="true"
                            aria-expanded={isSettingsOpened}>
                            <span className="tool-icon"></span>
                        </button>

                    {settingsAvailable ?
                        <SettingsDropdown
                            showImportPopup={showImportPopup}
                            exportData={exportData}
                            openRoot={this.openRoot}
                            visible={isSettingsOpened}
                            toggleDropdown={() => this.setState({isSettingsOpened: !isSettingsOpened})}
                            showHistoryPopup={showHistoryPopup}
                            showViewUserPopup={showViewUserPopup}
                            view={this.props.view}
                            resetViewUser={this.props.resetViewUser}
                            currentUser={currentUser}
                            showRemoveRightsPopup={showRemoveRightsPopup}
                            /> :
                        null
                    }
                </div>

                    <button type="button"
                        type="button"
                        onClick={() => this.setState({searchIsOpened: !searchIsOpened})}
                        className={classes("tool search", {active: searchIsOpened})}
                        title={help.t('SEARCH_BY_NAME') + ' (Ctrl+F)'}
                        aria-label={help.t('SEARCH_BY_NAME')}
                        aria-pressed={searchIsOpened}>
                        <span className="tool-icon"></span>
                    </button>
                </div>

                <div className={classes("search-wrapper main-search", {visible: searchIsOpened})}>
                    <i className="icon-search"></i>
                    <input onChange={this.onSearchChange}
                           onKeyUp={this.onInputKeyup}
                           onBlur={() => {this.setState({searchIsOpened: false})}}
                        ref={node => this.searchInput = node}
                        type="text"
                        className="form-control"
                        placeholder={help.t('SEARCH_BY_NAME')}
                        aria-label={help.t('SEARCH_BY_NAME')}/>
                </div>
            </div>
        )
    }
});

const SettingsDropdown = ({toggleDropdown, showImportPopup, visible, openRoot, exportData, showHistoryPopup, showViewUserPopup, view, resetViewUser, currentUser, showRemoveRightsPopup}) => {
    const runAction = (event, action) => {
        event.preventDefault();
        toggleDropdown();
        action();
    };

    return (
    <div className="toolbox-dropdown">
        {visible ? <div onClick={toggleDropdown} className="cover"></div> : null}

            <ul className="dropdown-menu" role="menu">
                <li>
                    <a onClick={event => runAction(event, showImportPopup)} href="javascript:void(0);" role="menuitem">
                    <span className="toolbox-menu-item">
                        <i className="toolbox-menu-icon import"></i>
                        {help.t('IMPORT')}
                    </span>
                    </a>
                </li>
                <li>
                    <a onClick={event => runAction(event, exportData)} href="javascript:void(0);" role="menuitem">
                    <span className="toolbox-menu-item">
                        <i className="toolbox-menu-icon export"></i>
                        {help.t('EXPORT')}
                    </span>
                    </a>
                </li>
                <li>
                    <a onClick={event => runAction(event, showHistoryPopup)} href="javascript:void(0);" role="menuitem">
                    <span className="toolbox-menu-item">
                        <i className="toolbox-menu-icon history"></i>
                        {help.t('GET_HISTORY')}
                    </span>
                    </a>
                </li>
                { !view ?
                    <li className="menu-separator">
                        <a onClick={event => runAction(event, openRoot)} href="javascript:void(0);" role="menuitem">
                            <span className="toolbox-menu-item">
                                <i className="toolbox-menu-icon rights"></i>
                                {help.t('ROOT_RIGHTS_EDIT')}
                            </span>
                        </a>
                    </li>
                    :
                    null
                }
                {CONST.backend === 'bitrix' ? <li>
                    <a onClick={event => runAction(event, showViewUserPopup)} href="javascript:void(0);" role="menuitem">
                    <span className="toolbox-menu-item">
                        <i className="toolbox-menu-icon user"></i>
                        {help.t('VIEW_USER')}
                    </span>
                    </a>
                </li> : null}
                { view ?
                    <li>
                        <a onClick={event => runAction(event, resetViewUser)} href="javascript:void(0);" role="menuitem">
                            <span className="toolbox-menu-item">
                                <i className="toolbox-menu-icon reset-user"></i>
                                <span>
                                    {help.t('UNVIEW_USER')}
                                    <small>{currentUser.NAME + ' ' + currentUser.LAST_NAME}</small>
                                </span>
                            </span>
                        </a>
                    </li>
                    :
                    null
                }
                <li className="menu-separator danger">
                    <a onClick={event => runAction(event, showRemoveRightsPopup)} href="javascript:void(0);" role="menuitem">
                    <span className="toolbox-menu-item">
                        <i className="toolbox-menu-icon remove-rights"></i>
                        {help.t('REMOVE_RIGHTS')}
                    </span>
                    </a>
                </li>
            </ul>
    </div>
    );
};

module.exports = Toolbox;
