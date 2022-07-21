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
 * Comment highlight module.
 *
 * @module     core_comment/comments
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_comment/component';
import Notification from 'core/notification';

export default class CommentHighlight extends Component {

    constructor(el, parent, options = {}) {
        super('commenthighlight', el, parent);
        this.commentListEl = options.commentListEl;
        this.comment = options.comment;
    }

    async getContext() {
        return this.comment;
    }

    async postRender() {
        await this.addChild(`[data-comment="${this.comment.id}"]`, 'comment', {
            commentListEl: this.commentListEl,
            comment: this.comment
        });

        this.addListener(`[data-dismisshighlightedcomment="${this.comment.id}"]`, 'click', (e) => {
            this.commentListEl.onHighlightDismissed().catch(Notification.exception);
            e.preventDefault();
            return false;
        });
    }

}
