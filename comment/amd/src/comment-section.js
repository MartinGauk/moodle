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
import * as templates from 'core/templates';

const createComment = async(content, options) => {
    await Ajax.call([
        {methodname: 'core_comment_create_comments', args: {
            comments: [
                {
                    contextid: options.contextid,
                    component: options.component,
                    commentarea: options.commentarea,
                    itemid: options.itemid,
                    content: content,
                }
            ],
        }},
    ])[0];
};

const deleteComment = async(id) => {
    await Ajax.call([
        {methodname: 'core_comment_delete_comments', args: {comments: [id]}},
    ])[0];
};

const onDeleteCommentClicked = async(id, el, options) => {
    await deleteComment(id);
    await loadComments(el, options);
};

const onCommentFormSubmit = async(content, el, options) => {
    await createComment(content, options);
    await loadComments(el, options);
};

const renderCommentForm = async(el, options) => {
    const context = {};
    const html = await templates.render('core_comment/comment_form', context);
    const commentFormContainer = el.querySelector('.js-comment-form-container');
    templates.replaceNodeContents(commentFormContainer, html, '');
    const commentForm = commentFormContainer.querySelector('form');
    commentForm.onsubmit = function() {
        onCommentFormSubmit(this.content.value, el, options);
        return false;
    };
};

const renderCommentList = async(comments, el, options) => {
    const context = {
        comments: comments
    };
    const html = await templates.render('core_comment/comment_list', context);
    const commentListContainer = el.querySelector('.js-comment-list-container');
    templates.replaceNodeContents(commentListContainer, html, '');
    commentListContainer.querySelectorAll(".js-delete-comment").forEach(button =>
        button.addEventListener('click', (e) => {
            onDeleteCommentClicked(button.dataset.id, el, options);
            e.preventDefault();
            return false;
        })
    );
};

const loadComments = async(el, options) => {
    const response = await Ajax.call([
        {methodname: 'core_comment_get_comments', args: {
            contextid: options.contextid,
            component: options.component,
            commentarea: options.commentarea,
            itemid: options.itemid
        }},
    ])[0];
    await renderCommentList(response.comments, el, options);
};

const renderCommentSection = async(el, options) => {
    const context = {};
    const html = await templates.render('core_comment/comment_section', context);
    templates.replaceNodeContents(el, html, '');
    await renderCommentForm(el, options);
};

export const init = async(el, options) => {
    await renderCommentSection(el, options);
    await loadComments(el, options);
};
