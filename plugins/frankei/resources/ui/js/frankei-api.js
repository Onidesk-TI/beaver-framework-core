window.FrankeiApi = (function () {
    'use strict';

    var BASE = '/api/frankei';

    function request(method, path, body) {
        var opts = {
            method: method,
            headers: { 'Accept': 'application/json' }
        };
        if (body !== undefined) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        return fetch(BASE + path, opts).then(function (r) {
            return r.json().catch(function () { return null; })
                .then(function (data) {
                    if (!r.ok) {
                        var msg = (data && data.error) || ('HTTP ' + r.status);
                        var err = new Error(msg);
                        err.status = r.status;
                        err.data = data;
                        throw err;
                    }
                    return data;
                });
        });
    }

    return {
        listScanners: function () {
            return request('GET', '/scanners');
        },
        scan: function (payload) {
            return request('POST', '/scan', payload);
        }
    };
})();
