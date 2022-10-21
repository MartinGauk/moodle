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
 * Comment mutations module.
 *
 * @module     core_comment/mutations
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as api from 'core_comment/api';
import {getComment} from "core_comment/api";

export default class {

    constructor(options) {
        this.options = options;
    }

    async setHighlightedComment(stateManager, commentId = null) {
        const state = stateManager.state;

        if (commentId === null) {
            stateManager.setReadOnly(false);
            state.highlight.commentId = null;
            state.highlight.replyId = null;

            stateManager.setReadOnly(true);
            return;
        }

        let highlightedComment = state.comments.get(commentId);
        let highlightedReply = null;
        if (!highlightedComment) {
            const response = await getComment(commentId, true);
            if (!response.comments.length) {
                return;
            }
            highlightedComment = response.comments[0];

            if (highlightedComment.contextid !== this.options.contextId ||
                highlightedComment.commentarea !== this.options.commentArea ||
                (this.options.itemId != undefined && this.options.itemId !== highlightedComment.itemid)) {
                // Highlighted comment from wrong comment section.
                return;
            }

            stateManager.setReadOnly(false);

            highlightedComment = this._initComment(stateManager, highlightedComment);
            if (response.parents.length) {
                highlightedReply = highlightedComment;
                highlightedComment = this._initComment(stateManager, response.parents[0]);
            }
        } else if (highlightedComment.replytoid != undefined) {
            highlightedReply = highlightedComment;
            highlightedComment = state.comments.get(highlightedReply.replytoid);
        }

        stateManager.setReadOnly(false);

        if (highlightedReply) {
            highlightedComment.showreplies = true;
            state.highlight.commentId = highlightedComment.id;
            state.highlight.replyId = highlightedReply.id;
        } else {
            state.highlight.commentId = highlightedComment.id;
        }

        stateManager.setReadOnly(true);
    }

    _initSection(stateManager, loadedSection) {
        const state = stateManager.state;
        const section = Object.assign({id: loadedSection.itemid}, loadedSection);
        state.sections.add(section);
        return section;
    }

    _initComment(stateManager, loadedComment) {
        const state = stateManager.state;

        if (state.comments.has(loadedComment.id)) {
            return this._updateComment(stateManager, loadedComment);
        }

        const comment = Object.assign({
            isediting: false,
            showreplies: false,
            showreplyform: false,
            expanded: false
        }, loadedComment);
        state.comments.add(comment);

        state.commentReplies.add({
            id: comment.id,
            comments: [],
            pagesize: 5,
            sortdirection: 'ASC',
            startatbottom: false,
            moreavailableabove: false,
            moreavailablebelow: true,
            loadingmoreabove: false,
            loadingmorebelow: false,
        });
        return comment;
    }

    _updateComment(stateManager, updatedComment) {
        const state = stateManager.state;
        return Object.assign(state.comments.get(updatedComment.id), updatedComment, {isediting: false});
    }

    async _loadMore(stateManager, replyToId = null, above = null) {
        const state = stateManager.state;
        const commentList = replyToId === null ? state.commentList : state.commentReplies.get(replyToId);
        const comments = commentList.comments.map(c => state.comments.get(c.id));

        if (above === null) {
            above = !!commentList.startatbottom;
        }
        if (above) {
            commentList.loadingmoreabove = true;
        } else {
            commentList.loadingmorebelow = false;
        }

        stateManager.setReadOnly(true);

        // We fetch comments starting from the timecreated of the topmost (or bottommost) comment. In order to ensure
        // that we fetch a complete page anyway, we count the number of already loaded comments with that exact
        // timecreated (overlap).
        let time = null;
        let overlap = 0;
        if (comments.length) {
            if (above) {
                time = comments[0].timecreated;
                while (overlap < comments.length && comments[overlap].timecreated === time) {
                    overlap++;
                }
            } else {
                time = comments[comments.length - 1].timecreated;
                while (overlap < comments.length && comments[comments.length - 1 - overlap].timecreated === time) {
                    overlap++;
                }
            }
        }
        let timeFrom = null;
        let timeTo = null;
        if ((commentList.sortdirection === 'DESC') !== above) {
            timeTo = time;
        } else {
            timeFrom = time;
        }

        // We add one to the page size and overlap to find out if there are more comments available after this page.
        let fetchCount = commentList.pagesize + overlap + 1;
        let sortDirection = commentList.sortdirection;
        if (above) {
            sortDirection = (sortDirection === 'DESC') ? 'ASC' : 'DESC';
        }

        // Fetch comments.
        const result = await api.getComments(
            this.options.contextId, this.options.component, this.options.commentArea,
            replyToId === null ? this.options.itemId : state.comments.get(replyToId).itemid,
            fetchCount, sortDirection, replyToId, timeFrom, timeTo
        );

        stateManager.setReadOnly(false);

        // Save new comments.
        const moreAvailable = result.comments.length === fetchCount;
        let newComments;
        if (above) {
            commentList.moreavailableabove = moreAvailable;
            newComments = result.comments.reverse().slice(-commentList.pagesize - overlap, -overlap);
            commentList.comments.unshift(...newComments.map(c => ({id: c.id})));
        } else {
            commentList.moreavailablebelow = moreAvailable;
            newComments = result.comments.slice(overlap, commentList.pagesize + overlap);
            commentList.comments.push(...newComments.map(c => ({id: c.id})));
        }

        // Initialize comments and sections.
        newComments.forEach(c => this._initComment(stateManager, c));
        const sections = result.commentsections.map(s => this._initSection(stateManager, s));

        if (this.options.itemId != undefined) {
            state.section = sections[0];
        }
    }

    async loadMore(stateManager, replyToId = null, above = null) {
        stateManager.setReadOnly(false);
        await this._loadMore(stateManager, replyToId, above);
        stateManager.setReadOnly(true);
    }

    async _onCommentPosted(stateManager, comment) {
        const state = stateManager.state;
        const commentList = comment.replytoid == undefined ? state.commentList : state.commentReplies.get(comment.replytoid);
        if (!commentList) {
            return;
        }
        if (commentList.sortdirection === 'DESC') {
            if (commentList.moreavailableabove) {
                commentList.comments = [{id: comment.id}];
                commentList.moreavailableabove = false;
                await this._loadMore(stateManager, comment.replytoid, false);
            } else {
                commentList.comments = [{id: comment.id}].concat(commentList.comments);
            }
        } else {
            if (commentList.moreavailablebelow) {
                commentList.comments = [{id: comment.id}];
                commentList.moreavailablebelow = false;
                await this._loadMore(stateManager, comment.replytoid, true);
            } else {
                commentList.comments = commentList.comments.concat([{id: comment.id}]);
            }
        }
    }

    async saveComment(stateManager, comment) {
        const savedComment = await api.saveComment(comment);

        const state = stateManager.state;
        stateManager.setReadOnly(false);

        if (state.comments.has(savedComment.id)) {
            // Existing comment updated.
            this._updateComment(stateManager, savedComment);

        } else {
            // New comment posted.
            const newComment = this._initComment(stateManager, savedComment);

            if (newComment.replytoid != undefined && state.comments.has(newComment.replytoid)) {
                const replyTo = state.comments.get(newComment.replytoid);
                replyTo.replies++;
                replyTo.showreplies = true;
                replyTo.showreplyform = false;
            }
            await this._onCommentPosted(stateManager, newComment);
        }

        stateManager.setReadOnly(true);
    }

    async deleteComment(stateManager, commentId) {
        await api.deleteComments([commentId]);

        const state = stateManager.state;
        if (!state.comments.has(commentId)) {
            return;
        }
        stateManager.setReadOnly(false);

        const comment = state.comments.get(commentId);
        let commentList;
        if (comment.replytoid == undefined) {
            commentList = state.commentList;
        } else {
            commentList = state.commentReplies.get(comment.replytoid);
            state.comments.get(comment.replytoid).replies--;
        }
        commentList.comments = commentList.comments.filter(c => c.id !== commentId);

        if (state.highlight.commentId === commentId) {
            state.highlight.commentId = null;
            state.highlight.replyId = null;
        } else if (state.highlight.replyId === commentId) {
            state.highlight.replyId = null;
        }

        state.comments.delete(commentId);

        stateManager.setReadOnly(true);
    }

    async setCommentExpanded(stateManager, commentId, expanded) {
        const state = stateManager.state;
        stateManager.setReadOnly(false);

        state.comments.get(commentId).expanded = expanded;

        stateManager.setReadOnly(true);
    }

    async setShowReplies(stateManager, commentId, showReplies) {
        const state = stateManager.state;
        stateManager.setReadOnly(false);

        state.comments.get(commentId).showreplies = showReplies;

        if (showReplies) {
            if (commentId === state.highlight.commentId) {
                state.highlight.replyId = null;
            }
            if (!state.commentReplies.get(commentId).comments.length) {
                await this._loadMore(stateManager, commentId);
            }
        }

        stateManager.setReadOnly(true);
    }

    async setShowReplyForm(stateManager, commentId, showReplyForm) {
        const state = stateManager.state;
        stateManager.setReadOnly(false);

        state.comments.get(commentId).showreplyform = showReplyForm;

        stateManager.setReadOnly(true);
    }

    async setEditing(stateManager, commentId, editing) {
        const state = stateManager.state;
        stateManager.setReadOnly(false);

        state.comments.get(commentId).isediting = editing;

        stateManager.setReadOnly(true);
    }
}