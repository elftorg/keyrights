const dayPickerModule = require('react-day-picker');

const DatePicker = dayPickerModule.default || dayPickerModule;
const DateUtilsModule = dayPickerModule.DateUtils || {};
const DateUtils = DateUtilsModule.default || DateUtilsModule;

module.exports = {
    DatePicker,
    DateUtils
};
