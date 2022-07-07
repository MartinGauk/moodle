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
        this.commentList = options.commentList;
        this.commentSection = this.commentList.commentSection;
        this.replyTo = this.commentList.replyTo;
        this.comment = options.comment;
        this.highlightedReply = this.isHighlighted() ? this.commentList.highlightedReply : null;
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
            showsettings: (this.comment.canedit && !this.isEditing) || this.comment.candelete,
            showitemlink: !this.commentSection.itemId && !this.replyTo,
            wasmodified: this.comment.timecreated !== this.comment.timemodified,
        }, this.comment);
    }

    isHighlighted() {
        return this.commentList.highlightedComment && this.comment.id === this.commentList.highlightedComment.id;
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
            await this.commentReplies.loadMore().catch(Notification.exception);
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
        this.commentReplyForm.focus();
    }

    async onDeleted() {
        if (this.replyTo) {
            this.replyTo.comment.replies--;
            this.replyTo.render();
        }
        await this.commentList.onCommentDeleted(this.comment.id);
    }

    async onUpdated(updatedComment) {
        this.comment = updatedComment;
        this.isEditing = false;
        this.removeChild(this.commentEditForm);
        await Promise.all([this.render(), this.commentHeader.render(), this.commentBody.render()]);
    }

    async onReplyPosted(reply) {
        this.comment.replies++;
        this.showReplies = true;
        this.showReplyForm = false;
        await this.render();
        await this.commentReplies.onCommentPosted(reply);
    }

    async postRender() {
        await this.addChild(`[data-commentitemlink="${this.comment.id}"]`, 'commentitemlink', {comment: this});

        // Render comment header.
        this.commentHeader = await this.addChild(`[data-commentheader="${this.comment.id}"]`, 'commentheader', {comment: this});

        // Render editing form or comment body.
        if (this.isEditing) {
            this.commentEditForm = await this.addChild(`[data-commenteditform="${this.comment.id}"]`, 'commentform', {
                commentSection: this.commentSection,
                comment: this
            });
        } else {
            this.commentBody = await this.addChild(`[data-commentbody="${this.comment.id}"]`, 'commentbody', {comment: this});
        }

        // Render reply form.
        if (this.showReplyForm) {
            this.commentReplyForm = await this.addChild(`[data-commentreplyform="${this.comment.id}"]`, 'commentform', {
                commentSection: this.commentSection,
                replyTo: this
            });
        }

        // Render replies.
        if (this.showReplies) {
            this.commentReplies = await this.addChild(`[data-commentreplies="${this.comment.id}"]`, 'commentlist', {
                commentSection: this.commentSection,
                replyTo: this,
                pageSize: 5,
                sortDirection: 'ASC'
            });
        }
        if (this.highlightedReply) {
            this.commentReplies = await this.addChild(`[data-highlightedreply="${this.highlightedReply.id}"]`, 'commentlist', {
                commentSection: this.commentSection,
                replyTo: this,
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
