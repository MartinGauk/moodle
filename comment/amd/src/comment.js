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
 * Comment module.
 *
 * @module     core_comment/comments
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';
import {eventTypes} from 'core_comment/events';
import Notification from 'core/notification';

export default class Comment extends Component {

    constructor(descriptor) {
        super(descriptor);
        this.commentId = Number(this.element.dataset.commentid);
        if (!Number.isInteger(this.commentId)) {
            throw new Error('commentId missing in dataset');
        }
    }

    create() {
        this.selectors = {
            COMMENT_ITEM_LINK: `[data-for="commentitemlink"]`,
            COMMENT_HEADER: `[data-for="commentheader"]`,
            COMMENT_EDIT_FORM: `[data-for="commenteditform"]`,
            COMMENT_BODY: `[data-for="commentbody"]`,
            COMMENT_REPLY_FORM: `[data-for="commentreplyform"]`,
            COMMENT_REPLIES: `[data-for="commentreplies"]`,
            SHOW_REPLIES: `[data-for="showreplies"]`,
            HIDE_REPLIES: `[data-for="hidereplies"]`,
            SHOW_REPLY_FORM: `[data-for="showreplyform"]`,
            HIDE_REPLY_FORM: `[data-for="hidereplyform"]`,
        };
    }

    getWatchers() {
        const commentId = this.element.dataset.commentid;
        return [
            {watch: `comments[${commentId}].isediting:updated`, handler: this._onIsEditingUpdated},
            {watch: `comments[${commentId}].showreplies:updated`, handler: this.render},
            {watch: `comments[${commentId}].showreplyform:updated`, handler: this._onShowReplyFormUpdated},
            {watch: `comments[${commentId}].replies:updated`, handler: this.render},
        ];
    }

    getComment() {
        return this.getState().comments.get(this.commentId);
    }

    getHighlightedReply() {
        if (this.getState().highlight.commentId !== this.commentId && this.getState().highlight.replyId === null) {
            return null;
        }
        return this.getState().comments.get(this.getState().highlight.replyId);
    }

    async getContext() {
        const comment = this.getComment();
        return Object.assign({
            showitemlink: this.getState().section == undefined && comment.replytoid == undefined,
            section: this.getState().sections.get(comment.itemid),
        }, comment);
    }

    async _onIsEditingUpdated() {
        await this.render();
        if (this.getComment().isediting) {
            await this.commentEditForm.renderPromise;
            this.commentEditForm.focus();
        }
    }

    async _onShowReplyFormUpdated() {
        await this.render();
        if (this.getComment().showreplyform) {
            await this.commentReplyForm.renderPromise;
            this.commentReplyForm.focus();
        }
    }

    async addListeners() {
        this.addListener(this.selectors.SHOW_REPLIES, 'click', (e) => {
            this.reactive.dispatch('setShowReplies', this.commentId, true).catch(Notification.exception);
            e.preventDefault();
            return false;
        });
        this.addListener(this.selectors.HIDE_REPLIES, 'click', (e) => {
            this.reactive.dispatch('setShowReplies', this.commentId, false).catch(Notification.exception);
            e.preventDefault();
            return false;
        });
        this.addListener(this.selectors.SHOW_REPLY_FORM, 'click', (e) => {
            if (this.getComment().showreplyform) {
                this.commentReplyForm.focus();
            } else {
                this.reactive.dispatch('setShowReplyForm', this.commentId, true).catch(Notification.exception);
            }
            e.preventDefault();
            return false;
        });
        this.addListener(this.selectors.HIDE_REPLY_FORM, 'click', (e) => {
            this.reactive.dispatch('setShowReplyForm', this.commentId, false).catch(Notification.exception);
            e.preventDefault();
            return false;
        });
        this.addListener(this.selectors.COMMENT_EDIT_FORM, eventTypes.formCanceled, () => {
            this.reactive.dispatch('setEditing', this.commentId, false).catch(Notification.exception);
        });
        this.addListener(this.selectors.COMMENT_REPLY_FORM, eventTypes.formCanceled, () => {
            this.reactive.dispatch('setShowReplyForm', this.commentId, false).catch(Notification.exception);
        });
    }

    async addChildren() {
        await this.addChild(this.selectors.COMMENT_ITEM_LINK, 'commentitemlink');

        // Render comment header.
        await this.addChild(this.selectors.COMMENT_HEADER, 'commentheader');

        // Render editing form or comment body.
        if (this.getComment().isediting) {
            this.commentEditForm = await this.addChild(this.selectors.COMMENT_EDIT_FORM, 'commentform');
        } else {
            if (this.commentEditForm) {
                // TODO this.removeChild(this.commentEditFormEl);
            }
            await this.addChild(this.selectors.COMMENT_BODY, 'commentbody');
        }

        // Render reply form.
        if (this.getComment().showreplyform) {
            this.commentReplyForm = await this.addChild(this.selectors.COMMENT_REPLY_FORM, 'commentform');
        }

        // Render replies.
        if (this.getComment().showreplies) {
            await this.addChild(this.selectors.COMMENT_REPLIES, 'commentreplies');
        }
    }
}
