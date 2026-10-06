// This file is part of Moodle - http://moodle.org/.

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
