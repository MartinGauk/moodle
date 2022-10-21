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
 * Comment list module.
 *
 * @module     core_comment/comments
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';
import Comment from 'core_comment/comment';
import Notification from 'core/notification';

export default class CommentList extends Component {

    create() {
        this.selectors = {
            LOAD_MORE_ABOVE: `[data-for="loadmoreabove"]`,
            LOAD_MORE_BELOW: `[data-for="loadmorebelow"]`,
            COMMENT: `[data-for="comment"]`,
            COMMENT_HIGHLIGHT: `[data-for="commenthighlight"]`,
        };
    }

    getWatchers() {
        return [
            {watch: `commentList:updated`, handler: this.render},
        ];
    }

    getData() {
        return this.getState().commentList;
    }

    getComments() {
        return this.getData().comments;
    }

    getHighlightedComment() {
        return this.getState().comments.get(this.getState().highlight.commentId);
    }

    async getContext() {
        let comments = this.getComments();
        const highlightedComment = this.getHighlightedComment();
        if (highlightedComment) {
            comments = comments.filter(c => c.id !== highlightedComment.id);
        }
        return Object.assign({},
            this.getData(),
            {
                highlightedcomment: highlightedComment,
                comments: comments,
                count: comments.length
            });
    }

    async onCommentDeleted(id) {
        // TODO
        this.getChildren().filter((child) => child instanceof Comment && child.comment.id === id).forEach((child) => {
            this.removeChild(child);
        });
    }

    async onCommentPosted(comment) {
        // TODO
        window.setTimeout(() => this.scrollToComment(comment.id), 0);
    }

    scrollToComment(id) {
        const comment = this.children[`[data-comment="${id}"]`];
        if (!comment) {
            return false;
        }
        this.el.scrollTop = comment.el.offsetTop - 10;
        // TODO doesnt work
        // eslint-disable-next-line no-console
        console.log('scrolled to ' + id);
        return true;
    }

    addIntersectionObserver() {
        if (!IntersectionObserver) { // IE is dumb.
            return;
        }
        if (this.intersectionObserver) {
            this.intersectionObserver.disconnect();
            this.intersectionObserver = null;
        }

        const loadMoreAbove = this.getElement(this.selectors.LOAD_MORE_ABOVE);
        const loadMoreBelow = this.getElement(this.selectors.LOAD_MORE_BELOW);
        if (!loadMoreAbove && !loadMoreBelow) {
            return;
        }

        this.intersectionObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) {
                    return;
                }
                if (entry.target === loadMoreAbove) {
                    this.loadMore(true).catch(Notification.exception);
                }
                if (entry.target === loadMoreBelow) {
                    this.loadMore(false).catch(Notification.exception);
                }
                this.intersectionObserver.disconnect();
                this.intersectionObserver = null;
            });
        });

        if (loadMoreAbove) {
            this.intersectionObserver.observe(loadMoreAbove);
        }
        if (loadMoreBelow) {
            this.intersectionObserver.observe(loadMoreBelow);
        }
    }

    async loadMore(above = null) {
        await this.reactive.dispatch('loadMore', null, above);
    }

    async addListeners() {
        this.addListener(this.selectors.LOAD_MORE_ABOVE, 'click', (e) => {
            this.loadMore(true).catch(Notification.exception);
            e.preventDefault();
            return false;
        });
        this.addListener(this.selectors.LOAD_MORE_BELOW, 'click', (e) => {
            this.loadMore(false).catch(Notification.exception);
            e.preventDefault();
            return false;
        });
    }

    async addChildren() {
        await Promise.all(this.getComments().map(comment => {
            return this.addChild(`${this.selectors.COMMENT}[data-commentId='${comment.id}']`, 'comment');
        }));
        const highlightedComment = this.getHighlightedComment();
        if (highlightedComment) {
            await this.addChild(this.selectors.COMMENT_HIGHLIGHT, 'commenthighlight');
        }
    }

    async postRender() {
        this.addIntersectionObserver();
    }

}
