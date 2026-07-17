const React      = require('react');
const classes    = require('classnames');
const extend     = require('extend');
const help = require('../../../helpers/helpers');

const UserElement = ({user, remove, isSelected, select, index}) => (
    <li title={`[${user.ID}] ${user.NAME} ${user.LAST_NAME} (${user.EMAIL})`}
        className={classes({selected: isSelected})}>
        <a onClick={() => select(user)} href="javascript:void(0);">
            <span>{`[${user.ID}] ${user.NAME} ${user.LAST_NAME} (${user.EMAIL})`}</span>
            {//isSelected
                //   ? <span className="minus glyphicon-minus glyphicon" title={help.t('DEL')}></span>
                //    : <span className="plus glyphicon-plus glyphicon" title={help.t('ADD')}></span>
            }
        </a>
    </li>
);

const TabUsers = React.createClass({
    getInitialState() {
        return {
            q: ''
        };
    },
    render() {
        const {selectedUsers, select, users, remove} = this.props;
        const {q} = this.state;
        const filteredUsers = users.filter(u => {
            if (!(u.ACTIVE === 'Y' || u.ACTIVE === true)) {
                return false;
            }

            if (!q.trim()) {
                return true;
            }

            const query = q.toLowerCase();
            return Boolean(
                u.NAME && u.NAME.toLowerCase().indexOf(query) !== -1
                || u.LAST_NAME && u.LAST_NAME.toLowerCase().indexOf(query) !== -1
                || u.EMAIL && u.EMAIL.toLowerCase().indexOf(query) !== -1
            );
        });

        return (
            <div className="tab-pane active" id="users">
                <form onSubmit={e => e.preventDefault()} className="form-horizontal">
                    <div className="search-wrapper">
                        <input
                            value={q}
                            ref={node => this.search = node}
                            onChange={() => this.setState({q: this.search.value})}
                            type="text"
                            className="form-control"
                            id="filter-groups"/>
                        <i className="icon-search"></i>
                    </div>
                </form>
                <div className="user-add-collapsible-list">
                    <ul>
                        {filteredUsers.map((u, k) => {
                            let selected = selectedUsers.filter(user => user.ID === u.ID).length;


                            return (
                                <UserElement
                                    index={selectedUsers.indexOf(u)}
                                    select={!selected ? select : null}
                                    remove={selected ? remove : null}
                                    isSelected={selected}
                                    key={k}
                                user={u}/>
                            )
                        })}
                    </ul>
                    {!filteredUsers.length
                        ? <div className="keyrights-list-empty">{help.t('EMPTY_SEARCH_HINT')}</div>
                        : null}
                </div>
            </div>
        )
    }
});

module.exports = TabUsers;
