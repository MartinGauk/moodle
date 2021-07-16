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

export default class CommentForm extends Component {

    constructor(el, commentSection, comment = null, replyTo = null) {
        super(el);
        this.commentSection = commentSection;
        this.renderOptions = commentSection.renderOptions;
        this.comment = comment;
        this.replyTo = replyTo;
        window.setTimeout(() => this.render());
    }

    async getTemplate() {
        return this.renderOptions.commentformtemplate;
    }

    async getContext() {
        return {
            comment: this.comment ? await this.comment.getContext() : null,
            replyto: this.replyTo ? await this.replyTo.getContext() : null
        };
    }

    async submitForm(form) {
        if (!this.comment) {
            const newComment = await this.commentSection.createComment(form.content.value, null, null, this.replyTo);
            if (!this.replyTo) {
                await this.commentSection.commentList.insertComment(newComment);
            } else {
                if (!this.replyTo.showReplies) {
                    await this.replyTo.toggleReplies();
                }
                await this.replyTo.commentReplies.insertComment(newComment);
            }
            form.reset();
        } else {
            // TODO update
        }
    }

    async postRender() {
        const form = this.el.querySelector('form');
        form.onsubmit = () => {
            this.submitForm(form);
            return false;
        };
    }
}
