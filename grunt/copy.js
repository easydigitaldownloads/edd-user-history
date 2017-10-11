module.exports = {
	main: {
		src: [
			'**',
			'!.git/**',
			'!.gitignore',
			'!.gitmodules',
			'!.tx/**',
			'!node_modules/**',
			'!build/**',
			'!bin/**',
			'!grunt/**',
			'!tests/**',
			'!composer.json',
			'!Gruntfile.js',
			'!package.json',
			'!phpunit.xml',
			'!**/Gruntfile.js',
			'!**/package.json',
			'!**/README.md',
			'!**/*~'
			],
		dest: 'build/<%= pkg.name %>/'
	}
};
