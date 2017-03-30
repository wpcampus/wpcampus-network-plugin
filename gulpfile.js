// Require all the things (that we need)
var autoprefixer = require('gulp-autoprefixer');
var gulp = require('gulp');
var phpcs = require('gulp-phpcs');
var sass = require('gulp-sass');
var watch = require('gulp-watch');

// Define the source paths for each file type
var src = {
    scss: './assets/scss/**/*',
	php: ['**/*.php','!vendor/**','!node_modules/**']
};

// Define the destination paths for each file type
var dest = {
	scss: './assets/css'
};

// Sass is pretty awesome, right?
gulp.task('sass', function() {
    return gulp.src(src.scss)
        .pipe(sass({
			outputStyle: 'compressed'
		})
		.on('error', sass.logError))
        .pipe(autoprefixer({
        	browsers: ['last 2 versions'],
			cascade: false
		}))
		.pipe(gulp.dest(dest.scss));
});

// Check our PHP
gulp.task('php',function() {
	gulp.src(src.php)
		.pipe(phpcs({
			bin: 'vendor/bin/phpcs',
			standard: 'WordPress-Core'
		}))
		.pipe(phpcs.reporter('log'));
});

// I've got my eyes on you(r file changes)
gulp.task('watch', function() {
	gulp.watch(src.scss, ['sass']);
	gulp.watch(src.php,['php']);
});

// Test our files.
gulp.task('test',['php']);

// Compile our assets.
gulp.task('compile',['sass']);

// Let's get this party started
gulp.task('default', ['compile','test']);