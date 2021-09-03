// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Comment section modal.
 *
 * @module     core_comment/comments
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core_comment/comments', 'core/modal', 'core/modal_registry'],
    function(Comments, Modal, ModalRegistry) {

        let registered = false;

        /**
         * Constructor for the Modal.
         *
         * @param {object} root The root jQuery element for the modal
         */
        const ModalCommentSection = function(root) {
            Modal.call(this, root);
        };

        ModalCommentSection.TYPE = 'core_comment-comment_section';
        ModalCommentSection.prototype = Object.create(Modal.prototype);
        ModalCommentSection.prototype.constructor = ModalCommentSection;

        /**
         * Set the comment section options.
         *
         * @param {object} options The comment section options
         */
        ModalCommentSection.prototype.setOptions = function(options) {
            this.options = options;
        };

        /**
         * Get the comment section options.
         *
         * @return {object} The comment section options
         */
        ModalCommentSection.prototype.getOptions = function() {
            return this.options;
        };

        ModalCommentSection.prototype.show = function() {
            const el = this.getBody().find('[data-commentsection]')[0];
            Comments.initCommentSection(el, this.getOptions());
            Modal.prototype.show.call(this);
        };

        // Automatically register with the modal registry the first time this module is imported so that you can create modals
        // of this type using the modal factory.
        if (!registered) {
            ModalRegistry.register(ModalCommentSection.TYPE, ModalCommentSection, 'core_comment/modal_comment_section');
            registered = true;
        }

        return ModalCommentSection;
    });