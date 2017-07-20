/*global node:true */

module.exports = function( grunt ) {
	'use strict';

	require('matchdep').filterDev('grunt-*').forEach( grunt.loadNpmTasks );

	grunt.initConfig({

		makepot: {
			plugin: {
				options: {
					mainFile: 'plugin.php',
					domainPath: 'languages',                   // Where to save the POT file.
					exclude: ['v1-legacy/*'],
					potHeaders: {
						poedit: true
					},
					type: 'wp-plugin',
					updateTimestamp: false
				}
			}
		},

	});

};