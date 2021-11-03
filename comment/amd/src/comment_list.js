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
 * @package    core_comment
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';
import Comment from 'core_comment/comment';
import Notification from 'core/notification';

export default class CommentList extends Component {

    constructor(el, parent, options = {}) {
        super('commentlist', el, parent);
        this.commentSection = options.commentSection;
        this.replyTo = options.replyTo || null;
        this.pageSize = options.pageSize || 10;
        this.sortDirection = (options.sortDirection || 'DESC').toUpperCase();
        this.startAtBottom = options.startAtBottom;
        this.moreAvailableAbove = 'moreAvailableAbove' in options ? options.moreAvailableAbove : !!this.startAtBottom;
        this.moreAvailableBelow = 'moreAvailableBelow' in options ? options.moreAvailableBelow : !this.startAtBottom;
        this.comments = options.preLoadedComments || [];
    }

    async getContext() {
        return {
            replyto: this.replyTo ? this.replyTo.comment : null,
            comments: this.comments || [],
            count: this.comments ? this.comments.length : 0,
            moreavailableabove: this.moreAvailableAbove,
            moreavailablebelow: this.moreAvailableBelow
        };
    }

    async loadMore(above = null) {
        if (above === null) {
            above = this.startAtBottom;
        }

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

        let pageSize = this.pageSize + overlap + 1;
        let sortDirection = this.sortDirection;
        if (above) {
            sortDirection = (sortDirection === 'DESC') ? 'ASC' : 'DESC';
        }
        const newComments = await this.commentSection.getComments(pageSize, sortDirection, this.replyTo, timeFrom, timeTo);
        const moreAvailable = newComments.length === pageSize;
        if (above) {
            this.moreAvailableAbove = moreAvailable;
            this.comments.unshift(...newComments.reverse().slice(-this.pageSize - overlap, -overlap));
        } else {
            this.moreAvailableBelow = moreAvailable;
            this.comments.push(...newComments.slice(overlap, this.pageSize + overlap));
        }

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

    async postRender() {
        await Promise.all(this.comments.map((comment) => {
            return this.addChild(`[data-comment="${comment.id}"]`, 'comment', {commentList: this, comment: comment});
        }));
        this.addListener('[data-loadmoreabove]', 'click', (e) => {
            this.loadMore(true).catch(Notification.exception);
            e.preventDefault();
            return false;
        });
        this.addListener('[data-loadmorebelow]', 'click', (e) => {
            this.loadMore(false).catch(Notification.exception);
            e.preventDefault();
            return false;
        });
    }

}
