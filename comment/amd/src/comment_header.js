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
 * Comment header module.
 *
 * @module     core_comment/comments
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';
import * as ModalFactory from 'core/modal_factory';
import * as ModalEvents from 'core/modal_events';
import * as Str from 'core/str';

export default class CommentHeader extends Component {

    constructor(descriptor) {
        super(descriptor);
        this.commentId = Number(this.element.dataset.commentid);
        if (!Number.isInteger(this.commentId)) {
            throw new Error('commentId missing in dataset');
        }
    }

    create() {
        this.selectors = {
            DELETE_COMMENT: `[data-for="deletecomment"]`,
            EDIT_COMMENT: `[data-for="editcomment"]`,
            COPY_COMMENT_URL: `[data-for="copycommenturl"]`,
        };
    }

    getWatchers() {
        const commentId = this.element.dataset.commentid;
        return [
            {watch: `comments[${commentId}].fullname:updated`, handler: this.render},
            {watch: `comments[${commentId}].profileurl:updated`, handler: this.render},
            {watch: `comments[${commentId}].usermodifiedfullname:updated`, handler: this.render},
            {watch: `comments[${commentId}].timemodifiedtext:updated`, handler: this.render},
            {watch: `comments[${commentId}].isediting:updated`, handler: this.render},
        ];
    }

    getComment() {
        return this.getState().comments.get(this.commentId);
    }

    async getContext() {
        const comment = this.getComment();
        return Object.assign({
            wasmodified: comment.timecreated !== comment.timemodified
        }, comment);
    }

    async showDeleteModal() {
        let deleteCommentString, confirmDeleteCommentString, deleteString;
        [deleteCommentString, confirmDeleteCommentString, deleteString] = await Str.get_strings([
            {key: 'deletecomment', component: 'core_comment'},
            {key: 'confirmdeletecomment', component: 'core_comment'},
            {key: 'delete', component: 'core_comment'},
        ]);

        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: deleteCommentString,
            body: confirmDeleteCommentString,
        });
        modal.setSaveButtonText(deleteString);
        modal.getRoot().on(ModalEvents.save, () => {
            this.reactive.dispatch('deleteComment', this.commentId).catch(Notification.exception);
        });
        modal.show();
    }

    async addListeners() {
        this.addListener(this.selectors.DELETE_COMMENT, 'click', (e) => {
            this.showDeleteModal().catch(Notification.exception);
            e.preventDefault();
            return false;
        });
        this.addListener(this.selectors.EDIT_COMMENT, 'click', (e) => {
            this.reactive.dispatch('setEditing', this.commentId, true).catch(Notification.exception);
            e.preventDefault();
            return false;
        });
        this.addListener(this.selectors.COPY_COMMENT_URL, 'click', (e) => {
            navigator.clipboard.writeText(this.getComment().commenturl);
            e.preventDefault();
            return false;
        });
    }
}
