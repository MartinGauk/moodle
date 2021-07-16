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
import CommentHeader from 'core_comment/comment_header';
import CommentBody from 'core_comment/comment_body';
import CommentFooter from 'core_comment/comment_footer';
import CommentForm from 'core_comment/comment_form';
import Ajax from 'core/ajax';

export default class Comment extends Component {

    constructor(el, commentList, comment) {
        super(el);
        this.commentList = commentList;
        this.renderOptions = commentList.renderOptions;
        this.comment = comment;
        this.showReplies = false;
        this.showReplyForm = false;
        window.setTimeout(() => this.render());
    }

    async getTemplate() {
        return this.renderOptions.commenttemplate;
    }

    async getContext() {
        return this.comment;
    }

    async delete() {
        await Ajax.call([
            {methodname: 'core_comment_delete_comments', args: {comments: [this.comment.id]}},
        ])[0];
        if (this.commentList.replyTo) {
            this.commentList.replyTo.comment.replies--;
            this.commentList.replyTo.commentFooter.render();
        }
        await this.commentList.removeComment(this.comment.id);
    }

    async toggleReplies() {
        if (this.showReplies) {
            this.showReplies = false;
            this.commentReplies.el.style.display = 'none';
            await this.commentFooter.render();
        } else {
            if (!this.commentReplies) {
                const CommentList = require('core_comment/comment_list');
                this.commentReplies = this.addChild(
                    `[data-commentreplies="${this.comment.id}"]`,
                    (el) => new CommentList(el, this.commentList.commentSection, this, 5, 'ASC', true)
                );
            }
            this.showReplies = true;
            this.commentReplies.el.style.display = 'block';
            await this.commentFooter.render();
        }
    }

    async toggleReplyForm() {
        if (this.showReplyForm) {
            this.showReplyForm = false;
            this.commentReplyForm.el.style.display = 'none';
            await this.commentFooter.render();
        } else {
            if (!this.commentReplyForm) {
                this.commentReplyForm = this.addChild(
                    `[data-commentreplyform="${this.comment.id}"]`,
                    (el) => new CommentForm(el, this.commentList.commentSection, null, this)
                );
            }
            this.showReplyForm = true;
            this.commentReplyForm.el.style.display = 'block';
            await this.commentFooter.render();
        }
    }

    async postRender() {
        this.commentHeader = this.addChild(`[data-commentheader="${this.comment.id}"]`, (el) => new CommentHeader(el, this));
        this.commentBody = this.addChild(`[data-commentbody="${this.comment.id}"]`, (el) => new CommentBody(el, this));
        this.commentFooter = this.addChild(`[data-commentfooter="${this.comment.id}"]`, (el) => new CommentFooter(el, this));
    }
}
