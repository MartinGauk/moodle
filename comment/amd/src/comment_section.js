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
        Object.assign(this, options);
    }

    async getContext() {
        return {
            startatbottom: this.startAtBottom,
            fillheight: this.fillHeight,
            showform: this.section && this.section.canpost,
            section: this.section
        };
    }

    async postRender() {
        this.commentForm = await this.addChild('[data-commentform]', 'commentform', {
            commentSection: this,
            onCancel: this.commentFormOnCancel
        });
        this.commentList = await this.addChild('[data-commentlist]', 'commentlist', {
            commentSection: this,
            pageSize: this.pageSize,
            sortDirection: this.sortDirection,
            startAtBottom: this.startAtBottom,
            preLoadedComments: this.comments,
            moreAvailableAbove: this.moreAvailableAbove,
            moreAvailableBelow: this.moreAvailableBelow
        });
    }

}