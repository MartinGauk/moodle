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
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';
import Notification from 'core/notification';
import * as ModalFactory from 'core/modal_factory';
import * as ModalEvents from 'core/modal_events';
import * as Str from 'core/str';

export default class CommentForm extends Component {

    constructor(el, parent, options = {}) {
        super('commentform', el, parent);
        this.commentSection = options.commentSection;
        this.comment = options.comment;
        this.onCancel = options.onCancel;
        this.onSubmit = options.onSubmit;
        if (this.comment) {
            this.replyTo = this.comment.commentList.replyTo;
        } else {
            this.replyTo = options.replyTo;
        }
    }

    async getContext() {
        return {
            canpost: this.commentSection.context.canpost,
            cancancel: true,
            allowpseudonym: this.commentSection.context.allowpseudonym,
            allowrealname: this.commentSection.context.allowrealname,
            comment: this.comment ? await this.comment.comment : null,
            replyto: this.replyTo ? await this.replyTo.comment : null
        };
    }

    getData() {
        return {
            content: this.form.content.value,
            pseudonymous: this.form.pseudonymous ? this.form.pseudonymous.checked : false
        };
    }

    async submit() {
        const data = this.getData();
        const savedComment = await this.commentSection.saveComment(
            data.content,
            data.pseudonymous,
            null,
            this.replyTo,
            this.comment
        );
        if (this.onSubmit) {
            this.onSubmit(savedComment);
        }
        if (this.comment) {
            await this.comment.onUpdated(savedComment);
        } else if (this.replyTo) {
            await this.replyTo.onReplyPosted(savedComment);
        } else {
            await this.commentSection.commentList.onCommentPosted(savedComment);
        }
        await this.render();
    }

    focus() {
        const input = this.form.querySelector('[name="content"]');
        if (input) {
            input.focus();
        } else {
            this.el.focus();
        }
    }

    isDirty() {
        const data = this.getData();
        return Object.keys(this.originalData).some(key => this.originalData[key] !== data[key]);
    }

    async cancel() {
        this.form.reset();
        this.el.firstChild.classList.add('empty');
        if (this.comment) {
            await this.comment.cancelEditing();
        } else if (this.replyTo && this.replyTo.showReplyForm) {
            await this.replyTo.toggleReplyForm();
        }
        if (this.onCancel) {
            this.onCancel();
        }
    }

    async showCancelModal() {
        let discardChangesString, confirmDiscardChangesString;
        [discardChangesString, confirmDiscardChangesString] = await Str.get_strings([
            {key: 'discardchanges', component: 'core_comment'},
            {key: 'confirmdiscardchanges', component: 'core_comment'}
        ]);

        const modal = await ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: discardChangesString,
            body: confirmDiscardChangesString,
        });
        modal.setSaveButtonText(discardChangesString);
        modal.getRoot().on(ModalEvents.save, () => {
            this.cancel().catch(Notification.exception);
        });
        modal.show();
    }

    async postRender() {
        this.form = this.el.querySelector('form');
        this.originalData = this.getData();
        if (this.originalData.content === '') {
            this.el.firstChild.classList.add('empty');
        }

        this.form.onsubmit = () => {
            this.submit().catch(Notification.exception);
            return false;
        };
        this.addListener('form [name="content"]', 'keydown', (e) => {
            if (e.which === 13 && e.ctrlKey) {
                e.preventDefault();
                this.submit().catch(Notification.exception);
            }
        });
        this.addListener('form [name="content"]', 'input', (e) => {
            if (e.target.value === '') {
                this.el.firstChild.classList.add('empty');
            } else {
                this.el.firstChild.classList.remove('empty');
            }
        });
        this.addListener('[data-cancelcommentform]', 'click', (e) => {
            if (this.isDirty()) {
                this.showCancelModal();
            } else {
                this.cancel();
            }
            e.preventDefault();
            return false;
        });

        const unsavedChangesString = await Str.get_string('unsavedchanges', 'core_comment');
        window.addEventListener("beforeunload", (e) => {
            if (!this.isDirty()) {
                return undefined;
            }
            (e || window.event).returnValue = unsavedChangesString; // Gecko + IE.
            return unsavedChangesString; // Gecko + Webkit, Safari, Chrome etc.
        });

    }
}
