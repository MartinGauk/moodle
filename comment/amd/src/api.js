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
 * Comment API module.
 *
 * @module     core_comment/api
 * @copyright  2022 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

export const getComments = async(
    contextId, component, commentArea, itemId,
    pageSize, sortDirection, replyToId = null, timeFrom = null, timeTo = null
) => {
    return await Ajax.call([
        {
            methodname: 'core_comment_get_comments', args: {
                contextid: contextId,
                component: component,
                commentarea: commentArea,
                itemid: itemId,
                replytoid: replyToId,
                timefrom: timeFrom,
                timeto: timeTo,
                pagesize: pageSize,
                sortdirection: sortDirection
            }
        },
    ])[0];
};

export const getComment = async(commentId, includeParent = false) => {
    return await Ajax.call([
        {
            methodname: 'core_comment_get_comments', args: {
                commentid: commentId,
                includeparents: includeParent
            }
        },
    ])[0];
};

export const saveComment = async(comment) => {
    return await Ajax.call([
        {methodname: comment.id ? 'core_comment_update_comment' : 'core_comment_create_comment', args: {
                comment: {
                    contextid: comment.contextid,
                    component: comment.component,
                    commentarea: comment.commentarea,
                    itemid: comment.itemid,
                    id: comment.id != undefined ? comment.id : undefined,
                    replytoid: comment.replytoid != undefined ? comment.replytoid : undefined,
                    content: comment.content,
                    pseudonymous: comment.pseudonymous,
                    customdata: comment.customdata || '',
                }
            }},
    ])[0];
};

export const deleteComments = async(commentIds) => {
    return await Ajax.call([
        {methodname: 'core_comment_delete_comments', args: {comments: commentIds}},
    ])[0];
};