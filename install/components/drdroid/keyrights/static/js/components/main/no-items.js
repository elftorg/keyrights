const React = require('react');
const help = require('../../helpers/helpers');
const FolderNote = require('./folder-note');
const UiState = require('../ui/state');

module.exports = ({isSearching, isFavoritesOpened, activeFolderItem, showEditFolderPopup}) => {
    const text = isSearching ? help.t('SEARCH_FOUND_NOTHING') : isFavoritesOpened ? help.t('EMPTY_FAVORITE_LIST') : help.t('EMPTY_MESSAGE_TIP');
    return (
        <div className="wrapper wrapper-main">
            <UiState
                type={isSearching ? 'search' : isFavoritesOpened ? 'favorite' : 'password'}
                title={text}
                text={isSearching ? help.t('EMPTY_SEARCH_HINT') : help.t('EMPTY_LIST_HINT')}
            />
            {activeFolderItem && activeFolderItem.ID > 0 ? <FolderNote folder={activeFolderItem} showEditFolderPopup={showEditFolderPopup} /> : null}
        </div>
    );
};
