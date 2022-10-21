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
 * Javascript events for the comment component.
 *
 * @module     core_comment/events
 * @copyright  2022 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {dispatchEvent} from 'core/event_dispatcher';

/**
 * Events for the core_comment subsystem.
 */
export const eventTypes = {
    stateChanged: 'core_comment:stateChanged',
    formCanceled: 'core_comment:formCanceled',
    formSubmitted: 'core_comment:formSubmitted',
};

export const notifyStateChanged = (detail, target) => dispatchEvent(eventTypes.stateChanged, detail, target);
