/* SqlWizard Lite — Undo/Redo history */
window.SqlWizardHistory = (function (window, document) {
    'use strict';

    var MAX = 50;
    var stack = [];       // estados anteriores
    var future = [];      // estados para redo
    var lastState = null;

    function clone(obj) {
        return JSON.parse(JSON.stringify(obj));
    }

    function push(state) {
        var current = clone(state);
        if (lastState && JSON.stringify(lastState) === JSON.stringify(current)) return;
        if (lastState) {
            stack.push(lastState);
            if (stack.length > MAX) stack.shift();
        }
        lastState = current;
        future = [];
    }

    function undo() {
        if (!stack.length) return false;
        var prev = stack.pop();
        if (lastState) future.push(lastState);
        lastState = prev;
        apply(prev);
        return true;
    }

    function redo() {
        if (!future.length) return false;
        var next = future.pop();
        if (lastState) stack.push(lastState);
        lastState = next;
        apply(next);
        return true;
    }

    function apply(state) {
        if (window.SqlWizardCanvas && window.SqlWizardCanvas.setState) {
            window.SqlWizardCanvas.setState(clone(state));
        }
        // Não gravar no history ao aplicar
        if (window.SqlWizardHistory._applying) return;
        window.SqlWizardHistory._applying = true;
        setTimeout(function () { window.SqlWizardHistory._applying = false; }, 10);
    }

    function reset(state) {
        stack = [];
        future = [];
        lastState = state ? clone(state) : null;
    }

    return {
        push: push,
        undo: undo,
        redo: redo,
        reset: reset,
        canUndo: function () { return stack.length > 0; },
        canRedo: function () { return future.length > 0; },
        stackSize: function () { return stack.length; },
        futureSize: function () { return future.length; },
        _applying: false
    };
})(window, document);
