const autoprefixer = require('gulp-autoprefixer');
const cleanCSS = require('gulp-clean-css');
const gulp = require('gulp');
const mergeMediaQueries = require('gulp-merge-media-queries');
const minify = require('gulp-minify');
const notify = require('gulp-notify');
const rename = require('gulp-rename');
const sass = require('gulp-sass');

// Define the source paths for each file type.
const src = {
	js: ['assets/js/src/**/*.js'],
    css: ['assets/css/src/**/*.scss']
};

// Define the destination paths for each file type.
const dest = {
	js: 'assets/js',
	css: 'assets/css'
};

gulp.task('css', function(done) {
	return gulp.src(src.css)
// Take care of SASS.
		.pipe(sass({
			outputStyle: 'expanded' //nested, expanded, compact, compressed
		}).on('error', sass.logError))
		.pipe(mergeMediaQueries())
		.pipe(autoprefixer({
			cascade: false
		}))
		.pipe(cleanCSS({
			compatibility: 'ie8'
		}))
		.pipe(rename({
			suffix: '.min'
		}))
		.pipe(gulp.dest(dest.css))
		.pipe(notify('WPC Network CSS compiled'))
		.on('end',done);
});

// Move our third-party assets.
gulp.task('handlebars',function(done) {
	return gulp.src(['./node_modules/mustache/mustache.min.js','./node_modules/handlebars/dist/handlebars.min.js'])
		.pipe(gulp.dest(dest.js))
		.on('end',done);
});

gulp.task( 'wpcconduct', function( done ) {
	gulp.src([
		'./node_modules/@wpcampus/wpcampus-wc-conduct/dist/main.js'
	])
		.pipe(rename({
			basename: "wpcampus-conduct",
			suffix: '.min'
		}))
		.pipe( gulp.dest( dest.js + '/@wpcampus/' ) );

	return gulp.src([
		'./node_modules/@wpcampus/wpcampus-wc-conduct/dist/main.css'
	])
		.pipe(rename({
			basename: "wpcampus-conduct",
			suffix: '.min'
		}))
		.pipe( gulp.dest( dest.css + '/@wpcampus/' ) )
		.on( 'end', done );
});

gulp.task( 'wpcfooter', function( done ) {
	gulp.src([
		'./node_modules/@wpcampus/wpcampus-wc-footer/dist/main.js'
	])
		.pipe(rename({
			basename: "wpcampus-footer",
			suffix: '.min'
		}))
		.pipe( gulp.dest( dest.js + '/@wpcampus/' ) );

	return gulp.src([
		'./node_modules/@wpcampus/wpcampus-wc-footer/dist/main.css'
	])
		.pipe(rename({
			basename: "wpcampus-footer",
			suffix: '.min'
		}))
		.pipe( gulp.dest( dest.css + '/@wpcampus/' ) )
		.on( 'end', done );
});

// Take care of JS.
gulp.task('js',function(done) {
	return gulp.src(src.js)
		.pipe(minify({
			mangle: false,
			noSource: true,
			ext:{
				min:'.min.js'
			}
		}))
		.pipe(gulp.dest(dest.js))
		.pipe(notify('WPC Network JS compiled'))
		.on('end',done);
});

// Compile our assets.
gulp.task('compile',gulp.series('css','js'));

// Let's get this party started.
gulp.task('default', gulp.series('compile','handlebars','wpcconduct','wpcfooter'));

// I've got my eyes on you(r file changes).
gulp.task('watch', gulp.series('default',function(done) {
	gulp.watch(src.js, gulp.series('js'));
	gulp.watch(src.css,gulp.series('css'));
	return done();
}));
