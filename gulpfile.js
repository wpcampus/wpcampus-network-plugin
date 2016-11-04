// Require all the things (that we need)
var gulp = require('gulp');
var watch = require('gulp-watch');
var sass = require('gulp-sass');
var autoprefixer = require('gulp-autoprefixer');

// Define the source paths for each file type
var src = {
    scss: './assets/scss/**/*'
};

// Define the destination paths for each file type
var dest = {
	scss: './assets/css'
}

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

// I've got my eyes on you(r file changes)
gulp.task('watch', function() {
	gulp.watch(src.scss, ['sass']);
});

// Let's get this party started
gulp.task('default', ['sass','watch']);