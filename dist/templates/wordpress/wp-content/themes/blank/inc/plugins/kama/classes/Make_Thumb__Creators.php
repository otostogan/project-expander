<?php

namespace Kama_Thumbnail;

/**
 * TODO extract to separate class.
 */
trait Make_Thumb__Creators {

	/**
	 * Core: Creates a thumbnail file based on the Imagick library
	 *
	 * @param string $img_string
	 *
	 * @return bool
	 */
	protected function make_thumbnail_Imagick( string $img_string ): bool {

		try {

			$image = new \Imagick();

			$image->readImageBlob( $img_string );

			// Select the first frame to handle animated images properly
			if( is_callable( [ $image, 'setIteratorIndex' ] ) ){
				$image->setIteratorIndex( 0 );
			}

			// set the quality
			$format = $image->getImageFormat();
			if( in_array( $format, [ 'JPEG', 'JPG' ] ) ){
				$image->setImageCompression( \Imagick::COMPRESSION_JPEG );
			}
			if( 'PNG' === $format ){
				$image->setOption( 'png:compression-level', $this->quality );
			}

			$image->setImageCompressionQuality( $this->quality );

			$origin_h = $image->getImageHeight();
			$origin_w = $image->getImageWidth();

			// get the coordinates to read from the original and the size of the new image
			[ $dx, $dy, $wsrc, $hsrc, $width, $height ] = $this->resize_coordinates( $origin_w, $origin_h );

			// crop
			$image->cropImage( $wsrc, $hsrc, $dx, $dy );
			$image->setImagePage( $wsrc, $hsrc, 0, 0 );

			// strip out unneeded meta data
			$image->stripImage();

			// downsize to size
			$image->scaleImage( $width, $height );

			if( $this->force_format ){
				$image->setImageFormat( $this->force_format );
			}

			if( 'webp' === $this->force_format ){

				if( 0 ){
					$image->setBackgroundColor( new \ImagickPixel('transparent') );
					$image->setImageFormat('webp');
					$image->setImageAlphaChannel( \Imagick::ALPHACHANNEL_ACTIVATE );
					$image->writeImage( $this->thumb_path );
				}
				else{
					$image->writeImage( 'webp:' . $this->thumb_path );
				}

				$this->metadata->thumb_format = 'WEBP';
			}
			else {

				$image->writeImage( $this->thumb_path );

				$this->metadata->thumb_format = $image->getImageFormat();
			}

			chmod( $this->thumb_path, kthumb_opt()->CHMOD_FILE );
			$image->clear();
			$image->destroy();

			return true;
		}
		catch( \ImagickException $e ){

			trigger_error( 'ImagickException: '. $e->getMessage() );

			// Let's try to create through GD. Example: https://ps.w.org/wpforms-lite/assets/screenshot-2.gif
			$this->metadata->lib = 'Imagick error. Force GD';

			return $this->make_thumbnail_GD( $img_string );
		}

	}

	/**
	 * Core: Creates a thumbnail file based on the GD library
	 *
	 * @param string $img_string
	 *
	 * @return bool
	 */
	protected function make_thumbnail_GD( string $img_string ): bool {

		$size = $this->image_size_from_string( $img_string );

		// file has no parameters
		if( $size === false ){
			return false;
		}

		// Create a resource
		$image = @ imagecreatefromstring( $img_string );
		/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
		$isok = ( PHP_VERSION_ID >= 80000 ) ? ( $image instanceof \GDImage ) : is_resource( $image );
		if( ! $isok ){
			return false;
		}

		[ $origin_w, $origin_h ] = $size;

		// get the coordinates to read from the original and the size of the new image
		[ $dx, $dy, $wsrc, $hsrc, $width, $height ] = $this->resize_coordinates( $origin_w, $origin_h );

		// Canvas
		$thumb = imagecreatetruecolor( $width, $height );

		if( function_exists( 'imagealphablending' ) && function_exists( 'imagesavealpha' ) ){
			imagealphablending( $thumb, false ); // color and alpha pairing mode
			imagesavealpha( $thumb, true );      // flag that keeps a transparent channel
		}

		// turn on the smoothing function
		if( function_exists( 'imageantialias' ) ){
			imageantialias( $thumb, true );
		}

		// resize
		if( ! imagecopyresampled( $thumb, $image, 0, 0, $dx, $dy, $width, $height, $wsrc, $hsrc ) ){
			return false;
		}

		// save image
		$thumb_format = explode( '/', $size['mime'] )[1];
		if( $this->force_format ){
			$thumb_format = $this->force_format;
		}

		// convert from full colors to index colors, like original PNG.
		if( 'png' === $thumb_format && function_exists( 'imageistruecolor' ) && ! imageistruecolor( $thumb ) ){
			imagetruecolortopalette( $thumb, false, imagecolorstotal( $thumb ) );
		}

		// transparent
		if( 'gif' === $thumb_format ){
			$transparent = imagecolortransparent( $thumb, imagecolorallocate( $thumb, 0, 0, 0 ) );
			$_width = imagesx( $thumb );
			$_height = imagesy( $thumb );
			for( $x = 0; $x < $_width; $x++ ){
				for( $y = 0; $y < $_height; $y++ ){
					$pixel = imagecolorsforindex( $thumb, imagecolorat( $thumb, $x, $y ) );
					if( $pixel['alpha'] >= 64 ){
						imagesetpixel( $thumb, $x, $y, $transparent );
					}
				}
			}
		}

		// jpg / png / webp / gif
		$func_name = function_exists( "image$thumb_format" ) ? "image$thumb_format" : 'imagejpeg';

		$this->metadata->thumb_format = $func_name;

		// AVIF | BMP | GIF | JPG | PNG | WBMP | XPM | WEBP
		switch( $func_name ){
			case 'imagegif':
			case 'imagebmp':
				$func_name( $thumb, $this->thumb_path );
				break;
			case 'imagepng':
				$quality = floor( $this->quality / 10 );
				$func_name( $thumb, $this->thumb_path, $quality );
				break;
			// imageavif, imagejpeg
			default:
				$func_name( $thumb, $this->thumb_path, $this->quality );
		}

		chmod( $this->thumb_path, kthumb_opt()->CHMOD_FILE );
		imagedestroy( $image );
		imagedestroy( $thumb );

		return true;
	}

	/**
	 * Gets the crop coordinates.
	 *
	 * @param int $origin_w  Original width
	 * @param int $origin_h  Original height
	 *
	 * @return array X and Y indent and how many pixels to read in height
	 *               and width from the source: $dx, $dy, $wsrc, $hsrc.
	 */
	protected function resize_coordinates( int $origin_w, int $origin_h ): array {

		// If it is specified not to enlarge the image, and it is smaller than the
		// specified size, we specify the maximum size - this is the size of the image itself.
		// It is important to specify global values, they are used in the IMG width and height attribute and maybe somewhere else.
		if( ! $this->rise_small ){
			( $origin_w < $this->width )  && ( $this->width  = $origin_w );
			( $origin_h < $this->height ) && ( $this->height = $origin_h );
		}

		$crop   = $this->crop;
		$width  = $this->width;
		$height = $this->height;

		// If we don't need to crop and both sides are specified,
		// then find the smaller corresponding side of the image and reset it to zero
		if( ! $crop && ( $width > 0 && $height > 0 ) ){
			( $width/$origin_w < $height/$origin_h )
				? $height = 0
				: $width = 0;
		}

		// If one of the sides is not specified, give it a proportional value
		$width  || (  $width  = round( $origin_w * ( $height / $origin_h ) )  );
		$height || (  $height = round( $origin_h * ( $width / $origin_w ) )  );

		// determine the need to transform the size so that the smallest side fits in
		// if( $width < $origin_w || $height < $origin_h )
		$ratio = max( $width/$origin_w, $height/$origin_h );

		// Determine the cropping position
		$dx = $dy = 0;
		if( is_array( $crop ) ){

			[ $xx, $yy ] = $crop;

			// cut left and right
			if( $height / $origin_h > $width / $origin_w ){

				// отступ слева у источника
				if( $xx === 'center' ){
					$dx = round( ( $origin_w - $width * ( $origin_h / $height ) ) / 2 );
				}
				elseif( $xx === 'left' ){
					$dx = 0;
				}
				// отступ слева у источника
				elseif( $xx === 'right' ){
					$dx = round( ( $origin_w - $width * ( $origin_h / $height ) ) );
				}
			}
			// cut top and bottom
			else{
				if( $yy === 'center' ){
					$dy = round( ( $origin_h - $height * ( $origin_w / $width ) ) / 2 );
				}
				elseif( $yy === 'top' ){
					$dy = 0;
				}
				elseif( $yy === 'bottom' ){
					$dy = round( ( $origin_h - $height * ( $origin_w / $width ) ) );
				}
				// ( $height * $origin_w / $width ) / 2 * 6/10 - отступ сверху у источника
				// *6/10 - чтобы для вертикальных фоток отступ сверху был не половина а процентов 30
			}
		}

		$this->metadata->crop = is_array( $crop ) ? implode( '/', $crop ) : 'none';

		// How many pixels to read from the source
		$wsrc = round( $width/$ratio );
		$hsrc = round( $height/$ratio );

		return array( $dx, $dy, $wsrc, $hsrc, $width, $height );
	}

}