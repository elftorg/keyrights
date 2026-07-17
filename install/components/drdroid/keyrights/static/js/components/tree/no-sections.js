const React = require('react');
const help = require('../../helpers/helpers');
const UiState = require('../ui/state');

module.exports = ({onAdd, addFolderPopup, canEditRoot}) => (
    <div className="wrapper wrapper-left">
        <UiState
            compact={true}
            type="folder"
            title={help.t('SECTION_ADD_TIP')}
            text={help.t('EMPTY_TREE_HINT')}
            action={canEditRoot ? addFolderPopup : null}
            actionText={help.t('SECTION_ADD')}
        />
    </div>
);
