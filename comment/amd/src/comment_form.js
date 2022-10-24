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
import {eventTypes} from 'core_comment/events';
import Notification from 'core/notification';
import * as ModalFactory from 'core/modal_factory';
import * as ModalEvents from 'core/modal_events';
import * as Str from 'core/str';

export default class CommentForm extends Component {
    static getEvents() {
        return {
            formCanceled: eventTypes.formCanceled,
            formSubmitted: eventTypes.formSubmitted,
        };
    }

    constructor(descriptor) {
        super(descriptor);
        this.commentId = Number(this.element.dataset.commentid);
        this.replyToId = null;
        if (!Number.isInteger(this.commentId)) {
            this.commentId = null;
            this.replyToId = Number(this.element.dataset.replytoid);
            if (!Number.isInteger(this.replyToId)) {
                this.replyToId = null;
            }
        }
    }

    create() {
        this.selectors = {
            CANCEL_COMMENT_FORM: `[data-for="cancelcommentform"]`,
            FORM: `form`,
            CONTENT_INPUT: `form [name="content"]`,
        };
    }

    getComment() {
        return this.commentId !== null ? this.getState().comments.get(this.commentId) : null;
    }

    getReplyTo() {
        let replyToId = this.replyToId;
        const comment = this.getComment();
        if (comment) {
            replyToId = comment.replytoid;
        }
        return replyToId !== null ? this.getState().comments.get(replyToId) : null;
    }

    getSection() {
        if (this.getState().section) {
            return this.getState().section;
        }
        const itemId = this.commentId !== null ? this.getComment().itemid : this.getReplyTo().itemid;
        return this.getState().sections.get(itemId);
    }

    async getContext() {
        const section = this.getSection();
        return {
            canpost: section.canpost,
            cancancel: true,
            allowpseudonym: section.allowpseudonym,
            allowrealname: section.allowrealname,
            comment: this.getComment(),
            replyto: this.getReplyTo(),
        };
    }

    getData() {
        const form = this.getElement(this.selectors.FORM);
        return {
            content: form.content.value,
            pseudonymous: form.pseudonymous ? form.pseudonymous.checked : false,
            customdata: null
        };
    }

    async submit() {
        const data = this.getData();
        const section = this.getSection();
        let comment = {
            contextid: section.contextid,
            component: section.component,
            commentarea: section.commentarea,
            itemid: section.itemid,
            id: this.commentId,
            replytoid: this.commentId !== null ? this.getComment().replytoid : this.replyToId,
            content: data.content,
            pseudonymous: data.pseudonymous,
            customdata: data.customdata
        };

        const form = this.getElement(this.selectors.FORM);
        comment = this.callback('presave', [comment, form], comment);
        if (!comment) {
            return;
        }
        comment = this.callback(this.commentEl ? 'preupdate' : 'precreate', [comment, form], comment);
        if (!comment) {
            return;
        }

        await this.reactive.dispatch('saveComment', comment);

        this.callback('postsave', [comment]);
        this.callback(this.commentEl ? 'postupdate' : 'postcreate', [comment]);

        this.dispatchEvent(this.events.formSubmitted, {commentId: this.commentId, replyToId: this.replyToId});

        await this.render();
    }

    isDirty() {
        const data = this.getData();
        return Object.keys(this.originalData).some(key => this.originalData[key] !== data[key]);
    }

    async cancel() {
        this.getElement(this.selectors.FORM).reset();
        this.element.firstChild.classList.add('empty');
        this.dispatchEvent(this.events.formCanceled, {commentId: this.commentId, replyToId: this.replyToId});
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

    async addListeners() {
        this.addListener(this.selectors.FORM, 'submit', (e) => {
            this.submit().catch(Notification.exception);
            e.preventDefault();
            return false;
        });
        this.addListener(this.selectors.CONTENT_INPUT, 'keydown', (e) => {
            if (e.which === 13 && e.ctrlKey) {
                e.preventDefault();
                this.submit().catch(Notification.exception);
            }
        });
        this.addListener(this.selectors.CONTENT_INPUT, 'input', (e) => {
            if (e.target.value === '') {
                this.element.firstChild.classList.add('empty');
            } else {
                this.element.firstChild.classList.remove('empty');
            }
        });
        this.addListener(this.selectors.CANCEL_COMMENT_FORM, 'click', (e) => {
            if (this.isDirty()) {
                this.showCancelModal();
            } else {
                this.cancel();
            }
            e.preventDefault();
            return false;
        });
    }

    async postRender() {
        this.originalData = this.getData();
        if (this.originalData.content === '') {
            this.element.firstChild.classList.add('empty');
        }

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
