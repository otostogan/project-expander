import gulp from "gulp";
import autoprefixer from "gulp-autoprefixer";
import cleanCSS from "gulp-clean-css";
import browserSync from "browser-sync";
import * as sass from "sass";
import gulpSass from "gulp-sass";
import webpackStream from "webpack-stream";
import gulpif from "gulp-if";

const mainSass = gulpSass(sass);

const paths = {
	scss: {
		src: "./src/scss/**/*.scss",
		dest: "./",
	},
	js: {
		src: "./src/js/**/*.js",
		entry: "./src/js/main.js",
		lazyload: "./src/js/lazyload.js",
		dest: "./assets/js",
	},
};

let isProd = false;

gulp.task("toProd", (done) => {
	isProd = true;
	done();
});

gulp.task("styles", () => {
	return gulp
		.src(paths.scss.src, { sourcemaps: !isProd })
		.pipe(mainSass().on("error", mainSass.logError))
		.pipe(
			autoprefixer({
				cascade: false,
				grid: true,
				overrideBrowserslist: ["last 5 versions"],
			})
		)
		.pipe(
			gulpif(
				isProd,
				cleanCSS({
					level: 0,
				})
			)
		)
		.pipe(gulp.dest(paths.scss.dest, { sourcemaps: "." }))
		.pipe(browserSync.stream());
});

gulp.task("scripts", () => {
	return gulp
		.src(paths.js.src)
		.pipe(
			webpackStream({
				mode: isProd ? "production" : "development",
				entry: {
					main: paths.js.entry,
					lazyload: paths.js.lazyload,
				},
				output: {
					filename: "[name].js",
				},
				module: {
					rules: [
						{
							test: /\.m?js$/,
							exclude: /node_modules/,
							use: {
								loader: "babel-loader",
								options: {
									presets: [
										[
											"@babel/preset-env",
											{
												targets: "defaults",
											},
										],
									],
								},
							},
						},
						{
							test: /\.css$/,
							use: ["style-loader", "css-loader"],
						},
					],
				},
				resolve: {
					extensions: [".js", ".mjs", ".json"],
					alias: {
						"gsap/ScrollTrigger": "gsap/dist/ScrollTrigger.js",
					},
				},
				devtool: !isProd ? "source-map" : false,
			})
		)
		.pipe(gulp.dest(paths.js.dest))
		.pipe(browserSync.stream());
});

gulp.task("server", () => {
	gulp.watch("./src/scss/**/*.scss", gulp.series("styles"));
	gulp.watch("./src/js/**/*.js", gulp.series("scripts"));
});

gulp.task("default", gulp.parallel("styles", "scripts", "server"));
gulp.task("build", gulp.series("toProd", "styles", "scripts"));
