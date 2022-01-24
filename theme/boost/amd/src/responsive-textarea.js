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
 * Responsive text area functionality.
 *
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const responsiveTextArea = (el) => {
    const textArea = el.querySelector('textarea');
    const div = el.querySelector('div');
    if (!textArea) {
        throw new Error("Textarea not found inside responsive textarea container.");
    }
    if (!div) {
        throw new Error("Div not found inside responsive textarea container.");
    }

    const resize = () => {
        div.textContent = textArea.value + '\xa0'; // \xa0 (nbsp) makes sure the text area grows when enter is pressed.
    };
    resize();

    textArea.addEventListener('input', resize);

    const form = el.closest('form');
    if (form) {
        form.addEventListener('reset', () => setTimeout(resize)); // Wait for the textArea to be reset using timeout.
    }
};
