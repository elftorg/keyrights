const zxcvbn = require('zxcvbn');
const extend = require('extend');
const moment = require('moment');

const _passScores = [
    {class: 'label-danger', title: String._('PASS_STRENGTH_LOW')},
    {class: 'label-danger', title: String._('PASS_STRENGTH_LOW')},
    {class: 'label-warning', title: String._('PASS_STRENGTH_SOSO')},
    {class: 'label-success', title: String._('PASS_STRENGTH_NICE')},
    {class: 'label-success', title: String._('PASS_STRENGTH_NICE')}
];

const _accessStates = {NO_ACCESS: 1, HAS_ACCESS_READ: 2, HAS_ACCESS_WRITE: 3, HAS_ACCESS_OWN: 4, INHERITS: 5};

const help = {
    sortByNameAsc(a, b) {
        let nameA = a.NAME.toLowerCase().trim();
        let nameB = b.NAME.toLowerCase().trim();

        if (nameA < nameB) return -1;
        if (nameA > nameB) return 1;

        return 0;
    },

    t(messageId) {
        if (window.CONST && window.CONST.translator && window.CONST.translator[messageId]) {
            return window.CONST.translator[messageId];
        }
        return messageId;
    },

    sortByNameDesc(a, b) {
        let nameA = a.NAME.toLowerCase();
        let nameB = b.NAME.toLowerCase();

        if (nameA < nameB) return 1;
        if (nameA > nameB) return -1;

        return 0;
    },

    getInherited(item, tree) {
        const items = this.getItemParents(item.element, tree.sections, tree.index);
        const mappedRights = {};

        items.reverse().map(p => p.RIGHTS.map(r => {
            if (r.user) {
                mappedRights[`u${r.user}`] = r;
            } else {
                mappedRights[`g${r.group}`] = r;
            }
        }));

        item.element.RIGHTS.map(r => {
            if (r.user) {
                if (mappedRights[`u${r.user}`]) delete mappedRights[`u${r.user}`];
            } else {
                if (mappedRights[`g${r.group}`]) delete mappedRights[`g${r.group}`];
            }
        });

        return Object.keys(mappedRights).map(k => mappedRights[k]);
    },

    getItemParents(item, sections, index) {
        const res = [];
        while (item.SECTION !== false) {

            const dad = sections[index[item.SECTION]];
            res.push(dad);

            item = dad;
        }

        return res;
    },

    _folderRights(folder, user) {
        const userId = parseInt(user.ID);
        const rights = folder.RIGHTS;

        if (user.admin) return _accessStates.HAS_ACCESS_OWN;

        if (parseInt(folder.OWNER) === parseInt(user.ID)) return _accessStates.HAS_ACCESS_OWN;

        let now = moment();

        // check user
        if (rights.filter(r => !r.blocked && r.user && parseInt(r.user) === userId && (r.timed && moment(r.timed).isBefore(now))).length) return _accessStates.NO_ACCESS;
        if (rights.filter(r => !r.blocked && r.user && parseInt(r.user) === userId && !r.edit).length) return _accessStates.HAS_ACCESS_READ;
        if (rights.filter(r => !r.blocked && r.user && parseInt(r.user) === userId && r.edit).length) return _accessStates.HAS_ACCESS_WRITE;
        if (rights.filter(r => r.blocked && r.user && parseInt(r.user) === userId).length) return _accessStates.NO_ACCESS;

        // check groups
        if (rights.filter(r => !r.blocked && user.UF_DEPARTMENT.indexOf(r.group) !== -1 && (r.timed && moment(r.timed).isBefore(now))).length) return _accessStates.NO_ACCESS;
        if (rights.filter(r => !r.blocked && user.UF_DEPARTMENT.indexOf(r.group) !== -1 && !r.edit).length) return _accessStates.HAS_ACCESS_READ;
        if (rights.filter(r => !r.blocked && user.UF_DEPARTMENT.indexOf(r.group) !== -1 && r.edit).length) return _accessStates.HAS_ACCESS_WRITE;
        if (rights.filter(r => r.blocked && user.UF_DEPARTMENT.indexOf(r.group) !== -1).length) return _accessStates.NO_ACCESS;

        return _accessStates.INHERITS;
    },

    getItemKids(id, sections) {
        return sections.filter(s => s.ALL_PARENTS.indexOf(id) !== -1).map(t => parseInt(t.ID));
    },

    getFolderAccessObj(folder, user, sections, index) {
        const accessLevel = this._folderRights(folder, user);
        if (accessLevel === _accessStates.NO_ACCESS) return false;

        if (accessLevel === _accessStates.INHERITS && sections) {
            let parentId = folder.SECTION;
            while (parentId || parentId !== false) {
                let parent = sections[index[parentId]];
                let parentAccessLevel = this._folderRights(parent, user);

                if (parentAccessLevel === _accessStates.NO_ACCESS) return false;
                if (parentAccessLevel === _accessStates.HAS_ACCESS_WRITE || parentAccessLevel === _accessStates.HAS_ACCESS_OWN) {
                    return {
                        CAN_WRITE: parentAccessLevel === _accessStates.HAS_ACCESS_WRITE || parentAccessLevel === _accessStates.HAS_ACCESS_OWN,
                        CAN_OWN: parentAccessLevel === _accessStates.HAS_ACCESS_OWN
                    };
                };

                parentId = parent.SECTION;
            }
        }

        return {
            CAN_WRITE: accessLevel === _accessStates.HAS_ACCESS_WRITE || accessLevel === _accessStates.HAS_ACCESS_OWN,
            CAN_OWN: accessLevel === _accessStates.HAS_ACCESS_OWN
        };
    },

    unique(arr) {
        var obj = arr.reduce((unique, value) => {
            unique[value] = value;
            return unique;
        }, {});

        return Object.keys(obj).map(k => obj[k]);
    },

    fieldToKey(arr, field) {
        let res = {};
        arr.forEach(a => {
            res[a[field]] = a;
        });

        return res;
    },
    parseUrl(e, t) {
        var n, r = ["source", "scheme", "authority", "userInfo", "user", "pass", "host", "port", "relative", "path", "directory", "file", "query", "fragment"], i = this.php_js && this.php_js.ini || {}, s = i["phpjs.parse_url.mode"] && i["phpjs.parse_url.mode"].local_value || "php", o = {
            php: /^(?:([^:\/?#]+):)?(?:\/\/()(?:(?:()(?:([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?()(?:(()(?:(?:[^?#\/]*\/)*)()(?:[^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
            strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
            loose: /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/\/?)?((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
        };
        var u    = o[s].exec(e), a = {}, f = 14;
        while (f--) {if (u[f]) {a[r[f]] = u[f]}}
        if (t) {return a[t.replace("PHP_URL_", "").toLowerCase()]}
        if (s !== "php") {
            var l = i["phpjs.parse_url.queryKey"] && i["phpjs.parse_url.queryKey"].local_value || "queryKey";
            o     = /(?:^|&)([^&=]*)=?([^&]*)/g;
            a[l]  = {};
            n     = a[r[12]] || "";
            n.replace(o, function(e, t, n) {if (t) {a[l][t] = n}})
        }
        delete a.source;
        return a
    },

    checkPassStrength(p) {
        if (!p) {
            return _passScores[0];
        } else if (p.length >= 20) {
            return _passScores[4];
        } else {
            const result = zxcvbn(p);
            return _passScores[result.score];
        }
    },

    /**
     * Get random string with specified length
     * @param len int
     * @returns {string}
     */
    strRand(len) {
        const secureCrypto = window.crypto || window.msCrypto;
        if (!secureCrypto || typeof secureCrypto.getRandomValues !== 'function') {
            throw new Error('Secure random number generator is unavailable');
        }
        const words = '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
        const maxUnbiased = Math.floor(0x100000000 / words.length) * words.length;
        const random = new Uint32Array(1);
        let result = '';

        for (var idx = len; idx > 0; idx--) {
            do {
                secureCrypto.getRandomValues(random);
            } while (random[0] >= maxUnbiased);
            result += words.charAt(random[0] % words.length);
        }

        return result;
    }
};

module.exports = help;
