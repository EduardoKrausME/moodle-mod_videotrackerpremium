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
 * view.js
 *
 * @package   mod_videotrackerpremium
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Connects a Video Bridge source adapter to the shared authoritative progress tracker.
 *
 * @module mod_videotrackerpremium/view
 */
define([
    'mod_videotrackerpremium/player',
    'local_video_bridge/progress',
], function(Player, Progress) {
    const init = (rootid, config) => {
        const root = document.getElementById(rootid);
        if (!root) {
            return;
        }

        Player.create(root, config)
            .then((adapter) => Progress.attach(adapter, root, config))
            .catch(() => {
                root.classList.add('videotrackerpremium-player-error');
                const error = root.parentElement.querySelector('[data-region="player-error"]');
                if (error) {
                    error.classList.remove('d-none');
                }
            });
    };

    return {init};
});
