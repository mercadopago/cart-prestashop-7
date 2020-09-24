const gulp = require('gulp');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');
const cleanCSS = require('gulp-clean-css');

const config = {
  scripts: [
    './views/js/back.js',
    './views/js/custom-card.js',
    './views/js/front.js',
    './views/js/ticket.js',
  ],
  stylesheets: [
    './views/css/front.css',
    './views/css/back.css',
  ],
};

gulp.task('scripts', () => {
  return gulp.src(config.scripts)
    .pipe(uglify())
    .pipe(rename({ extname: '.min.js' }))
    .pipe(gulp.dest('./views/js/'));
});

gulp.task('stylesheets', () => {
  return gulp.src(config.stylesheets)
    .pipe(cleanCSS({ compatibility: 'ie8' }))
    .pipe(rename({ extname: '.min.css' }))
    .pipe(gulp.dest('./views/css/'));
});

gulp.task('minify-assets', gulp.series('scripts', 'stylesheets'));
