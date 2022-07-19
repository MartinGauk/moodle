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
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';

export default class CommentSection extends Component {

    constructor(el, parent = null, options = {}) {
        super('commentsection', el, parent);
        this.options = options;
        this.renderOptions = options.renderOptions;
        this.contextId = options.contextId;
        this.component = options.component;
        this.commentArea = options.commentArea;
        this.itemId = options.itemId;
        this.section = options.section;
        this.sections = options.sections;
    }

    async getContext() {
        return {
            startatbottom: this.options.startAtBottom,
            fillheight: this.options.fillHeight,
            maxlistheight: this.options.maxListHeight,
            showform: this.section && this.section.canpost,
            section: this.section
        };
    }

    async postRender() {
        this.commentFormEl = await this.addChild('[data-commentform]', 'commentform', {
            commentSectionEl: this,
            onCancel: this.options.commentFormOnCancel
        });
        this.commentListEl = await this.addChild('[data-commentlist]', 'commentlist', {
            commentSectionEl: this,
            pageSize: this.options.pageSize,
            sortDirection: this.options.sortDirection,
            startAtBottom: this.options.startAtBottom,
            preLoadedComments: this.options.comments,
            moreAvailableAbove: this.options.moreAvailableAbove,
            moreAvailableBelow: this.options.moreAvailableBelow,
            highlightedComment: this.options.highlightedComment,
            highlightedReply: this.options.highlightedReply
        });
    }

}