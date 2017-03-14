'use strict';

module.exports = function (grunt) {
    // load all grunt tasks
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-bower-task');
    grunt.loadNpmTasks('grunt-contrib-uglify');


    grunt.initConfig({
        watch: {
            files: [
                "less/*.less",
                "less/bootstrap/*.less",
                "templates/partials/*.html*",
                "templates/*.twig",
                "js/**/*.js",
                "assets/img/**/*.{png,jpg,svg,ico,gif}",
                "assets/img/products/*/*.{png,jpg,svg,ico}",
                "assets/static/*"
            ],
            tasks: ["less:dev", "copy", "concat:js"]
        },
        // "less"-task configuration
        less: {
            dev: {
                options: {
                    paths: ["less/"],
                    dumpLineNumbers: 'all'
                },
                files: {
                    "public/css/app.css": ["less/*.less"],
                    "public/css/theme.css": "less/theme.less"
                }
            },
            dist:{
                options: {
                    paths: ["less/"],
                    cleancss: true
                },
                files: {
                    "public/css/app.css": ["less/*.less"],
                    "public/css/theme.css": "less/theme.less"
                }
            }
        },
        copy: {
            fonts: {
                cwd: 'assets/fonts',
                src: '*',
                dest: 'public/fonts',
                expand: true
            },
            templates: {
                cwd: 'templates',
                src: 'partials/*',
                dest: 'public/templates',
                expand: true
            },
            images: {
                cwd: 'assets/img',
                expand: true,
                src: ['**/*.{png,jpg,svg,ico}'],
                dest: 'public/img/'
            },
            fixtures: {
                cwd: 'assets/fixtures',
                expand: true,
                src: ['*'],
                dest: 'public/fixtures/'
            },
            static: {
                cwd: 'assets/static',
                expand: true,
                src: ['*'],
                dest: 'public/static/'
            }

        },
        concat: {
            options: {
                separator: ';'
            },
            js: {
                src: [  'js/detection/*js',
                        'js/app/services.js',
                        'js/app/controllers.js',
                        'js/app/filters.js',
                        'js/app/directives/*.js',
                        'js/app/app.js',
                        'js/*.js',
                        'node_modules/autotrack/autotrack.js'],
                dest: 'public/js/app.js'
            }

        },
        bower: {
            install: {
                options: {
                    targetDir: 'public/vendor',
                    layout: 'byType',
                    install: true,
                    verbose: false,
                    cleanTargetDir: false,
                    cleanBowerDir: false,
                    bowerOptions: {}
                }
            }
        },
        uglify: {
            dist: {
                files: [{
                    expand: true,
                    cwd: 'public/js',
                    src: '**/*.js',
                    dest: 'public/js'
                }]
            }
        }



    });
    grunt.registerTask('default', ['build', 'watch']);
    grunt.registerTask('dist', ['bower', 'copy', 'concat', 'uglify', 'less:dist']);
    grunt.registerTask('build',['bower', 'copy', 'concat', 'less:dev']);
};