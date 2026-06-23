const gulp = require("gulp");
const sass = require("gulp-sass")(require("sass"));
const cleanCSS = require("gulp-clean-css");
const uglify = require("gulp-uglify");
const rename = require("gulp-rename");
const babel = require("gulp-babel");

const isProduction = process.env.NODE_ENV === "production";

// Frontend + admin styles: src/scss/**/*.scss -> assets/css/*.min.css
// Excludes _*.scss (Sass partials, e.g. _multistep-shared.scss) — those are
// only ever reached via @import from another file, never compiled standalone.
function scssTask() {
  let stream = gulp
    .src(["src/scss/**/*.scss", "!src/scss/**/_*.scss"])
    .pipe(sass().on("error", sass.logError));

  if (isProduction) {
    stream = stream.pipe(cleanCSS());
  }

  return stream.pipe(rename({ suffix: ".min" })).pipe(gulp.dest("assets/css"));
}

// Frontend + admin scripts: src/js/**/*.js -> assets/js/*.min.js
function jsTask() {
  let stream = gulp
    .src("src/js/**/*.js")
    .pipe(babel({ presets: ["@babel/preset-env"] }));

  if (isProduction) {
    stream = stream.pipe(uglify());
  }

  return stream.pipe(rename({ suffix: ".min" })).pipe(gulp.dest("assets/js"));
}

function watchFiles() {
  gulp.watch("src/scss/**/*.scss", scssTask);
  gulp.watch("src/js/**/*.js", jsTask);
}

const build = gulp.series(gulp.parallel(scssTask, jsTask));
const watch = gulp.series(build, watchFiles);

exports.build = build;
exports.watch = watch;
exports.default = watch;
