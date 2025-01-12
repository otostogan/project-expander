=== Plugin Name ===
Stable tag:   trunk
Tested up to: 6.1.1

License:      GPLv2 or later
License URI:  http://www.gnu.org/licenses/gpl-2.0.html

Contributors: Tkama
Tags: thumbnail, image

Creates post thumbnails on fly and cache the result. Auto-create of post thumbnails based on: WP post thumbnail OR first img in post content OR attachment. Supports Multisite.


== Description ==

Convenient way to create post thumbnails on the fly without server overload.

The best alternative to scripts like "thumbnail.php".

Supports Multisite.



### Usage ###

The plugin for developers firstly, because it don't do anything after install. In order to the plugin begin to work, you need use one of plugin function in your theme or plugin. Example:

`
<?php echo kama_thumb_img( 'w=150 &h=150' ); ?>
`

Using the code in the loop you will get ready thumbnail IMG tag. Plugin takes post thumbnail image or find first image in post content, resize it and create cache. Also creates custom field for the post with URL to original image. In simple words it cache all routine and in next page loads just take cache result.

You can make thumbs from custom URL, like this:
`<?php echo kama_thumb_img('w=150 &h=150', 'URL_TO_IMG'); ?>`

The `URL_TO_IMG` must be from local server: by default, plugin don't work with external images, because of security. But you can set allowed hosts on settings page: `Settings > Media`.

**All plugin functions:**

`
// return thumb url URL
echo kama_thumb_src( $args, $src );

// return thumb IMG tag
echo kama_thumb_img( $args, $src );

// return thumb IMG tag wraped with <a>. A link of A will leads to original image.
echo kama_thumb_a_img( $args, $src );

// to get image width or height after thumb creation
echo kama_thumb( $optname );
// ex:
echo '<img src="'. kama_thumb_src('w=200') .'" width="'. kama_thumb('width') .'" height="'. kama_thumb('height') .'" alt="" />';
`

Parameters:

* **$args** (array/string)
	Arguments to create thumb. Accepts:

	* **w | width**
		(int) desired width.

	* **h | height**
		(int) desired height.

		if parameters `w` and `h` not set, both of them became 100 - square thumb 100х100 px.

	* **notcrop**
		(isset) if set `crop` parameter become false - `crop=false`.

	* **crop**
		(isset) Control image cropping. By default always `true`.

		To disable cropping set here `false/0/no/none` or set parameter `'notcrop'`. Then image will not be cropped and will be created as small copy of original image by sizes settings of one side: width or height - here plugin select the smallest suitable side. So one side will be as it set in `w` or `h` and another side will be smaller then `w` or `h`.

		**Cropping position**

		Also, you can specify string: `'top'`, `'bottom'`, `'left'`, `'right'` or `'center'` and any other combinations of this strings glued with `/`. Ex: `'right/bottom'`. All this will set cropping area:

		- `'left', 'right'` - horizontal side (w)
		- `'top', 'bottom'` - vertical side (h)
		- `'center'` - for both sides (w and h)

		When only one value is set, the other will be by default. By default: `'center/center'`.

		Examples:

		~~~
		// image will be reduced by height, and width will be cropped.
		// "right" means that right side of image will be shown and left side will be cut.
		kama_thumb_img('w=200 &h=400 &crop=right');

		// image will be redused by width, and height will be cropped.
		// "top" means that the top of the image will be shown and bottom side will be cut.
		kama_thumb_img('w=400 &h=200 &crop=top');

		// you can specify two side position at once, order doesn't matter
		kama_thumb_img('w=400 &h=200 &crop=top/right');
		~~~

		**Reduce image by specified side**

		In order to get not cropped proportionally rediced image by specified side: by width or height. You need specify only width or only height, then other side will be reduced proportional. And no cropping will appear here.

		~~~
		kama_thumb_img('w=200');
		~~~

		So, width of our image will be 200, and height will be as it will...
		Теперь ширина всегда будет 200, а высота какая получится... And the picture will be always full, without cropping.


	* **q | quality**
		(int) jpg compression quality (Default 85. max.100)

	* **stub_url**
		(string) URL to no_photo image.

	* **alt**
		(str) alt attr of img tag.

	* **title**
		(str) title attr of img tag.

	* **class**
		(str) class attr of img tag.

	* **style**
		(str) style attr of img tag.

	* **attr**
		(str) Allow to pass any attributes in IMG tag. String passes in IMG tag as it is, without escaping.

	* **a_class**
		(str) class attr of A tag.

	* **a_style**
		(str) style attr of A tag.

	* **a_attr**
		(str) Allow to pass any attributes in A tag. String passes in A tag as it is, without escaping.

	* **no_stub**
		(isset) don't show picture stub if there is no picture. Return empty string.

	* **yes_stub**
		(isset) show picture stub if global option in option disable stub showing, but we need it...

	* **post_id | post**
		(int|WP_Post) post ID. It needs when use function not from the loop. If pass the parameter plugin will exactly knows which post to process. Parametr 'post' added in ver 2.1.

	* **attach_id**
		(int) ID of wordpress attachment image. Also, you can set this parametr by pass attachment ID to '$src' parament - second parametr of plugin functions: `kama_thumb_img('h=200', 250)` or `kama_thumb_img('h=200 &attach_id=250')`

	* **allow**
		(str) Which hosts are allowed. This option sets globally in plugin setting, but if you need allow hosts only for the function call, specify allow hosts here. Set 'any' to allow to make thumbs from any site (host).


* **$src**
	(string) URL to any image. In this case plugin will not parse URL from post thumbnail/content/attachments.

	If parameters passes as array second argument `$src` can be passed in this array, with key: `src` или `url` или `link` или `img`:

	`
	echo kama_thumb_img( array(
		'src' => 'http://yousite.com/IMAGE_URL.jpg',
		'w' => 150,
		'h' => 100,
	) );
	`



### Notes ###

1. You can pass `$args` as string or array:

	`
	// string
	kama_thumb_img('w=200 &h=100 &alt=IMG NAME &class=aligncenter', 'IMG_URL');

	// array
	kama_thumb_img( array(
		'width'  => 200,
		'height' => 150,
		'class'  => 'alignleft'
		'src'    => ''
	) );
	`

2. You can set only one side: `width` | `height`, then other side became proportional.
3. `src` parameter or second function argument is for cases when you need create thumb from any image not image of WordPress post.
4. For test is there image for post, use this code:

	`
	if( ! kama_thumb_img('w=150&h=150&no_stub') )
		echo 'NO img';
	`


### Examples ###

#### #1 Get Thumb ####

In the loop where you need the thumb 150х100:

`
<?php echo kama_thumb_img('w=150 &h=100 &class=alignleft myimg'); ?>
`
Result:
`
<img src='thumbnail_URL' alt='' class='alignleft myimg' width='150' height='100'>
`

#### #2 Not show stub image ####
`
<?php echo kama_thumb_img('w=150 &h=100 &no_stub'); ?>
`

#### #3 Get just thumb URL ####
`
<?php echo kama_thumb_src('w=100&h=80'); ?>
`
Result: `/wp-content/cache/thumb/ec799941f_100x80.png`

This url you can use like:
`
<img src='<?php echo kama_thumb_src('w=100 &h=80 &q=75'); ?>' alt=''>
`

#### #4 `kama_thumb_a_img()` function ####
`
<?php echo kama_thumb_a_img('w=150 &h=100 &class=alignleft myimg &q=75'); ?>
`
Result:
`
<a href='ORIGINAL_URL'><img src='thumbnail_URL' alt='' class='alignleft myimg' width='150' height='100'></a>
`

#### #5 Thumb of any image URL ####
Pass arguments as array:
`
<?php
	echo kama_thumb_img( array(
		'src' => 'http://yousite.com/IMAGE_URL.jpg',
		'w' => 150,
		'h' => 100,
	) );
?>
`

Pass arguments as string:
`
<?php
	echo kama_thumb_img('w=150 &h=200 ', 'http://yousite.com/IMAGE_URL.jpg');
?>
`
When parameters passes as string and "src" parameter has additional query args ("src=$src &w=200" where $src = http://site.com/img.jpg?foo&foo2=foo3) it might be confuse. That's why "src" parameter must passes as second function argument, when parameters passes as string (not array).


#### #6 Parameter post_id ####

Get thumb of post ID=50:

`
<?php echo kama_thumb_img("w=150 &h=100 &post_id=50"); ?>
`

### I don't need plugin ###
This plugin can be easily used not as a plugin, but as a simple php file.

If you are themes developer, and need all it functionality, but you need to install the plugin as the part of your theme, this short instruction for you:

1. Create folder in your theme, let it be 'thumbmaker' - it is for convenience.
2. Download the plugin and copy the files: `class.Kama_Make_Thumb.php` and `no_photo.jpg` to the folder you just create.
3. Include `class.Kama_Make_Thumb.php` file into theme `functions.php`, like this:
`require 'thumbmaker/class.Kama_Make_Thumb.php';`
4. Bingo! Use functions: `kama_thumb_*()` in your theme code.
5. If necessary, open `class.Kama_Make_Thumb.php` and edit options (at the top of the file): cache folder URL/PATH, custom field name etc.

* Conditions of Use - mention of this plugin in describing of your theme.



== Screenshots ==

1. Setting block on standart "Media" admin page.



== Installation ==

### Instalation via Admin Panel ###
1. Go to `Plugins > Add New > Search Plugins` enter "Kama Thumbnail"
2. Find the plugin in search results and install it.


### Instalation via FTP ###
1. Download the `.zip` archive
2. Open `/wp-content/plugins/` directory
3. Put `kama-thumbnail` folder from archive into opened `plugins` folder
4. Activate the `Kama Thumbnail` in Admin plugins page
5. Go to `Settings > Media` page to customize plugin






== Changelog ==

= 3.5.1 =
- CHG: Options Page Moved from Media page to separate page.
- CHG: Minor improvements.
- IMP: Note if `ini_get( 'max_execution_time' ) === 0`.

= 3.5.0 =
- CHG: !IMPORTANT All core classes moved under `Kama_Thumbnail` namespace. So if you use such classes as `Kama_Thumbnail` or `Kama_Make_Thumb` directly - You need to update your code to use namespase. Example: `Kama_Thumbnail_Helpers::parse_main_dom()` >>> `\Kama_Thumbnail\Helpers::parse_main_dom()` OR `new Kama_Make_Thumb()` >>> `new \Kama_Thumbnail\Make_Thumb()`.
- CHG: CHMOD Options moved: `Kama_Make_Thumb::$CHMOD_DIR` >>> `kthumb_opt()->CHMOD_DIR` and `Kama_Make_Thumb::$CHMOD_FILE` >>> `kthumb_opt()->CHMOD_FILE`
- NEW: `$src` parameter now understand Attachment ID|Attachment Object|WP_Post.
- NEW: `no_photo_url` option now supports attachment ID as a value.
- NEW: Delete cached thumbnails of deleted attachment.
- IMP: Unit test improvements.
- IMP: `src` value moved to `srcset` and `src` now contains the original URL.
- IMP: `decoding="async"` by default for kama_thumb_img().
- IMP: Checks the cache_dir path before deleting all files. This is to avoid accidentally deleting files in another directory. Now cache folder must contain one of substring `cache` or `thumb`.
- BUG: Type hint in get_src_from_text() method.
- BUG: `kama_thumb__img_attrs` hook support `srcset` attribute bugfix.
- BUG: stub need to be created when url with not allowed domain was passed. But the stub was created in the path of the normal image.
- BUG: Bug fix with symbolic links in WP_PLUGIN_DIR|WPMU_PLUGIN_DIR paths.

= 3.4.2 =
- NEW: Option to delete single IMG cache by image/thumb URL or attachment ID.
- NEW: CLI command to delete single IMG cache.
- CHG: IMPORTANT - now requires PHP 7.2 (was 5.6).
- IMP: Refactoring and improvements.
- IMP: Unit tests.

= 3.4.1 =
- BUG: Uninstall.php bugfix.

= 3.4.0 =
- NEW: hook: 'kama_thumbnail__allowed_hosts'.
- FIX: Bugfixes from last refactor.
- IMP: Code improvements.
- IMP: Huge refactor.

= 3.3.8 =
- NEW: hook `kama_thumb__replece_in_content_args`.
- NEW: `wh` parameter - set width & height params at once. Example: `kama_thumb_img( 'wh=200:300' )`.
- FIX: PHP 8.0 support.
- Refactor.

= 3.3.7 =
- FIX: `crop` parameter fixes.
- BUG: `<a>` attributes were passed with prefix `a_`.
- IMP: For convenience, to not specify standard attributes in the `attr` parameter, added support for attributes: `data-src`, `data-srcset` (for tag IMG), `rel`, `target`, `download` (for tag A). Also added attributes `sizes` and ``srcset` for IMG tag, for now you can just point them, they are not handled in any way.
- IMP: Constantly set img $attrs variables to pass them to hooks - it's more convenient.
- IMP: The 3rd parameter for hooks `kama_thumb__img_attrs`, `kama_thumb__a_img_attrs` is an instance of the class.

= 3.3.6 =
- Minor Bugfix.

= 3.3.5 =
- FIX: Bug about class-Kama_Thumb_CLI.php file

= 3.3.3 =
- FIX: Multisite bug fix.
- NEW: WP_CLI initial support.

= 3.3.2 =
- huge bug with path when creating thumbs;
- `set_args()` refactor;
- `yes_stub` parameter become deprecated;
- fix `no_stub` parameter;
- Minor bug fixes.

= 3.3.1 =
* FIX: get_src_from_postmeta() fix notices if GLOBAL $post is not set.

= 3.3 =
* NEW: Parameter `force_format`.
* NEW: Force GD lib if Imagick fails thumb creation.

= 3.2 =
* NEW: Filter `kama_thumb_file_sub_dir`.
* NEW: `loading="lazy"` attribute for kama_thumb_img() kama_thumb_a_img()
* FIX: The same thumb hash (thumb URL) for image URLs which differs only with query parameters.
* CHG: IMPORTANT! `kama_thumb_make_file_name` filter arguments list changed.
* CHG: Refactoring.

= 3.1.1 =
* FIX: Consider that image could contain metadata with HTML '&lt;!DOCTYPE'.

= 3.1 =
* FIX: Bug with adding custom query parameter to original image URL. See `kthumb` in code.

= 3.0 =
* NEW: Option `stop_creation_sec` that allow to stop thumb creation after specified period of time. It protect from PHP fatal error `max execution time` when there are too many images to process.
* FIX: `pre_do_thumbnail_src` filter has not return any value.

= 2.9.20 =
* FIX: `parse_main_dom()` regular expression fix for `ps.w.org` type domain.

= 2.9.19 =
* FIX: `parse_main_dom()` function regular expression fix and improve of the function logic. New hook `kama_thumb_parse_main_dom`.
* FIX: Warning suppression for `wp-config.php` check  - `@ file_exists( dirname( ABSPATH ) . wp-config.php )`

= 2.9.18 =
* NEW: Action `kama_thumb_created` allow process created thumbnail, for example, to compress it
* NEW: Filter `kama_thumb_make_file_name` - allows change created thumbnail filename.

= 2.9.17 =
* FIX: Поддержка PHP 7.4: `$url{0} >>> $url[0]`

= 2.8.16 =
* FIX: Исправлен потенциальный жесткий баг, когда происходит рекурсивный вызов генерации картинки. Баг можно поймать, только когда на сервере установлено бесконечное время выполнения скрипта.

= 2.8.15 =
* CHANGE: Убрал необходимость включать WP_DEBUG, чтобы работала опция debug.
* FIX: Полная обработка ImagickException.

= 2.8.14 =
* NEW: Сообщение что заглушки очищены теперь выводится только админам у которых включен `WP_DEBUG`.

= 2.8.13 =
* NEW: фильтр `pre_do_thumbnail_src`.
* NEW: Обработка SVG - если для создания миниатюры преедана ссылка на SVG файл, эта ссылка просто будет возвращена.

= 2.8.12 =
* NEW: фильтр `kama_thumb_src`.
* NEW: параметр `stub_url`.

= 2.8.11 =
* FIX: Заметка в админке об очистке заглушек теперь показывается только админам.

= 2.8.10 =
* FIX: Баг с нестандартными доменами например: `domain.academy`.

= 2.8.9 =
* ADD: Хук `kama_thumb_default_args`.

= 2.8.8 =
* FIX: Проверка строки при получении картинки. Иногда по URL может вернуться HTML код вместо данных картинки.
* NEW: Авто-определение путей при интеграции плагина в тему или в MU плагины. Теперь для интеграции плагина в тему, плагин можно просто положить в папку темы и подключить в `functions.php` главный файл плагина.
* DEL: Удалил фильтр `kama_thumb_allow_admin_options_page`. Теперь его работа базируется на фильтре `kama_thumb_def_options`, если он используется, то страница опций плагина автоматически отключается и опции указываются через этот фильтр.

= 2.8.7 =
* FIX: Мелкие правки кода.
* NEW: для `kama_thumb_img()` автоматически прописывается `alt` (бурется у вложения), если указан параметр `attach_id`.
* NEW: Фильтр: `kama_thumb_def_options` - позволяет изменить опции по умолчанию (когда плаг ставиться не как плаг)
* NEW: Фильтр: `kama_thumb_allow_admin_options_page` - позволяет отключить страницу опций в админке (когда плаг ставиться не как плаг)
* NEW: Опция: `auto_clear_days` - каждые сколько дней очищать кэш


= 2.8.6 =
* BUG: wrong resizing when `rise_small` option enabled and specified size smaller then the image one is.

= 2.8.5 =
* BUG: on save of any type of post was created empty postmeta 'photo_URL', but it must be cleared only if it was exist before.

= 2.8.4 =
* CHG: create_function() replaced with lambda function, in order to support PHP 7.2+

= 2.8.3 =
* NEW: parameter 'rise_small'. If set to false, when thumbnail will not become bigger if it size (width/height) is smaller than the specified size. Default: true - small thumbnails size increases.

= 2.8.2 =
* IMP: function `parse_main_dom()` now understand localhosts and IP.

= 2.8.1 =
* FIX: incorrect display on options page starting from WP 4.9.0

= 2.8.0 =
* Bugfix: there was error if pass post object (WP_Post) in 'post' parameter...

= 2.7.9 =
* CHG: rename filter start with 'kmt_' to 'kama_thumb_': 'kmt_set_args', 'kmt_is_allow_host', 'kmt_img', 'kmt_a_img' become 'kama_thumb_set_args', 'kama_thumb_is_allow_host', 'kama_thumb_img', 'kama_thumb_a_img'.
* minor fixes

= 2.7.8 =
* ADD: hook 'kama_thumb_inited'.
* FIX: little logic confuse about trigger_error()

= 2.7.7 =
* ADD: support for multisite - in MU plugin has it's own options page in network settings menu. Child sites don't have plugin options - it manages globally from network.
* ADD: now all files creates in sub folders of main cache folder, to avoid folder overflow. SEO redirect from old location to new.
* IMP: minor fixes: 'meta_key' option insurance. 'debug' option works only in WP_DEBUG mode. Code improvements.

= 2.7.6 =
* FIX: if set 'kama_thumb_a_img('w=200", $src )' where $src is empty. Plugin was tried generate image of current post, but in this case better to show 'no_photo' stub.

= 2.7.5 =
* ADD: new parameters for adding attributes to A tag: 'a_class', 'a_style', 'a_attr' - `kama_thumb_a_img('w=200 &a_class=foobar &a_style=display:block; &a_attr=rel="nofollow"')`.

= 2.7.3 - 2.7.4 =
* ADD: 'crop' parameter...
* ADD: 'attach_id' parameter - ID of wordpress attachment image. Also, you can set this parametr by pass attachment ID to 'src' parament or in second parametr of plugin functions: `kama_thumb_img('h=200', 250)` or `kama_thumb_img('h=200 &attach_id=250')`
* FIX: 'class.Kama_Make_Thumb.php' changes and fixes

= 2.7.2 =
* ADD: 'style' parameter for 'kama_thumb_img()' and 'kama_thumb_a_img()' functions.

= 2.7.0 - 2.7.1 =
* ADD: new function `echo kama_thumb('width')` - it allows to get real 'width/height' of image if width/height is empty. Or if we set 'notcrop' parameter and small image side will be resized proportionally and we don't know the size beforehand.
* CHG: default no_photo image changed.

= 2.6.3 =
* CHG: confusing option 'debug' in class.Kama_Make_Thumb now turn on/off on settings page...
* ADD: options sanitization on save for protection reasons...

= 2.6.1 =
* NEW: languages dir removed from plugin

= 2.6.0 =
* ADD: 'yes_stub' parameter for functions 'kama_thumb_*()'. Useful when 'no_stub' set in option, but in special place we need the stub (no photo image)
* ADD: New button on options page to clear all cache and delete metafields at once.
* FIX: bug fix with 'no_stub' option. Right after plugin activation it worked incorrectly.

= 2.5.8 =
* FIX: To support URL with HTML entity input src noe decodet with html_entity_decode() - 'it&#96;s-image.jpg' > 'it`s-image.jpg'

= 2.5.7 =
* FIX: Supports to URL without protocols: //site.com/folder/image.png

= 2.5.6 =
* FIX: removed two underscore '__' from all classes methods. Because it reserved by PHP.

= 2.5.5 =
* ADD: WP HTTP API to get IMG from URL.
* ADD: 'width' & 'height' attributes for 'kama_thumb_img()' function for images with not specified 'width' or 'height' parameter (uses with 'notcrop' attribute)
* BUG: If set 'notcrop' parameter and not set 'height' - PHP dies with fatal error...

= 2.5.4 =
* ADD: thumb img in post content: now consider 'srcset' attribute if it's set

= 2.5.3 =
* FIX: regular about 'mini' class in IMG tag and now you can change 'mini' class

= 2.5.2 =
* FIX: some minor fixes for plugin activation and uninstall

= 2.5.1 =
* ADD: Cyrilic domain support - such URL will wokr 'http://сайт.рф/img.jpg'.
* ADD: 'allow' parameter from single function call fix - not work correctly.

= 2.5 =
* ADD: New filters for Kama_Make_Thumb class: 'kmt_set_args', 'kmt_is_allow_host', 'kmt_img', 'kmt_a_img'
* ADD: Is allow host now checks for only main domain (not subdomain). Ex: now plugin works if you try create thumb of 'http://site.com/img.png' from 'foo.site.com' host
* ADD: New parameter 'allow' - set allowed hosts for only current function call. Ex: kama_thumb_img("w=200 &h=200 &allow=any", 'http://external-domain.com/img.jpg' );

= 2.4.4 =
* IMPROVE: Get file from remote domain not work properly if there were redirects...

= 2.4.3 =
* FIX: mini class for IMG in content. Was output error if IMG inside and not inside A tag.

= 2.4.2 =
* CHANGE: Kama_Make_Thumb class::get_src_and_set_postmeta() become publik. In order to just original get img url of post.
* FIX: search img url in post content not worked with relative url like: "/foo.jpg", and not worked if img extension "jpeg";
* FIX: many times faster `&lt;img class="mini"&gt;` treatment in post content (regular expression fix);

= 2.4.1 =
* FIX: parsing parametrs if it given as string. ex: "h=250 &notcrop &class=aligncenter" notcrop becomes  "notcrop_"

= 2.4 =
* FIX: If place second function parameter $src (img url) - it didn't work correctly, because stupid mistake.
* FIX: when use class 'mini' in post content and IMG already wrapped with A tag, plugin made double A wrap.
* IMPROVE: Now self hosted images firstly parses as absolute path, and if there is error, it parses as URL. This method is much stable in some cases.
* ADD: Place 'any' in alowed hostes string on settings page, and plugin will make thumbs from any domain.

= 2.3 =
* Great Bug: Now if parameters passes as string 'src' parameter better pass as second argument of functions kama_thumb_*("w=200 &h=300", 'http://site.com/image.jpg').

= 2.2 =
* ADD: 'attr' parameter. Allow to pass any attributes in IMG tag. String passes as it is, without escaping.

= 2.1 =
* ADD: aliases for passed parameters: src = url|link|img, post_id = post (can be passed as post object), q = quality, w = width, h = height
* FIX: when parameters passes as string and "src" parameter has aditional query args ("src=$src &w=200" where $src = http://site.com/img.jpg?foo&foo2=foo3) it might be confuse, that's why "src" parameter must passes in the end of string, when parameters passes as string (not array).
* CHG: some code refactoring in class.Kama_Make_Thumb.php file.
* FIX: no_stub worked only for images from posts. When 'src' is setted parameter 'no_stub' had no effect;

= 2.0 =
* ADD: notice message when no image library instaled on server (GD or Imagic)
* ADD: diferent names for real thumb and nophoto thumb. And possibility to clear only nophoto thumbs from cache. All it needed to correctly create IMGs from external URLs (not selfhosted img) - sometimes it can't be loaded external imges properly.

= 1.9.4 =
* FIX: ext detection if img URL have querya rgs like <code>*.jpg?foo</code>

= 1.9.3 =
* CHG: DOCUMENT ROOT detection if allow_url_fopen and CURL disabled on server

= 1.9.2 =
* FIX: trys to get image by abs server path, if none of: CURL || allow_url_fopen=on is set on server

= 1.9.1 =
* FIX: getimagesizefromstring() only work in php 5.4+

= 1.9.0 =
* ADD: Images parses from URL with curl first

= 1.8.0 =
* ADD: Images parses from URL. It FIX some bugs, where plugin couldn't create abs path to img.
* ADD: Allowed hosts settings. Now you can set sites from which tumbs will be created too.

= 1.7.2 =
* CHG: Back to PHP 5.2 support :(

= 1.7.1 =
* CHG: PHP lower then 5.3 now not supported, because it's bad practice...

= 1.7 =
* FIX: refactor - separate one class to two: "WP Plugin" & "Thumb Maker". Now code have better logic!

= 1.6.5 =
* ADD: EN localisation

= 1.6.4 =
* ADD: now cache_folder & no_photo_url detected automatically
* ADD: notcrop parametr

