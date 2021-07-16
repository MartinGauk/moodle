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
 * Comment section module.
 *
 * @module     core_comment/comments
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Component from 'core_comment/component';
import CommentForm from 'core_comment/comment_form';
import CommentList from 'core_comment/comment_list';
import * as templates from 'core/templates';

export default class CommentSection extends Component {

    constructor(el, options) {
        super(el);
        this.contextId = options.contextid;
        this.component = options.component;
        this.commentArea = options.commentarea;
        this.itemId = options.itemid;
        this.pageSize = options.pageSize || 10;
        this.context = options.commentSection || null;
        this.comments = null;
        if (this.context) {
            this.applyRenderOptions(this.context.renderoptions);
            window.setTimeout(() => this.render());
        } else {
            this.renderOptions = null;
            this.fetchContext().then(() => this.render());
        }
    }

    applyRenderOptions(renderOptions) {
        this.renderOptions = Object.assign({
            commentsectiontemplate: 'core_comment/comment_section',
            commentlisttemplate: 'core_comment/comment_list',
            commentformtemplate: 'core_comment/comment_form',
            commenttemplate: 'core_comment/comment',
            commentheadertemplate: 'core_comment/comment_header',
            commentbodytemplate: 'core_comment/comment_body',
            commentfootertemplate: 'core_comment/comment_footer'
        }, Object.fromEntries(renderOptions));

        templates.prefetchTemplates(
            Object.entries(this.renderOptions)
                // eslint-disable-next-line no-unused-vars
                .filter(([key, value]) => key.endsWith('template'))
                // eslint-disable-next-line no-unused-vars
                .map(([key, value]) => value)
        );
    }

    async getTemplate() {
        return this.renderOptions.commentsectiontemplate;
    }

    async getContext() {
        return this.context;
    }

    async postRender() {
        this.commentForm = this.addChild('[data-commentform]', (el) => new CommentForm(el, this));
        this.commentList = this.addChild('[data-commentlist]', (el) => new CommentList(el, this, null, this.pageSize, 'DESC'));
        if (this.comments !== null) {
            this.commentList.comments = this.comments.slice(0, this.pageSize);
            this.commentList.moreAvailableAfter = this.comments.length > this.pageSize;
        }
    }

    async fetchContext() {
        const response = await Ajax.call([
            {
                methodname: 'core_comment_get_comments', args: {
                    contextid: this.contextId,
                    component: this.component,
                    commentarea: this.commentArea,
                    itemid: this.itemId,
                    pagesize: this.pageSize + 1,
                }
            },
        ])[0];
        this.context = response.commentsections[0];
        this.applyRenderOptions(this.context.renderoptions);
        // TODO prefetch templates
        this.comments = response.comments;
    }

    async createComment(content, pseudonym = null, customData = null, replyTo = null) {
        const newComment = await Ajax.call([
            {methodname: 'core_comment_create_comment', args: {
                    comment: {
                        contextid: this.contextId,
                        component: this.component,
                        commentarea: this.commentArea,
                        itemid: this.itemId,
                        replytoid: replyTo ? replyTo.comment.id : undefined,
                        content: content,
                        pseudonym: pseudonym,
                        customdata: customData || '',
                    }
                }},
        ])[0];
        replyTo.comment.replies++;
        replyTo.commentFooter.render();
        return newComment;
    }
}