window.Keypad = new class {
    open(options) {
        Event.fire('keypad-options', options || {});
        Event.fire('keypad', true);

        return new Promise((resolve, reject) => {
            Event.listenOnce('keypad-confirm', value => resolve(value));
            Event.listenOnce('keypad-cancel', reject);
        });
    }

    close() {
        Event.fire('keypad', false);
    }
}