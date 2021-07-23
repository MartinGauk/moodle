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
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';
import Notification from 'core/notification';

export default class Comment extends Component {

    constructor(el, commentList, comment) {
        super('comment', el, commentList);
        this.commentList = commentList;
        this.commentSection = commentList.commentSection;
        this.replyTo = commentList.replyTo;
        this.renderOptions = commentList.renderOptions;
        this.comment = comment;
        this.showReplies = false;
        this.showReplyForm = false;
        this.isEditing = false;
        window.setTimeout(() => this.render());
    }

    async getContext() {
        return Object.assign({
            isediting: this.isEditing,
            showreplies: this.showReplies,
            showreplyform: this.showReplyForm
        }, this.comment);
    }

    async delete() {
        await this.commentSection.deleteComment(this.comment.id);
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
        await Promise.all([this.render(), this.commentBody.render()]);
    }

    async onReplyPosted(reply) {
        this.comment.replies++;
        this.showReplies = true;
        await this.render();
        await this.commentReplies.onCommentPosted(reply);
    }

    async postRender() {
        // Render editing form or comment body.
        if (this.isEditing) {
            this.commentEditForm = this.addChild(
                `[data-commenteditform="${this.comment.id}"]`, 'commentform', [this.commentSection, null, this]
            );
        } else {
            this.commentBody = this.addChild(`[data-commentbody="${this.comment.id}"]`, 'commentbody', [this]);
        }
        // Render reply form.
        if (this.showReplyForm) {
            this.commentReplyForm = this.addChild(
                `[data-commentreplyform="${this.comment.id}"]`, 'commentform', [this.commentSection, this]
            );
        }
        // Render replies.
        if (this.showReplies) {
            this.commentReplies = this.addChild(
                `[data-commentreplies="${this.comment.id}"]`, 'commentlist', [this.commentSection, this, 5, 'ASC', false]
            );
        }

        this.addListener(`[data-deletecomment="${this.comment.id}"]`, 'click', (e) => {
            this.delete().catch(Notification.exception);
            e.preventDefault();
            return false;
        });
        this.addListener(`[data-editcomment="${this.comment.id}"]`, 'click', (e) => {
            this.startEditing();
            e.preventDefault();
            return false;
        });
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
            if (!this.showReplyForm) {
                this.toggleReplyForm();
            }
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
