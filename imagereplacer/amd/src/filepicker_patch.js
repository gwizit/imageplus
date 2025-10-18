'use strict';

define(['core/yui'], function(Y) {
    return {
        init: function() {
            if (!window.M || !M.core_filepicker || !M.core_filepicker.prototype) {
                return;
            }

            var proto = M.core_filepicker.prototype;
            if (proto.__imagereplacer_patched) {
                return;
            }

            var originalSetup = proto.setup_toolbar;
            proto.setup_toolbar = function() {
                var filemanager = this.filemanager;
                if (!filemanager) {
                    return originalSetup.apply(this, arguments);
                }

                if (!filemanager.one) {
                    filemanager = Y.one(filemanager);
                }

                if (!filemanager) {
                    return originalSetup.apply(this, arguments);
                }

                var content = filemanager.one('.fp-content');
                if (!content) {
                    content = filemanager;
                }

                var toolbar = content.one('.fp-toolbar');
                if (!toolbar) {
                    toolbar = Y.Node.create('<div class="fp-toolbar"></div>');
                    var search = Y.Node.create('<div class="fp-tb-search"></div>');
                    var buttons = Y.Node.create('<div class="fp-tb-buttons"></div>');
                    toolbar.append(search);
                    toolbar.append(buttons);
                    content.prepend(toolbar);
                }

                this.filemanager = filemanager;
                this.toolbar = toolbar;

                try {
                    return originalSetup.apply(this, arguments);
                } catch (e) {
                    window.console && console.warn && console.warn('Filepicker toolbar patch caught error', e);
                }
            };

            var originalDisplay = proto.display_response;
            proto.display_response = function(json) {
                try {
                    return originalDisplay.call(this, json);
                } catch (e) {
                    if (e && e.message && e.message.indexOf('activenode') !== -1) {
                        this.activeview = null;
                        return originalDisplay.call(this, json);
                    }
                    throw e;
                }
            };

            proto.__imagereplacer_patched = true;
        }
    };
});
