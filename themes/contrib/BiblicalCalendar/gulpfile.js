
const { series, parallel, watch } = require('gulp');
var gulp = require('gulp');

var sass = require('gulp-sass')(require('sass'));;
//sass.compiler = require('sass');



function clean(cb) {
  // body omitted
  cb();
  
}

function css(cb) {
  // body omitted
  watch('./src/scss/*.scss', { ignoreInitial: false }, function(cb) {
    // body omitted
    gulp.src('./src/scss/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest('./css/'));
    cb();
  });
}



exports.build = series(clean, parallel(css));
