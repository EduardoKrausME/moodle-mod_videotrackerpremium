// This file is part of Moodle - http://moodle.org/.

/**
 * Creates the player adapter exposed by the selected Video Bridge source.
 *
 * @module mod_videotrackerpremium/player
 */
define([], function() {
    const create = (root, config) => new Promise((resolve, reject) => {
        if (!config.adaptermodule) {
            reject(new Error('Missing video source adapter module.'));
            return;
        }

        require([config.adaptermodule], (provider) => {
            try {
                if (!provider || typeof provider.create !== 'function') {
                    throw new Error('Invalid video source adapter module.');
                }
                Promise.resolve(provider.create(root, config)).then(resolve).catch(reject);
            } catch (error) {
                reject(error);
            }
        }, reject);
    });

    return {create};
});
