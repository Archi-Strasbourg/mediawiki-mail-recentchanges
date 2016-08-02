/*jslint node: true */
module.exports = function (grunt) {
    'use strict';
    grunt.initConfig(
        {
            phpcs: {
                options: {
                    standard: 'PSR2',
                    bin: 'vendor/bin/phpcs'
                },
                php: {
                    src: ['*.php']
                }
            },
            jslint: {
                Gruntfile: {
                    src: ['Gruntfile.js']
                }
            }
        }
    );

    grunt.loadNpmTasks('grunt-phpcs');
    grunt.loadNpmTasks('grunt-jslint');

    grunt.registerTask('lint', ['phpcs', 'jslint']);
};
