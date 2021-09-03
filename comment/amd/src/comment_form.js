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
 * Comment form module.
 *
 * @module     core_comment/comments
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';
import Notification from 'core/notification';

export default class CommentForm extends Component {

    constructor(el, parent, options = {}) {
        super('commentform', el, parent);
        this.commentSection = options.commentSection;
        this.comment = options.comment;
        if (this.comment) {
            this.replyTo = this.comment.commentList.replyTo;
        } else {
            this.replyTo = options.replyTo;
        }
    }

    async getContext() {
        return {
            canpost: this.commentSection.context.canpost,
            allowpseudonym: this.commentSection.context.allowpseudonym,
            allowrealname: this.commentSection.context.allowrealname,
            comment: this.comment ? await this.comment.comment : null,
            replyto: this.replyTo ? await this.replyTo.comment : null
        };
    }

    async submitForm(form) {
        const savedComment = await this.commentSection.saveComment(
            form.content.value,
            form.pseudonym ? form.pseudonym.value : null,
            null,
            this.replyTo,
            this.comment
        );
        if (this.comment) {
            await this.comment.onUpdated(savedComment);
        } else if (this.replyTo) {
            await this.replyTo.onReplyPosted(savedComment);
        } else {
            await this.commentSection.commentList.onCommentPosted(savedComment);
        }
        form.reset();
    }

    focus() {
        const input = this.el.querySelector('form [name="content"]');
        if (input) {
            input.focus();
        } else {
            this.el.focus();
        }
    }

    async postRender() {
        const form = this.el.querySelector('form');
        if (form) {
            form.onsubmit = () => {
                this.submitForm(form).catch(Notification.exception);
                return false;
            };
        }
        this.addListener('form textarea[name="content"]', 'keydown', (e) => {
            if (e.which === 13 && e.ctrlKey) {
                e.preventDefault();
                this.submitForm(form).catch(Notification.exception);
            }
        });
        this.addListener('[data-cancelcommentform]', 'click', (e) => {
            const form = this.el.querySelector('form');
            form.reset();
            if (this.comment) {
                this.comment.cancelEditing();
            } else if (this.replyTo && this.replyTo.showReplyForm) {
                this.replyTo.toggleReplyForm();
            }
            e.preventDefault();
            return false;
        });
    }
}
