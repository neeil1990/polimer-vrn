var gulp        = require('gulp');
var less        = require('gulp-less');
var cleanCSS    = require('gulp-clean-css');
var notify      = require('gulp-notify');
var browserSync = require('browser-sync');
var rename      = require("gulp-rename");
var reload      = browserSync.reload;

var paths = {
  html:['index.html', 'card.html'],
  css:['src/style.less']
};

gulp.task('minify-css', function() {
  return gulp.src('src/style.less')
    .pipe(less())
    .pipe(rename({basename: 'template_styles', suffix: '', extname: '.css'}))
    .pipe(gulp.dest(''))
    .pipe(reload({stream:true}));
});

// ////////////////////////////////////////////////
// HTML 
// ///////////////////////////////////////////////
gulp.task('html', function(){
  gulp.src(paths.html)
  .pipe(reload({stream:true}));
});

// ////////////////////////////////////////////////
// Browser-Sync
// // /////////////////////////////////////////////
gulp.task('browserSync', function() {
  browserSync({
    server: {
      baseDir: "./"
    },
    port: 8080,
    open: true,
    notify: false
  });
});


gulp.task('watcher',function(){
  gulp.watch(paths.css, ['minify-css']);
  gulp.watch(paths.html, ['html']);
});

gulp.task('default', ['watcher', 'browserSync']);