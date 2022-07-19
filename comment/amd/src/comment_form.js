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
import * as Comments from 'core_comment/comments';
import Notification from 'core/notification';
import * as ModalFactory from 'core/modal_factory';
import * as ModalEvents from 'core/modal_events';
import * as Str from 'core/str';

export default class CommentForm extends Component {

    constructor(el, parent, options = {}) {
        super('commentform', el, parent);
        this.commentSectionEl = options.commentSectionEl;
        this.commentEl = options.commentEl;
        this.onCancel = options.onCancel;
        this.onSubmit = options.onSubmit;
        if (this.commentEl) {
            this.replyToEl = this.commentEl.commentListEl.replyToEl;
        } else {
            this.replyToEl = options.replyToEl;
        }
        if (this.commentEl) {
            this.section = this.commentEl.section;
        } else if (this.replyToEl) {
            this.section = this.replyToEl.section;
        } else {
            this.section = this.commentSectionEl.section;
        }
    }

    async getContext() {
        return {
            canpost: this.section.canpost,
            cancancel: true,
            allowpseudonym: this.section.allowpseudonym,
            allowrealname: this.section.allowrealname,
            comment: this.commentEl ? await this.commentEl.comment : null,
            replyto: this.replyToEl ? await this.replyToEl.comment : null
        };
    }

    getData() {
        return {
            content: this.form.content.value,
            pseudonymous: this.form.pseudonymous ? this.form.pseudonymous.checked : false,
            customdata: null
        };
    }

    async submit() {
        const data = this.getData();
        let comment = {
            contextid: this.section.contextid,
            component: this.section.component,
            commentarea: this.section.commentarea,
            itemid: this.section.itemid,
            id: this.commentEl ? this.commentEl.comment.id : null,
            replytoid: this.replyToEl ? this.replyToEl.comment.id : null,
            content: data.content,
            pseudonymous: data.pseudonymous,
            customdata: data.customdata
        };

        comment = this.callback('presave', [comment, this.form], comment);
        if (!comment) {
            return;
        }
        comment = this.callback(this.commentEl ? 'preupdate' : 'precreate', [comment, this.form], comment);
        if (!comment) {
            return;
        }

        const savedComment = await Comments.saveComment(comment);

        this.callback('postsave', [savedComment]);
        this.callback(this.commentEl ? 'postupdate' : 'postcreate', [savedComment]);

        if (this.onSubmit) {
            this.onSubmit(savedComment);
        }
        if (this.commentEl) {
            await this.commentEl.onUpdated(savedComment);
        } else if (this.replyToEl) {
            await this.replyToEl.onReplyPosted(savedComment);
        } else {
            await this.commentSectionEl.commentListEl.onCommentPosted(savedComment);
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
        if (this.commentEl) {
            await this.commentEl.cancelEditing();
        } else if (this.replyToEl && this.replyToEl.showReplyForm) {
            await this.replyToEl.toggleReplyForm();
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
