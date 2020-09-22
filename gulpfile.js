const gulp = require('gulp');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');

const config = {
  scripts: [
    './views/js/back.js',
    './views/js/custom-card.js',
    './views/js/front.js',
    './views/js/ticket.js',
  ],
};

gulp.task('scripts', function() {
  return gulp.src(config.scripts)
    .pipe(uglify())
    .pipe(rename({ extname: '.min.js' }))
    .pipe(gulp.dest('./views/js/'));
});

gulp.task('minify-assets', gulp.series('scripts'));
