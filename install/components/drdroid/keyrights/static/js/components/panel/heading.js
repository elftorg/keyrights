const React = require('react');

const Item = ({item, heading, edit, isLoading}) => {
    return (
        <tr>
            <th>
                <span className="record-detail-icon" aria-hidden="true">
                    <span className="glyphicon glyphicon-lock"></span>
                </span>
                {heading}
            </th>
            {item.isNew || item.isEdit || isLoading || item.element.CAN_WRITE !== true ? null : <Edit clickHandler={() => edit(item.element)} />}
        </tr>
    );
};

const Edit = ({clickHandler}) => (
    <th className="edit-link">
        <a className="glyphicon glyphicon-pencil" onClick={clickHandler} href="javascript:void(0);"></a>
    </th>
);

const Folder = ({item, heading, edit, isLoading}) => (
    <tr>
        <th>
            <i className={`icon-folder icon-folder-sprite folder-${item.element.ICON}`}></i>
            {heading}
        </th>
        {isLoading || item.element.CAN_WRITE !== true || parseInt(item.element.ID) === 0 ? null : <Edit clickHandler={() => edit(item.element)} />}
    </tr>
);

module.exports = (props) => (
    <table className="table sidepanel-header">
        <thead>
        {!props.item.isFolder
            ? <Item {...props} />
            : <Folder {...props} />
        }
        </thead>
    </table>
);
