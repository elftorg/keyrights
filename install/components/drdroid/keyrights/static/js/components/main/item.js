const React       = require('react');
const clipboard   = require('../../helpers/clipboard');
const classes     = require('classnames');
const cryptHelper = require('../../helpers/crypt');
const ActionTypes = require('../../constants/action-types');
const colors      = require('../../constants/colors');
const help        = require('../../helpers/helpers');

module.exports = React.createClass({
    getInitialState() {
        return {
            menuIsVisible: false,
            copy: false
        };
    },

    shouldComponentUpdate(nextProps) {
        const {lastAction, activeItem, item} = nextProps;
        const lastActiveItem = this.props.activeItem;

        if (lastAction === ActionTypes.OPEN_ITEM) {
            if (parseInt(activeItem) === parseInt(item.ID) || parseInt(lastActiveItem) === parseInt(item.ID)) {
                return true;
            }

            return false;
        }

        return true;
    },

    toggleMenu(e, menuIsVisible) {
        e.preventDefault();
        e.stopPropagation();
        this.setState({menuIsVisible});
    },

    handleAction(e, action) {
        this.toggleMenu(e, false);
        this.setState({copy: true});

        setTimeout((function() {
            this.setState({copy: false});
        }).bind(this), 1000);

        action();
    },

    openLink(e) {
        e.stopPropagation();
    },

    onDragStart(e) {
        e.stopPropagation();
        e.dataTransfer.setData("entityId", this.props.item.ID);
        e.dataTransfer.setData("parentId", this.props.item.SECTION);
    },

    componentDidMount() {
        this.item.addEventListener('dragstart', this.onDragStart);
    },

    componentWillUnmount() {
        this.item.removeEventListener('dragstart', this.onDragStart);
    },

    render() {
        const {item, activeItem, openItem, changeFavorite} = this.props;
        const link = cryptHelper.getLink(item.CRYPTED);
        const data = cryptHelper.decrypt(item.CRYPTED);
        const color = colors[item.COLOR] ? colors[item.COLOR].color : 'transparent';
        const rowStyle = color === 'transparent' ? null : {borderLeftColor: color};
        return (
            <tr ref={node => this.item = node}
                draggable={item.CAN_WRITE}
                onContextMenu={(e) => this.toggleMenu(e, true)}
                className={classes({active: activeItem == item.ID})}
                onClick={() => openItem(item.ID)}>
                <td style={rowStyle}>
                    <div className="drag-root wrapper main-area-element">
                        {this.state.menuIsVisible ?
                            <div onClick={(e) => this.toggleMenu(e, false)} className="cover"></div> : null}
                        <span className="record-icon" aria-hidden="true">
                            <span className="glyphicon glyphicon-lock"></span>
                        </span>
                        <span className="record-name" title={item.NAME}>{item.NAME}</span>
                        {this.state.copy ? <span className="success-copy">{help.t('SUCCESS_COPY')}</span> : null}
                            <div className={classes("menu dropdown", {open: this.state.menuIsVisible})}>
                                <i onClick={(e) => this.toggleMenu(e, true)} className="icon-menu dropdown-toggle menu-item"></i>
                                {!this.state.menuIsVisible ||  (!data.LOGIN && !data.PASSWORD && !data.URL && !item.CAN_WRITE) ?
                                 null :
                                    <ul className="dropdown-menu">
                                        {data.LOGIN ?
                                            <li>
                                                <a onClick={(e) => this.handleAction(e, () => clipboard.copy(data.LOGIN))}
                                                    href="javascript:void(0);">
                                                    <span>
                                                        <i className="icon icon-file" dangerouslySetInnerHTML={{__html: '&nbsp;'}}></i>
                                                        <span>{help.t('COPY_LOGIN')}</span>
                                                    </span>
                                                </a>
                                            </li> :
                                            null
                                        }
                                        {data.PASSWORD ?
                                            <li>
                                                <a onClick={(e) => this.handleAction(e, () => clipboard.copy(data.PASSWORD))}
                                                    href="javascript:void(0);">
                                                    <span>
                                                        <i className="icon icon-file" dangerouslySetInnerHTML={{__html: '&nbsp;'}}></i>
                                                        <span>{help.t('COPY_PASS')}</span>
                                                    </span>
                                                </a>
                                            </li> :
                                            null
                                        }
                                        {data.URL ?
                                            <li>
                                                <a onClick={(e) => this.handleAction(e, () => clipboard.copy(data.URL))}
                                                    href="javascript:void(0);">
                                                    <span>
                                                        <i className="icon icon-file" dangerouslySetInnerHTML={{__html: '&nbsp;'}}></i>
                                                        <span>{help.t('COPY_URL')}</span>
                                                    </span>
                                                </a>
                                            </li> :
                                            null
                                        }
                                        {item.CAN_WRITE ?
                                            <li>
                                                <a onClick={e => this.handleAction(e, () => this.props.showRemoveItemConfirm(item))}
                                                    href="javascript:void(0);">
                                                    <span>
                                                        <i className="glyphicon glyphicon-trash" dangerouslySetInnerHTML={{__html: '&nbsp;'}}></i>
                                                        <span>{help.t('DEL')}</span>
                                                    </span>
                                                </a>
                                            </li> :
                                            null
                                        }
                                    </ul>
                                }
                            </div>
                    </div>
                </td>
                <td className="column-url">
                    {link.isLink
                        ? <a
                            className="url-link-icon"
                            target="_blank"
                            rel="noopener noreferrer"
                            href={link.text}
                            title={link.text}
                            aria-label={link.text}
                            onClick={this.openLink}>
                            <span className="glyphicon glyphicon-link" aria-hidden="true"></span>
                        </a>
                        : null}
                </td>
                <td>
                    <a className={classes(`favorite-toogle small`, {open: !item.isFavorite })}
                       onClick={(e) => changeFavorite(item.ID, false)}
                       href="javascript:void(0);">
                    </a>
                </td>
            </tr>
        )
    }
});
