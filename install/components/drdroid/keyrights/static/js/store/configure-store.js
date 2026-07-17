const {compose, createStore, applyMiddleware } = require('redux');
const reduxThunkModule = require('redux-thunk');
const reduxThunk = reduxThunkModule.default || reduxThunkModule.thunk || reduxThunkModule;

const rootReducer = require('../reducers');

const store = compose(
    applyMiddleware(reduxThunk)
)(createStore);

module.exports = store(rootReducer);
