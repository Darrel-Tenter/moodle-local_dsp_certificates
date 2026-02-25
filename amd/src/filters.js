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
 * AMD module: DSP Certificate Lookup — filter form behaviour.
 *
 * Responsibilities:
 *  1. Populate the staff member autocomplete with tenant users passed from PHP.
 *  2. Restore the selected user's name in the visible text input on page load.
 *  3. Clear the hidden userid field when the text input is cleared.
 *
 * No external dependencies beyond core AMD modules.
 *
 * @module    local_dsp_certificates/filters
 * @copyright 2026 Direct Support Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/log'], function(Log) {

    'use strict';

    /** @type {Array}  Full list of tenant users: [{id, fullname}, ...] */
    let tenantUsers = [];

    /** @type {number} Currently selected user ID (0 = none). */
    let selectedUserId = 0;

    /**
     * Initialise the filters module.
     *
     * Called from index.php via $PAGE->requires->js_call_amd().
     *
     * @param {Object} config
     * @param {Array}  config.tenantUsers    Array of {id, fullname} objects.
     * @param {number} config.selectedUserId Currently selected userid (from GET param).
     * @param {Object} config.strings        Localised strings from PHP.
     */
    function init(config) {
        tenantUsers    = config.tenantUsers    || [];
        selectedUserId = config.selectedUserId || 0;

        const textInput   = document.getElementById('dsp-cert-user-input');
        const hiddenInput = document.getElementById('dsp-cert-userid');
        const dropdown    = document.getElementById('dsp-cert-user-dropdown');

        if (!textInput || !hiddenInput || !dropdown) {
            Log.warn('local_dsp_certificates/filters: required DOM elements not found.');
            return;
        }

        // ── Set placeholder ──────────────────────────────────────────────────
        if (config.strings && config.strings.placeholder) {
            textInput.placeholder = config.strings.placeholder;
        }

        // ── Restore selected user name on page load ──────────────────────────
        if (selectedUserId > 0) {
            const match = tenantUsers.find(u => u.id === selectedUserId);
            if (match) {
                textInput.value = match.fullname;
            }
        }

        // ── Autocomplete: filter on input ─────────────────────────────────────
        textInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();

            // Clear the hidden userid if the text changes.
            hiddenInput.value = '0';
            selectedUserId    = 0;

            if (query.length < 1) {
                closeDropdown(dropdown);
                return;
            }

            const matches = tenantUsers.filter(u =>
                u.fullname.toLowerCase().includes(query)
            ).slice(0, 20); // Cap at 20 results for performance.

            if (matches.length === 0) {
                closeDropdown(dropdown);
                return;
            }

            renderDropdown(dropdown, matches, textInput, hiddenInput);
        });

        // ── Close dropdown on outside click ───────────────────────────────────
        document.addEventListener('click', function(e) {
            if (!textInput.contains(e.target) && !dropdown.contains(e.target)) {
                closeDropdown(dropdown);
            }
        });

        // ── Keyboard navigation ───────────────────────────────────────────────
        textInput.addEventListener('keydown', function(e) {
            const items = dropdown.querySelectorAll('.dropdown-item');
            const active = dropdown.querySelector('.dropdown-item.active');
            let idx = Array.from(items).indexOf(active);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                idx = Math.min(idx + 1, items.length - 1);
                setActiveItem(items, idx);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                idx = Math.max(idx - 1, 0);
                setActiveItem(items, idx);
            } else if (e.key === 'Enter' && active) {
                e.preventDefault();
                active.click();
            } else if (e.key === 'Escape') {
                closeDropdown(dropdown);
            }
        });
    }

    /**
     * Render the dropdown list with filtered user matches.
     *
     * @param {HTMLElement} dropdown
     * @param {Array}       matches
     * @param {HTMLElement} textInput
     * @param {HTMLElement} hiddenInput
     */
    function renderDropdown(dropdown, matches, textInput, hiddenInput) {
        dropdown.innerHTML = '';

        matches.forEach(function(user) {
            const item = document.createElement('button');
            item.type        = 'button';
            item.className   = 'dropdown-item';
            item.textContent = user.fullname;
            item.dataset.userid = user.id;

            item.addEventListener('click', function() {
                textInput.value   = user.fullname;
                hiddenInput.value = user.id;
                selectedUserId    = user.id;
                closeDropdown(dropdown);
            });

            dropdown.appendChild(item);
        });

        dropdown.style.display = 'block';
    }

    /**
     * Hide and clear the dropdown.
     *
     * @param {HTMLElement} dropdown
     */
    function closeDropdown(dropdown) {
        dropdown.style.display = 'none';
        dropdown.innerHTML     = '';
    }

    /**
     * Set the active (keyboard-highlighted) dropdown item.
     *
     * @param {NodeList} items
     * @param {number}   idx
     */
    function setActiveItem(items, idx) {
        items.forEach(function(item, i) {
            item.classList.toggle('active', i === idx);
        });
        if (items[idx]) {
            items[idx].scrollIntoView({block: 'nearest'});
        }
    }

    return {
        init: init
    };
});
