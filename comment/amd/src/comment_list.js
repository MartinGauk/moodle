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
import * as Comments from 'core_comment/comments';
import Notification from 'core/notification';

export default class CommentList extends Component {

    constructor(el, parent, options = {}) {
        super('commentlist', el, parent);
        this.commentSectionEl = options.commentSectionEl;
        this.replyToEl = options.replyToEl || null;
        this.pageSize = options.pageSize || 10;
        this.sortDirection = (options.sortDirection || 'DESC').toUpperCase();
        this.startAtBottom = options.startAtBottom;
        this.moreAvailableAbove = 'moreAvailableAbove' in options ? options.moreAvailableAbove : !!this.startAtBottom;
        this.moreAvailableBelow = 'moreAvailableBelow' in options ? options.moreAvailableBelow : !this.startAtBottom;
        this.comments = options.preLoadedComments || [];
        this.highlightedComment = options.highlightedComment;
        this.highlightedReply = options.highlightedReply;
    }

    async getContext() {
        let comments = this.comments;
        if (this.highlightedComment) {
            comments = comments.filter(c => c.id !== this.highlightedComment.id);
        }
        return {
            replyto: this.replyToEl ? this.replyToEl.comment : null,
            highlightedcomment: this.highlightedComment,
            comments: comments,
            count: comments.length,
            moreavailableabove: this.moreAvailableAbove,
            moreavailablebelow: this.moreAvailableBelow,
        };
    }

    async loadMore(above = null) {
        if (above === null) {
            above = this.startAtBottom;
        }

        // Start loading animation. Will stop automatically when the component is rendered again.
        const buttonEl = this.el.querySelector(above ?
            `[data-loadmoreabove="${this.uniqid}"]` : `[data-loadmorebelow="${this.uniqid}"]`);
        if (buttonEl) {
            buttonEl.outerHTML = '<i class="icon fa fa-circle-o-notch fa-spin fa-fw ml-4"></i>';
        }

        // We fetch comments starting from the timecreated of the topmost (or bottommost) comment. In order to ensure
        // that we fetch a complete page anyway, we count the number of already loaded comments with that exact
        // timecreated (overlap).
        let time = null;
        let overlap = 0;
        if (this.comments && this.comments.length) {
            if (above) {
                time = this.comments[0].timecreated;
                while (overlap < this.comments.length && this.comments[overlap].timecreated === time) {
                    overlap++;
                }
            } else {
                time = this.comments[this.comments.length - 1].timecreated;
                while (overlap < this.comments.length && this.comments[this.comments.length - 1 - overlap].timecreated === time) {
                    overlap++;
                }
            }
        }
        let timeFrom = null;
        let timeTo = null;
        if ((this.sortDirection === 'DESC') !== above) {
            timeTo = time;
        } else {
            timeFrom = time;
        }

        // We add one to the page size and overlap to find out if there are more comments available after this page.
        let pageSize = this.pageSize + overlap + 1;
        let sortDirection = this.sortDirection;
        if (above) {
            sortDirection = (sortDirection === 'DESC') ? 'ASC' : 'DESC';
        }

        // Fetch comments.
        const result = await Comments.getComments(
            this.commentSectionEl.contextId, this.commentSectionEl.component, this.commentSectionEl.commentArea,
            this.replyToEl ? this.replyToEl.comment.itemid : this.commentSectionEl.itemId, pageSize, sortDirection,
            this.replyToEl ? this.replyToEl.comment.id : null, timeFrom, timeTo
        );

        // Save new comments.
        const moreAvailable = result.comments.length === pageSize;
        if (above) {
            this.moreAvailableAbove = moreAvailable;
            this.comments.unshift(...result.comments.reverse().slice(-this.pageSize - overlap, -overlap));
        } else {
            this.moreAvailableBelow = moreAvailable;
            this.comments.push(...result.comments.slice(overlap, this.pageSize + overlap));
        }

        // Save new comment sections.
        this.commentSectionEl.sections.push(
            ...result.commentsections.filter(
                section => !this.commentSectionEl.sections.find(other => other.itemid === section.itemid)
            )
        );

        await this.render();
    }

    async onCommentDeleted(id) {
        for (let i = 0; i < this.comments.length; i++) {
            if (this.comments[i].id === id) {
                this.comments.splice(i, 1);
                break;
            }
        }
        await this.render();
        this.getChildren().filter((child) => child instanceof Comment && child.comment.id === id).forEach((child) => {
            this.removeChild(child);
        });
    }

    async onCommentPosted(comment) {
        if (this.sortDirection === 'DESC') {
            if (this.moreAvailableAbove) {
                this.comments = [comment];
                this.moreAvailableAbove = false;
                await this.loadMore(false);
            } else {
                this.comments.unshift(comment);
                await this.render();
            }
        } else {
            if (this.moreAvailableBelow) {
                this.comments = [comment];
                this.moreAvailableBelow = false;
                await this.loadMore(true);
            } else {
                this.comments.push(comment);
                await this.render();
            }
        }

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

        const loadMoreAbove = this.el.querySelector(`[data-loadmoreabove="${this.uniqid}"]`);
        const loadMoreBelow = this.el.querySelector(`[data-loadmorebelow="${this.uniqid}"]`);
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

    async postRender() {
        let comments = this.comments;
        if (this.highlightedComment && !comments.includes(this.highlightedComment)) {
            comments = comments.concat(this.highlightedComment);
        }
        await Promise.all(comments.map((comment) => {
            return this.addChild(`[data-comment="${comment.id}"]`, 'comment', {
                commentListEl: this,
                comment: comment
            });
        }));

        this.addListener(`[data-loadmoreabove="${this.uniqid}"]`, 'click', (e) => {
            this.loadMore(true).catch(Notification.exception);
            e.preventDefault();
            return false;
        });
        this.addListener(`[data-loadmorebelow="${this.uniqid}"]`, 'click', (e) => {
            this.loadMore(false).catch(Notification.exception);
            e.preventDefault();
            return false;
        });
        if (this.highlightedComment) {
            this.addListener(`[data-dismisshighlightedcomment="${this.highlightedComment.id}"]`, 'click', (e) => {
                this.highlightedComment = null;
                this.highlightedReply = null;
                this.render().catch(Notification.exception);
                e.preventDefault();
                return false;
            });
        }

        if (!this.replyToEl) {
            this.addIntersectionObserver();
        }
    }

}
