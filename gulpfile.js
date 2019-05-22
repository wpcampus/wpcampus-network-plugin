const autoprefixer = require('gulp-autoprefixer');
const cleanCSS = require('gulp-clean-css');
const gulp = require('gulp');
const mergeMediaQueries = require('gulp-merge-media-queries');
const minify = require('gulp-minify');
const notify = require('gulp-notify');
const rename = require('gulp-rename');
const sass = require('gulp-sass');
const shell = require('gulp-shell');

// Define the source paths for each file type.
const src = {
	js: ['assets/js/src/**/*'],
	php: ['**/*.php','!vendor/**','!node_modules/**'],
    css: ['assets/css/src/**/*']
};

// Define the destination paths for each file type.
const dest = {
	js: 'assets/js',
	css: 'assets/css'
};

// Take care of CSS.
gulp.task('css', function() {
	return gulp.src(src.css)
		.pipe(sass({
			outputStyle: 'expanded' //nested, expanded, compact, compressed
		}).on('error', sass.logError))
		.pipe(mergeMediaQueries())
		.pipe(autoprefixer({
			browsers: ['last 2 versions'],
			cascade: false
		}))
		.pipe(cleanCSS({
			compatibility: 'ie8'
		}))
		.pipe(rename({
			suffix: '.min'
		}))
		.pipe(gulp.dest(dest.css))
		.pipe(notify('WPC Network CSS compiled'));
});

// Take care of JS.
gulp.task('js',function() {
	gulp.src(['./node_modules/mustache/mustache.min.js','./node_modules/handlebars/dist/handlebars.min.js'])
		.pipe(gulp.dest(dest.js));
	gulp.src(src.js)
		.pipe(minify({
			mangle: false,
			noSource: true,
			ext:{
				min:'.min.js'
			}
		}))
		.pipe(gulp.dest(dest.js))
		.pipe(notify('WPC Network JS compiled'));
});

// "Sniff" our PHP.
gulp.task('php', function() {
	// TODO: Clean up. Want to run command and show notify for sniff errors.
	return gulp.src('wpcampus-network.php', {read: false})
		.pipe(shell(['composer sniff'], {
			ignoreErrors: true,
			verbose: false
		}))
		.pipe(notify('WPC Network PHP sniffed'), {
			onLast: true,
			emitError: true
		});
});

// Test our files.
gulp.task('test',['php']);

// Compile our assets.
gulp.task('compile',['css','js']);

// I've got my eyes on you(r file changes).
gulp.task('watch',['default'],function() {
	gulp.watch(src.css, ['css']);
	gulp.watch(src.js,['js']);
	gulp.watch(src.php,['php']);
});

// Let's get this party started.
gulp.task('default',['compile','test']);
