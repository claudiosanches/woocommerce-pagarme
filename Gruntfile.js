/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	grunt.initConfig({

		// Gets the package vars.
		pkg: grunt.file.readJSON( 'package.json' ),

		// Setting folder templates.
		dirs: {
			css:    'assets/css',
			fonts:  'assets/fonts',
			images: 'assets/images',
			js:     'assets/js'
		},

		// Javascript linting with jshint.
		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: [
				'Gruntfile.js',
				'<%= dirs.js %>/*/*.js',
				'!<%= dirs.js %>/*/*.min.js'
			]
		},

		// Minify .js files.
		uglify: {
			options: {
				preserveComments: /^!/
			},
			dist: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>/',
					ext: '.min.js'
				}]
			}
		},

		// Watch changes for assets.
		watch: {
			js: {
				files: [
					'<%= dirs.js %>/*js',
					'!<%= dirs.js %>/*.min.js'
				],
				tasks: ['jshint', 'uglify']
			}
		},

		// Image optimization.
		imagemin: {
			dist: {
				options: {
					optimizationLevel: 7,
					progressive: true
				},
				files: [{
					expand: true,
					cwd: './',
					src: 'screenshot-*.png',
					dest: './'
				}]
			}
		},

		// Make .pot files.
		makepot: {
			dist: {
				options: {
					type: 'wp-plugin'
				}
			}
		},

		// Check text domain.
		checktextdomain: {
			options:{
				text_domain: '<%= pkg.name %>',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src:  [
					'**/*.php', // Include all files.
					'!node_modules/**' // Exclude node_modules/
				],
				expand: true
			}
		},

		// Create README.md for GitHub.
		wp_readme_to_markdown: {
			options: {
				screenshot_url: 'http://ps.w.org/<%= pkg.name %>/assets/{screenshot}.png'
			},
			dest: {
				files: {
					'README.md': 'readme.txt'
				}
			}
		},

		// PHP Code Sniffer.
		phpcs: {
			options: {
				bin: 'vendor/bin/phpcs',
				showSniffCodes: true,
				standard: './phpcs.xml'
			},
			dist: {
				src:  [
					'**/*.php',         // Include all files
					'!node_modules/**', // Exclude node_modules/
					'!vendor/**'        // Exclude vendor/
				]
			}
		}
	});

	// Load NPM tasks to be used here.
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-imagemin' );
	grunt.loadNpmTasks( 'grunt-checktextdomain' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-phpcs' );

	// Register tasks.
	grunt.registerTask( 'default', [
		'jshint',
		'uglify'
	]);

	grunt.registerTask( 'readme', 'wp_readme_to_markdown' );

	grunt.registerTask( 'dev', [
		'default',
		'readme',
		'phpcs',
		'checktextdomain',
		'makepot'
	]);
};
