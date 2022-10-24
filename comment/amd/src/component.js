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
 * Comment UI component module.
 *
 * @module     core_comment/comments
 * @copyright  2021 TU Berlin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as templates from 'core/templates';
import Notification from 'core/notification';
import {BaseComponent} from 'core/reactive';

export default class Component extends BaseComponent {

    constructor(descriptor) {
        super(descriptor);
        this.name = descriptor.name;
        this.parent = descriptor.parent;
        if (!descriptor.name) {
            throw new Error('Name missing in descriptor');
        }
        this.uniqid = null;
        this.children = {};
        this.rendering = false;
        this.rendered = false;
        this.renderingQueued = false;
        this._createRenderPromise();
    }

    async getTemplate() {
        const templateKey = this.name + 'template';
        const renderOptions = this.getRenderOptions();
        if (!(templateKey in renderOptions)) {
            throw new Error('Template key not found in renderoptions: ' + templateKey);
        }
        return renderOptions[templateKey];
    }

    async getContext() {
        return {};
    }

    getRenderOptions() {
        return this.reactive.renderOptions;
    }

    getState() {
        return this.reactive.stateManager.state;
    }

    stateReady() {
        this.render().catch(Notification.exception);
    }

    async preRender(template, context) {
        return context;
    }

    async addListeners() {
        // Nop.
    }

    async addChildren() {
        // Nop.
    }

    // eslint-disable-next-line no-unused-vars
    async postRender(template, context) {
        // Nop.
    }

    async render() {
        if (this.rendering) {
            if (this.renderingQueued) {
                return;
            }
            this.renderingQueued = true;
            await this.renderPromise;
            this.renderingQueued = false;
        }
        this.rendering = true;
        if (this.rendered) {
            this._createRenderPromise();
        }
        try {
            const template = await this.getTemplate();
            let context = await this.getContext();
            // Copy context to prevent accidental modification of the original object.
            context = Object.assign({}, context);
            context = await this.preRender(template, context);
            context = this.callback('prerender', [template, context]) || context;

            // TODO remove logging
            // eslint-disable-next-line no-console
            console.log('rendering template ' + template + ' with context ', context);
            const {html, js} = await templates.renderForPromise(template, context);
            this.uniqid = context.uniqid;

            this.detachChildren();
            templates.replaceNodeContents(this.element, html, js);

            await this.addListeners();
            await this.addChildren();

            await this.postRender(template, context);
            this.callback('postrender', [template, context, this.element]);

            this.rendered = true;
            this.rendering = false;
            this._resolveRenderPromise();
        } catch (e) {
            this.rendering = false;
            this._rejectRenderPromise(e);
            await Notification.exception(e);
        }
    }

    getChildren() {
        return Object.values(this.children);
    }

    detachChildren() {
        this.getChildren().forEach((child) => {
            if (child.element.parentElement) {
                child.element.parentElement.removeChild(child.element);
            }
        });
    }

    async addChild(selector, childName, cached = true) {
        const childEl = this.getElement(selector);
        if (childEl) {
            if (cached && this.children[selector]) {
                const child = this.children[selector];
                childEl.replaceWith(child.element);
                return child;
            } else {
                const childClassKey = childName + 'class';
                const renderOptions = this.getRenderOptions();
                if (!(childClassKey in renderOptions)) {
                    throw new Error('Component class key not found in renderoptions: ' + childClassKey);
                }
                const child = new renderOptions[childClassKey]({
                    element: childEl,
                    name: childName,
                    parent: this,
                    reactive: this.reactive
                });
                this.children[selector] = child;
                return child;
            }
        }
        return null;
    }

    removeChild(child) {
        Object.entries(this.children)
            .filter(([, value]) => value === child)
            .forEach(([key,]) => {
                this.children[key].dispose();
                delete this.children[key];
            });
    }

    addListener(selector, event, callback) {
        const targetEl = this.getElement(selector);
        if (targetEl) {
            this.addEventListener(targetEl, event, callback);
        }
    }

    focus() {
        const focusable = this.element.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable) {
            focusable.focus();
        }
    }

    callback(callbackName, args, defaultValue = undefined) {
        const callbackKey = this.name + callbackName;
        const renderOptions = this.getRenderOptions();
        if (renderOptions[callbackKey]) {
            const type = typeof renderOptions[callbackKey];
            if (type !== 'function') {
                throw new Error("Expected callback function to be of type 'function', got '" + type + "' instead.");
            }
            try {
                return renderOptions[callbackKey](...args);
            } catch (e) {
                Notification.exception(e);
            }
        }
        return defaultValue;
    }

    _createRenderPromise() {
        this.renderPromise = new Promise((resolve, reject) => {
            this._resolveRenderPromise = resolve;
            this._rejectRenderPromise = reject;
        });
    }

    async unregisterChildren() {
        await Promise.all(this.getChildren().map((child) => child.unregister()));
        this.children = {};
    }

    async destroy() {
        await this.unregisterChildren();
    }
}