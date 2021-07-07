define(['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal', 'core/modal_registry'],
    function($, Notification, CustomEvents, Modal, ModalRegistry) {

        var registered = false;
        var SELECTORS = {
            DELETE_BUTTON: '[data-action="delete"]',
            CANCEL_BUTTON: '[data-action="cancel"]',
        };

        /**
         * Constructor for the Modal.
         *
         * @param {object} root The root jQuery element for the modal
         */
        var ModalDeleteComment = function(root) {
            Modal.call(this, root);

            if (!this.getFooter().find(SELECTORS.DELETE_BUTTON).length) {
                Notification.exception({message: 'No delete button found'});
            }

            if (!this.getFooter().find(SELECTORS.CANCEL_BUTTON).length) {
                Notification.exception({message: 'No cancel button found'});
            }
        };

        ModalDeleteComment.TYPE = 'core_comment-delete-comment';
        ModalDeleteComment.prototype = Object.create(Modal.prototype);
        ModalDeleteComment.prototype.constructor = ModalDeleteComment;

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        ModalDeleteComment.prototype.registerEventListeners = function() {
            // Apply parent event listeners.
            Modal.prototype.registerEventListeners.call(this);

            this.getModal().on(CustomEvents.events.activate, SELECTORS.DELETE_BUTTON, function(e, data) {
                // TODO delete handler
            }.bind(this));

            this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function(e, data) {
                // TODO cancel handler
            }.bind(this));
        };

        // Automatically register with the modal registry the first time this module is imported so that you can create modals
        // of this type using the modal factory.
        if (!registered) {
            ModalRegistry.register(ModalDeleteComment.TYPE, ModalDeleteComment, 'core_comment/modal_delete_comment');
            registered = true;
        }

        return ModalDeleteComment;
    });