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
import Comment from 'core_comment/comment';
import CommentBody from 'core_comment/comment_body';
import * as templates from 'core/templates';
import Notification from 'core/notification';

export default class CommentSection extends Component {

    constructor(el, options) {
        super('commentsection', el, null);
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
            this.loadContext().catch(Notification.exception);
        }
    }

    applyRenderOptions(renderOptions) {
        this.renderOptions = Object.assign({
            commentsectiontemplate: 'core_comment/comment_section',
            commentlisttemplate: 'core_comment/comment_list',
            commentformtemplate: 'core_comment/comment_form',
            commenttemplate: 'core_comment/comment',
            commentbodytemplate: 'core_comment/comment_body',
            commentsectionclass: CommentSection,
            commentlistclass: CommentList,
            commentformclass: CommentForm,
            commentclass: Comment,
            commentbodyclass: CommentBody,
        }, Object.fromEntries(renderOptions));

        // Prefetch all templates (i.e. the values of all render options with keys ending in "template").
        templates.prefetchTemplates(
            Object.entries(this.renderOptions)
                // eslint-disable-next-line no-unused-vars
                .filter(([key, value]) => key.endsWith('template'))
                // eslint-disable-next-line no-unused-vars
                .map(([key, value]) => value)
        );
    }

    async getContext() {
        return this.context;
    }

    async postRender() {
        this.commentForm = this.addChild('[data-commentform]', 'commentform', [this]);
        this.commentList = this.addChild('[data-commentlist]', 'commentlist', [this, null, this.pageSize, 'DESC']);
        if (this.comments !== null) {
            this.commentList.comments = this.comments.slice(0, this.pageSize);
            this.commentList.moreAvailableAfter = this.comments.length > this.pageSize;
        }
    }

    async loadContext() {
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
        this.comments = response.comments;
        await this.render();
    }

    async getComments(pageSize, sortDirection, replyTo = null, timeFrom = null, timeTo = null) {
        const response = await Ajax.call([
            {
                methodname: 'core_comment_get_comments', args: {
                    contextid: this.contextId,
                    component: this.component,
                    commentarea: this.commentArea,
                    itemid: this.itemId,
                    replytoid: replyTo ? replyTo.comment.id : undefined,
                    timefrom: timeFrom,
                    timeto: timeTo,
                    pagesize: pageSize,
                    sortdirection: sortDirection
                }
            },
        ])[0];
        return response.comments;
    }

    async saveComment(content, pseudonym = null, customData = null, replyTo = null, comment = null) {
        return await Ajax.call([
            {methodname: comment ? 'core_comment_update_comment' : 'core_comment_create_comment', args: {
                    comment: {
                        contextid: this.contextId,
                        component: this.component,
                        commentarea: this.commentArea,
                        itemid: this.itemId,
                        id: comment ? comment.comment.id : undefined,
                        replytoid: replyTo ? replyTo.comment.id : undefined,
                        content: content,
                        pseudonym: pseudonym,
                        customdata: customData || '',
                    }
                }},
        ])[0];
    }

    async deleteComment(id) {
        await Ajax.call([
            {methodname: 'core_comment_delete_comments', args: {comments: [id]}},
        ])[0];
    }
}