const React      = require('react');
const Tree       = require('./tree');
const MainArea   = require('./main-area');
const Panel      = require('./panel');
const Modals     = require('./modals');
const LoadScreen = require('./load-screen');
const Actions    = require('../actions/');
const NotAccess  = require('./not-access');

const {connect} = require('react-redux');
const classes = require('classnames');
const help = require('../helpers/helpers');


const Root = React.createClass({
    getInitialState() {
        return {
            navigationOpen: false,
            detailsOpen: true
        };
    },

    shouldComponentUpdate(nextProps, nextState) {
        if(nextProps.currentUser != this.props.currentUser && nextProps.currentUser != 'not_permission') {
            this.props.fetchData(nextProps.currentUser, true);
        }
        return true;
    },
    componentWillReceiveProps(nextProps) {
        if (nextProps.panelOpened && nextProps.panelKey !== this.props.panelKey) {
            this.setState({detailsOpen: true, navigationOpen: false});
        }
    },
    componentDidMount() {
        this.props.fetchData(this.props.currentUser);
        let path = window.location.hash.substr(2).split('/');
        if (path[0]) {
            this.props.openFolder(parseInt(path[0]), this.props.currentUser);
            if (path[1]) {
                this.props.openItem(parseInt(path[1]));
            }
        }
    },
    render() {
        if (!this.props.loaded) {
            return <LoadScreen />
        }
        if(this.props.currentUser == 'not_permission') {
            return (
                <div className="content">
                    <NotAccess
                        view = { this.props.view }
                        resetView = { this.props.resetViewUser }
                    />
                </div>
            )
        }
        return (
            <div className={classes('content', {
                'navigation-open': this.state.navigationOpen,
                'details-open': this.props.panelOpened && this.state.detailsOpen,
                'has-details': this.props.panelOpened
            })}>
                <div className="keyrights-mobile-bar">
                    <button type="button"
                        className={classes('keyrights-mobile-button folders', {active: this.state.navigationOpen})}
                        onClick={() => this.setState({navigationOpen: !this.state.navigationOpen, detailsOpen: false})}>
                        <span className="keyrights-mobile-icon"></span>
                        {help.t('MOBILE_FOLDERS')}
                    </button>
                    {this.props.panelOpened ?
                        <button type="button"
                            className={classes('keyrights-mobile-button details', {active: this.state.detailsOpen})}
                            onClick={() => this.setState({detailsOpen: !this.state.detailsOpen, navigationOpen: false})}>
                            <span className="keyrights-mobile-icon"></span>
                            {help.t('MOBILE_DETAILS')}
                        </button> :
                        null}
                </div>
                <Tree />
                <MainArea />
                <Panel />
                <Modals />
            </div>
        )
    }
});

function mapStateToProps(state) {
    const panelElement = state.panel.panelElement;

    return {
        currentUser: state.currentUser,
        loaded: state.loaded,
        view: state.view,
        panelOpened: panelElement !== false,
        panelKey: panelElement === false
            ? ''
            : [
                panelElement.isFolder ? 'folder' : 'item',
                panelElement.id || 'new',
                panelElement.isNew ? 'new' : '',
                panelElement.isEdit ? 'edit' : ''
            ].join(':')
    }
}

function mapDispatchToProps(dispatch) {
    return {
        fetchData: (currentUser, userId) => dispatch(Actions.fetchData(currentUser, userId)),
        openFolder: (id, user) => dispatch(Actions.openFolder(id, user)),
        openItem: (id) => dispatch(Actions.openItem(id)),
        resetViewUser: () => dispatch(Actions.resetViewUser()),
    }
}

module.exports = connect(mapStateToProps, mapDispatchToProps)(Root);
