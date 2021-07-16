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
 * Comment footer module.
 *
 * @module     core_comment/comments
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';

export default class CommentHeader extends Component {

    constructor(el, comment) {
        super(el);
        this.comment = comment;
        this.renderOptions = comment.renderOptions;
        window.setTimeout(() => this.render());
    }

    async getTemplate() {
        return this.renderOptions.commentfootertemplate;
    }

    async getContext() {
        return Object.assign({
            showreplies: this.comment.showReplies,
            showreplyform: this.comment.showReplyForm
        }, await this.comment.getContext());
    }

    async postRender() {
        this.addListener('[data-showreplies]', 'click', (e) => {
            if (!this.comment.showReplies) {
                this.comment.toggleReplies();
            }
            e.preventDefault();
            return false;
        });
        this.addListener('[data-hidereplies]', 'click', (e) => {
            if (this.comment.showReplies) {
                this.comment.toggleReplies();
            }
            e.preventDefault();
            return false;
        });
        this.addListener('[data-showreplyform]', 'click', (e) => {
            if (!this.comment.showReplyForm) {
                this.comment.toggleReplyForm();
            }
            e.preventDefault();
            return false;
        });
        this.addListener('[data-hidereplyform]', 'click', (e) => {
            if (this.comment.showReplyForm) {
                this.comment.toggleReplyForm();
            }
            e.preventDefault();
            return false;
        });
    }
}
