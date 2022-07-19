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
import * as Comments from 'core_comment/comments';
import Notification from 'core/notification';

export default class Comment extends Component {

    constructor(el, parent, options = {}) {
        super('comment', el, parent);
        this.commentListEl = options.commentListEl;
        this.commentSectionEl = this.commentListEl.commentSectionEl;
        this.replyToEl = this.commentListEl.replyToEl;
        this.comment = options.comment;
        this.section = this.commentSectionEl.sections.find(section => section.itemid === this.comment.itemid); // TODO test this
        this.highlightedReply = this.isHighlighted() ? this.commentListEl.highlightedReply : null;
        this.showReplies = false;
        this.showReplyForm = false;
        this.isEditing = false;
    }

    async getContext() {
        return Object.assign({
            isediting: this.isEditing,
            showreplies: this.showReplies,
            highlightedreply: this.highlightedReply,
            showreplyform: this.showReplyForm,
            showitemlink: !this.commentSectionEl.itemId && !this.replyToEl,
            wasmodified: this.comment.timecreated !== this.comment.timemodified,
            section: this.section,
        }, this.comment);
    }

    isHighlighted() {
        return this.commentListEl.highlightedComment && this.comment.id === this.commentListEl.highlightedComment.id;
    }

    async delete() {
        await Comments.deleteComments([this.comment.id]);
        await this.onDeleted();
    }

    async startEditing() {
        this.isEditing = true;
        await this.render();
    }

    async cancelEditing() {
        if (this.isEditing) {
            this.isEditing = false;
            await this.render();
        }
    }

    async toggleReplies() {
        this.highlightedReply = null;
        this.showReplies = !this.showReplies;
        await this.render();
        if (this.showReplies) {
            await this.commentRepliesEl.loadMore().catch(Notification.exception);
        }
    }

    async toggleReplyForm() {
        this.showReplyForm = !this.showReplyForm;
        await this.render();
    }

    async gotoReplyForm() {
        if (!this.showReplyForm) {
            await this.toggleReplyForm();
        }
        this.commentReplyFormEl.focus();
    }

    async onDeleted() {
        if (this.replyToEl) {
            this.replyToEl.comment.replies--;
            this.replyToEl.render();
        }
        await this.commentListEl.onCommentDeleted(this.comment.id);
    }

    async onUpdated(updatedComment) {
        this.comment = updatedComment;
        this.isEditing = false;
        this.removeChild(this.commentEditFormEl);
        await Promise.all([this.render(), this.commentHeaderEl.render(), this.commentBodyEl.render()]);
    }

    async onReplyPosted(reply) {
        this.comment.replies++;
        this.showReplies = true;
        this.showReplyForm = false;
        await this.render();
        await this.commentRepliesEl.onCommentPosted(reply);
    }

    async postRender() {
        await this.addChild(`[data-commentitemlink="${this.comment.id}"]`, 'commentitemlink', {
            commentEl: this
        });

        // Render comment header.
        this.commentHeaderEl = await this.addChild(`[data-commentheader="${this.comment.id}"]`, 'commentheader', {
            commentEl: this
        });

        // Render editing form or comment body.
        if (this.isEditing) {
            this.commentEditFormEl = await this.addChild(`[data-commenteditform="${this.comment.id}"]`, 'commentform', {
                commentSectionEl: this.commentSectionEl,
                commentEl: this
            });
        } else {
            this.commentBodyEl = await this.addChild(`[data-commentbody="${this.comment.id}"]`, 'commentbody', {
                commentEl: this
            });
        }

        // Render reply form.
        if (this.showReplyForm) {
            this.commentReplyFormEl = await this.addChild(`[data-commentreplyform="${this.comment.id}"]`, 'commentform', {
                commentSectionEl: this.commentSectionEl,
                replyToEl: this
            });
        }

        // Render replies.
        if (this.showReplies) {
            this.commentRepliesEl = await this.addChild(`[data-commentreplies="${this.comment.id}"]`, 'commentlist', {
                commentSectionEl: this.commentSectionEl,
                replyToEl: this,
                pageSize: 5,
                sortDirection: 'ASC'
            });
        }
        if (this.highlightedReply) {
            this.commentRepliesEl = await this.addChild(`[data-highlightedreply="${this.highlightedReply.id}"]`, 'commentlist', {
                commentSectionEl: this.commentSectionEl,
                replyToEl: this,
                moreAvailableAbove: false,
                moreAvailableBelow: false,
                highlightedComment: this.highlightedReply
            });
        }

        this.addListener(`[data-showreplies="${this.comment.id}"]`, 'click', (e) => {
            if (!this.showReplies) {
                this.toggleReplies();
            }
            e.preventDefault();
            return false;
        });
        this.addListener(`[data-hidereplies="${this.comment.id}"]`, 'click', (e) => {
            if (this.showReplies) {
                this.toggleReplies();
            }
            e.preventDefault();
            return false;
        });
        this.addListener(`[data-showreplyform="${this.comment.id}"]`, 'click', (e) => {
            this.gotoReplyForm();
            e.preventDefault();
            return false;
        });
        this.addListener(`[data-hidereplyform="${this.comment.id}"]`, 'click', (e) => {
            if (this.showReplyForm) {
                this.toggleReplyForm();
            }
            e.preventDefault();
            return false;
        });
    }
}
