<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( !class_exists( 'CSBSanitizer' ) ) {
	/**
	 * Cleans saved block attributes before render.php hands them to the frontend.
	 *
	 * Block attributes live in post content, which any user with edit_posts can write,
	 * so they are untrusted input. Slide links keep only safe protocols, slide text keeps
	 * basic inline HTML, enumerated values fall back to their defaults, and every other
	 * string loses the characters that could break out of a CSS rule or the <style> element.
	 *
	 * @since 3.2.2
	 */
	class CSBSanitizer {
		const ALIGNS = [ 'left', 'center', 'right', 'justify' ];
		const EFFECTS = [ 'slide', 'fade', 'cube', 'creative', 'coverflow', 'flip', 'cards' ];
		const LINK_TARGETS = [ '', '_blank' ];
		const SLIDE_TEXT_KEYS = [ 'title', 'description', 'btnText' ];
		const POSITION_PATTERN = '/^(top|center|bottom) (left|center|right)$/';

		/**
		 * Sanitizes the full block attribute array.
		 *
		 * @since 3.2.2
		 * @param mixed $attributes Block attributes as saved in post content, merged with defaults.
		 * @return array Attributes that are safe to JSON-encode into data-attributes.
		 */
		public static function attributes( $attributes ) {
			if ( !is_array( $attributes ) ) {
				return [];
			}

			$hasSlides = array_key_exists( 'slides', $attributes );
			$slides = $hasSlides && is_array( $attributes['slides'] ) ? $attributes['slides'] : [];
			unset( $attributes['slides'] );

			$attributes = self::cssValue( $attributes );
			$attributes = self::backgroundImage( $attributes, 'sliderBG' );

			if ( $hasSlides ) {
				$attributes['slides'] = array_values( array_map( [ __CLASS__, 'slide' ], array_filter( $slides, 'is_array' ) ) );
			}

			$attributes['sliderAlign'] = self::oneOf( $attributes['sliderAlign'] ?? null, self::ALIGNS, 'center' );
			$attributes['effect'] = self::oneOf( $attributes['effect'] ?? null, self::EFFECTS, 'slide' );
			$attributes['linkTarget'] = self::oneOf( $attributes['linkTarget'] ?? null, self::LINK_TARGETS, '_blank' );

			return $attributes;
		}

		/**
		 * Sanitizes one slide: text as inline HTML, the button link as a safe URL, the rest as CSS values.
		 *
		 * @since 3.2.2
		 * @param array $slide One item of the slides attribute.
		 * @return array
		 */
		private static function slide( $slide ) {
			$text = [];
			foreach ( self::SLIDE_TEXT_KEYS as $key ) {
				if ( array_key_exists( $key, $slide ) ) {
					$text[ $key ] = is_string( $slide[ $key ] ) ? wp_kses( $slide[ $key ], self::inlineHTML() ) : '';
					unset( $slide[ $key ] );
				}
			}

			$hasLink = array_key_exists( 'btnLink', $slide );
			$link = $hasLink ? $slide['btnLink'] : '';
			unset( $slide['btnLink'] );

			$slide = array_merge( self::cssValue( $slide ), $text );
			$slide = self::backgroundImage( $slide, 'background' );

			if ( $hasLink ) {
				// esc_url_raw() keeps #anchors, /paths and http(s)/mailto/tel links and returns '' for javascript:, data:, vbscript: and the like.
				$slide['btnLink'] = is_string( $link ) ? esc_url_raw( $link ) : '';
			}

			if ( isset( $slide['position'] ) && ( !is_string( $slide['position'] ) || !preg_match( self::POSITION_PATTERN, $slide['position'] ) ) ) {
				$slide['position'] = 'center center';
			}

			if ( array_key_exists( 'childPositions', $slide ) ) {
				$slide['childPositions'] = is_array( $slide['childPositions'] ) ? array_values( array_map( [ __CLASS__, 'childPosition' ], $slide['childPositions'] ) ) : [];
			}

			return $slide;
		}

		/**
		 * Keeps only numeric top/right/bottom/left offsets of a slide child.
		 *
		 * @since 3.2.2
		 * @param mixed $position
		 * @return array
		 */
		private static function childPosition( $position ) {
			$clean = [];
			if ( is_array( $position ) ) {
				foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
					if ( isset( $position[ $side ] ) && is_numeric( $position[ $side ] ) ) {
						$clean[ $side ] = 0 + $position[ $side ];
					}
				}
			}
			return $clean;
		}

		/**
		 * Runs a background control's image URL through esc_url_raw().
		 *
		 * @since 3.2.2
		 * @param array  $data Array holding the background control value.
		 * @param string $key  Key of the background control value.
		 * @return array
		 */
		private static function backgroundImage( $data, $key ) {
			if ( isset( $data[ $key ]['image']['url'] ) ) {
				$data[ $key ]['image']['url'] = is_string( $data[ $key ]['image']['url'] ) ? esc_url_raw( $data[ $key ]['image']['url'] ) : '';
			}
			return $data;
		}

		/**
		 * Recursively strips control characters and ; { } < > from strings so a value can't
		 * end its CSS declaration, rule or <style> element. Numbers and booleans pass through.
		 *
		 * @since 3.2.2
		 * @param mixed $value
		 * @return mixed
		 */
		private static function cssValue( $value ) {
			if ( is_array( $value ) ) {
				return array_map( [ __CLASS__, 'cssValue' ], $value );
			}
			if ( is_string( $value ) ) {
				return trim( preg_replace( '/[;{}<>]/', '', preg_replace( '/[\x00-\x1F\x7F]+/', ' ', $value ) ) );
			}
			return $value;
		}

		/**
		 * Returns the value when it is one of the allowed strings, otherwise the fallback.
		 *
		 * @since 3.2.2
		 * @param mixed  $value
		 * @param array  $allowed
		 * @param string $fallback
		 * @return string
		 */
		private static function oneOf( $value, $allowed, $fallback ) {
			return is_string( $value ) && in_array( $value, $allowed, true ) ? $value : $fallback;
		}

		/**
		 * Inline HTML allowed in slide title, description and button text.
		 * Matches sanitizeHTML() in bpl-tools/utils/common.js.
		 *
		 * @since 3.2.2
		 * @return array
		 */
		private static function inlineHTML() {
			$attrs = [ 'style' => true, 'class' => true ];
			return [
				'b'			=> $attrs,
				'strong'	=> $attrs,
				'i'			=> $attrs,
				'em'		=> $attrs,
				'span'		=> $attrs,
				'br'		=> [],
				'a'			=> array_merge( $attrs, [ 'href' => true, 'target' => true, 'rel' => true ] ),
			];
		}
	}
}
