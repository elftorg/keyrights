const ActionTypes = require('../constants/action-types');
const extend = require('extend');
const toCsv = require('../helpers/csv');
const help = require('../helpers/helpers');
const crypt = require('../helpers/crypt');
const api = require('../helpers/api');

const LIST_SECTIONS_URL = 'crypt/section/list/';
const IMPORT_URL = 'exchange/import/';
const LIST_ITEMS_URL = 'crypt/password/list/';
const LIST_RIGHTS_URL = 'crypt/rights/list/';
const CALL_METHOD_URL = 'api/call-method';
const SAVE_FOLDER_URL = 'crypt/section/save/';
const MOVE_FOLDER_URL = 'crypt/section/move/';
const MOVE_ITEM_URL = 'crypt/password/move/';
const REMOVE_ITEM_URL = 'crypt/password/remove/';
const REMOVE_FOLDER_URL = 'crypt/section/remove/';
const SAVE_RIGHTS_URL = 'crypt/rights/save/';
const SAVE_ITEM_URL = 'crypt/password/save/';
const CHANGE_OWNER_URL = 'crypt/set-owner/';
const HISTORY_URL = 'exchange/history/';
const COPY_LOGGER = 'exchange/copy';
const LIST_ITEMS_URL_FOR_ID = 'crypt/password/list-for-id/';
const REMOVE_RIGHTS_URL = 'crypt/rights/remove';

const handleApiError = (dispatch, error) => {
    dispatch(showAlert(
        error && error.message
            ? error.message
            : 'Не удалось выполнить запрос к KeyRights'
    ));
};

const runApi = (dispatch, promise, onSuccess) => promise
    .then(data => {
        if (onSuccess) {
            onSuccess(data);
        }

        return data;
    })
    .catch(error => {
        handleApiError(dispatch, error);
        return null;
    });
const settleRequest = (promise, fallback) => promise
    .then(value => ({value, error: null}))
    .catch(error => ({value: fallback, error}));

const fetchData = (currentUser, forId = false) => dispatch => {
    const itemParams = {};

    if (forId) {
        itemParams.isGroup = Boolean(currentUser.isGroup);
        itemParams.forId = itemParams.isGroup
            ? currentUser.UF_DEPARTMENT[0]
            : currentUser.ID;
    }

    const treePromise = api.get(LIST_SECTIONS_URL, itemParams).then(sections => (
        (sections || []).map(section => {
            if (section.SECTION === '') {
                return extend({}, section, {SECTION: false});
            }

            return section;
        })
    ));

    const itemsPromise = api.get(
        forId ? LIST_ITEMS_URL_FOR_ID : LIST_ITEMS_URL,
        itemParams
    );

    const usersPromise = api.post(CALL_METHOD_URL, {
        method: 'user.get',
        params: {SORT: 'ID', ORDER: 'ASC'}
    });

    const groupsPromise = api.post(CALL_METHOD_URL, {
        method: 'department.get',
        params: {SORT: 'ID', ORDER: 'DESC'}
    });

    return Promise.all([
        settleRequest(treePromise, []),
        settleRequest(itemsPromise, []),
        settleRequest(usersPromise, []),
        settleRequest(groupsPromise, [])
    ]).then(results => {
        dispatch(setData(currentUser, results.map(result => result.value)));

        const failedRequests = results.filter(result => result.error);
        if (failedRequests.length) {
            const messages = failedRequests
                .map(result => result.error && result.error.message)
                .filter((message, index, all) => message && all.indexOf(message) === index);

            dispatch(showAlert(messages.join('\n') || 'Не все данные KeyRights удалось загрузить'));
        }
    });
};
function openFolder(id, user) {
    window.location.hash = '#/' + id;

    return dispatch => {
        dispatch({type: ActionTypes.OPEN_FOLDER, id});

        return runApi(
            dispatch,
            api.post(LIST_RIGHTS_URL, {section: id}),
            data => dispatch(folderIsOpened(data, user))
        );
    };
}

function openItem(id) {
    const path = window.location.hash.substr(2).split('/');
    path[1] = id;
    window.location.hash = '#/' + path.join('/');

    return dispatch => {
        dispatch({type: ActionTypes.OPEN_ITEM, id});

        return runApi(
            dispatch,
            api.post(LIST_RIGHTS_URL, {item: id}),
            data => dispatch(itemIsOpened(extend({}, data, {ID: id})))
        );
    };
}

const removeFolder = item => dispatch => runApi(
    dispatch,
    api.post(REMOVE_FOLDER_URL, {sectionId: item.ID}),
    () => dispatch(_folderRemoved(item))
);

const addFolder = (data, user) => dispatch => runApi(
    dispatch,
    api.post(SAVE_FOLDER_URL, data),
    response => {
        response.section.CAN_WRITE = true;
        dispatch(_folderAdded(response));
        dispatch(openFolder(response.section.ID, user));
    }
);

const moveFolder = (id, to) => dispatch => {
    dispatch({type: ActionTypes.MOVE_FOLDER, id, to});

    return runApi(
        dispatch,
        api.post(MOVE_FOLDER_URL, {id, idNewParentFolder: to})
    );
};

const moveItem = (entityId, idNewFolder, idOldFolder) => dispatch => {
    dispatch({type: ActionTypes.MOVE_ITEM, entityId, idNewFolder, idOldFolder});

    return runApi(
        dispatch,
        api.post(MOVE_ITEM_URL, {entityId, idNewFolder, idOldFolder})
    );
};

const _importRec = dispatch => runApi(
    dispatch,
    api.post(IMPORT_URL, {step: 'next'}),
    result => {
        if (result === 'progress') {
            _importRec(dispatch);
            return;
        }

        dispatch(showAlert(help.t('IMPORT_FINISHED')));
        dispatch({type: ActionTypes.IMPORT_IS_DONE});
    }
);

const importData = data => dispatch => runApi(
    dispatch,
    api.post(IMPORT_URL, {data}),
    result => {
        if (result === 'progress') {
            _importRec(dispatch);
            return;
        }

        dispatch(showAlert(help.t('IMPORT_FINISHED')));
        dispatch({type: ActionTypes.IMPORT_IS_DONE});
        window.location.reload();
    }
);

const changeOwner = ({entityId, owner, sectionId}, user) => dispatch => {
    dispatch({
        type: ActionTypes.CHANGE_OWNER_START,
        sectionId,
        entityId,
        owner,
        user
    });

    return runApi(
        dispatch,
        api.post(CHANGE_OWNER_URL, {entityId, owner, sectionId}),
        () => dispatch({type: ActionTypes.CHANGE_OWNER_END})
    );
};

const editFolder = data => dispatch => {
    dispatch(_folderChanged(data));

    return runApi(
        dispatch,
        api.post(SAVE_FOLDER_URL, data),
        response => dispatch(_folderChanged(response.section))
    );
};

const saveRights = (data, user) => dispatch => {
    dispatch({type: ActionTypes.SAVE_RIGHTS_START, data, user});

    return runApi(
        dispatch,
        api.post(SAVE_RIGHTS_URL, data),
        () => {
            if (data.entityId) {
                dispatch({type: ActionTypes.SAVE_ITEM_RIGHTS_END, data, user});
            } else {
                dispatch({type: ActionTypes.SAVE_FOLDER_RIGHTS_END, data});
            }
        }
    );
};

const removeItem = item => dispatch => {
    dispatch({type: ActionTypes.REMOVE_ITEM, item});

    return runApi(
        dispatch,
        api.post(REMOVE_ITEM_URL, {
            entityId: parseInt(item.ID),
            sectionId: parseInt(item.SECTION)
        })
    );
};

const addItem = data => dispatch => runApi(
    dispatch,
    api.post(SAVE_ITEM_URL, data),
    response => dispatch(_itemAdded(extend({}, data, response, {ID: response.result})))
);

const saveEditedItem = data => dispatch => {
    dispatch({type: ActionTypes.ITEM_EDIT_START, data});

    return runApi(
        dispatch,
        api.post(SAVE_ITEM_URL, data),
        response => dispatch(_itemChanged(extend({}, data, response)))
    );
};

const copyLogger = itemId => dispatch => runApi(
    dispatch,
    api.post(COPY_LOGGER, {item_id: itemId})
);

const getHistory = params => dispatch => runApi(
    dispatch,
    api.post(HISTORY_URL, params),
    data => {
        const fields = [
            help.t('REPORT_WHEN'),
            help.t('REPORT_WHO'),
            help.t('REPORT_WHAT'),
            help.t('REPORT_DO')
        ];

        const csv = toCsv(data, ['date', 'user', 'name', 'action'], fields);
        const link = document.createElement('a');
        link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv));
        link.setAttribute('download', 'history.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        dispatch(showAlert(help.t('SUCCES_HISTORY_EXPORT')));
    }
);

const removeRights = data => dispatch => runApi(
    dispatch,
    api.post(REMOVE_RIGHTS_URL, {data}),
    () => dispatch({type: ActionTypes.REMOVE_RIGHTS_IS_DONE})
);
const setData = (user, data) => {
    return {
        type: ActionTypes.END_FETCH_DATA,
        sections: data[0],
        items: data[1],
        users: data[2],
        groups: data[3],
        currentUser: user
    };
};

const folderIsOpened = (data, user) => {
    return {
        type: ActionTypes.FOLDER_IS_OPENED,
        data,
        user
    }
};

const itemIsOpened = (data) => {
    return {
        type: ActionTypes.ITEM_IS_OPENED,
        data
    }
};

function newItem() {
    return {
        type: ActionTypes.NEW_ITEM_FORM,
    }
}

function editItem(data) {
    return {
        type: ActionTypes.EDIT_ITEM_FORM,
        data
    }
}

function toggleSort(state) {
    return {
        type: ActionTypes.CHANGE_MAIN_SORT,
        state
    }
}


const changeFavorite = (id, isFolder) => {
    var arrayFavorite = localStorage.db ? JSON.parse(localStorage.db) : {folders: [], items: []};
    if (isFolder) {
        if (arrayFavorite.folders.indexOf(parseInt(id)) >= 0) {
            arrayFavorite.folders = arrayFavorite.folders.filter(function (val) {
                return (val != id)
            });
        } else {
            arrayFavorite.folders.push(parseInt(id));
        }
        localStorage.db = JSON.stringify(arrayFavorite);
        return {
            type: ActionTypes.CHANGE_FAVORITE_FOLDER,
            arrayFavorite
        }

    }
    if (arrayFavorite.items.indexOf(parseInt(id)) >= 0) {
        arrayFavorite.items = arrayFavorite.items.filter(function (val) {
            return (val != id)
        });
    } else {
        arrayFavorite.items.push(parseInt(id));
    }
    localStorage.db = JSON.stringify(arrayFavorite);
    return {
        type: ActionTypes.CHANGE_FAVORITE_ITEM,
        arrayFavorite
    }

};
const showFavorite = () => {
    return {
        type: ActionTypes.SHOW_FAVORITE,
    }
};

const hideFavorite = () => {
    return {
        type: ActionTypes.HIDE_FAVORITE,
    }
};

const showAddFolderPopup = (data = null) => {
    return {
        type: ActionTypes.SHOW_ADD_FOLDER_POPUP,
        data
    }
};

const showImportPopup = () => {
    return {
        type: ActionTypes.SHOW_IMPORT_POPUP
    }
};

const showRemoveFolderConfirm = (id = null) => {
    return {
        type: ActionTypes.SHOW_REMOVE_FOLDER_CONFIRM,
        id
    }
};

const showEditFolderPopup = (data) => {
    return {
        type: ActionTypes.SHOW_EDIT_FOLDER_POPUP,
        data
    }
};

const closeModal = () => {
    return {
        type: ActionTypes.CLOSE_MODAL
    }
};

const searchInput = (q) => {
    return {
        type: ActionTypes.SEARCH_INPUT,
        q
    };
};

const toggleSearch = (state) => {
    return {
        type: ActionTypes.TOGGLE_SEARCH,
        state
    };
};

const addUsers = (data) => {
    return {
        type: ActionTypes.ADD_USERS,
        isSection: data.isSection,
        id: data.id
    };
};

const showChangeOwnerPopup = (data) => {
    return {
        type: ActionTypes.CHANGE_OWNER,
        isSection: data.isSection,
        id: data.id
    };
};

const showAlert = (text) => {
    return {
        type: ActionTypes.ALERT,
        text
    };
};

const closeAlert = () => {
    return {type: ActionTypes.CLOSE_ALERT};
};


const showRemoveItemConfirm = item => ({type: ActionTypes.REMOVE_ITEM_CONFIRM, item});

const closeNewItem = () => {
    return {
        type: ActionTypes.CLOSE_NEW_ITEM
    }
};

const _folderAdded = (data) => {
    return {
        type: ActionTypes.FOLDER_IS_ADDED,
        data
    }
};

const _folderRemoved = (item) => {
    return {
        type: ActionTypes.FOLDER_IS_REMOVED,
        item
    }
};

const _folderChanged = (data) => {
    return {
        type: ActionTypes.FOLDER_IS_EDITED,
        data
    }
};

const _itemChanged = (data) => {
    return {
        type: ActionTypes.ITEM_IS_EDITED,
        data
    }
};

const _itemAdded = (data) => {
    return {
        type: ActionTypes.ITEM_IS_ADDED,
        data
    }
};

const showHistoryPopup = () => {
    return {
        type: ActionTypes.SHOW_HISTORY_POPUP
    }
};

const exportData = () => {
    return (dispatch, getState) => {
        const state = getState();
        const sections = state.tree.tree.sections;
        const index = state.tree.tree.index;
        const items = state.items.items;

        const data = items.entities.map(item => {
            const dad = sections[index[item.SECTION]];
            const decrypt = crypt.decrypt(item.CRYPTED);

            let row = {
                ['Password Groups']: dad.NAME,
                ['Group Tree']: dad.SECTION == 0 ? '' : sections[index[dad.SECTION]].NAME,
                ['Account']: item.NAME,
                ['Login Name']: decrypt.LOGIN,
                ['Password']: decrypt.PASSWORD,
                ['Web Site']: decrypt.URL,
                ['Comments']: decrypt.NOTE
            };

            return row;
        });

        if (!data.length) return;

        const fields = Object.keys(data[0]);
        const csv = toCsv(data, fields);
        const link = document.createElement('a');
        document.body.appendChild(link);
        link.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv));
        link.setAttribute('download', 'backup.csv');
        link.click();
        document.body.removeChild(link);
    };
};

const showViewUserPopup = () => {
    return {
        type: ActionTypes.SHOW_VIEW_USER_POPUP,
    }
};

const viewUser = (item) => {
    return {
        type: ActionTypes.VIEW_USER,
        item
    }
};

const resetViewUser = () => {
  return {
      type: ActionTypes.RESET_VIEW_USER
  }
};

const showRemoveRightsPopup = () => {
    return {
        type: ActionTypes.SHOW_REMOVE_RIGTHS_POPUP
    }
};

module.exports = {
    fetchData,
    openFolder,
    openItem,
    toggleSort,
    showAddFolderPopup,
    showRemoveFolderConfirm,
    showEditFolderPopup,
    showImportPopup,
    closeModal,
    addFolder,
    editFolder,
    searchInput,
    newItem,
    editItem,
    closeNewItem,
    addItem,
    addUsers,
    moveFolder,
    moveItem,
    saveEditedItem,
    saveRights,
    removeFolder,
    toggleSearch,
    changeOwner,
    showChangeOwnerPopup,
    showAlert,
    closeAlert,
    removeItem,
    showRemoveItemConfirm,
    importData,
    exportData,
    changeFavorite,
    showFavorite,
    hideFavorite,
    getHistory,
    showHistoryPopup,
    copyLogger,
    showViewUserPopup,
    viewUser,
    resetViewUser,
    showRemoveRightsPopup,
    removeRights
};
