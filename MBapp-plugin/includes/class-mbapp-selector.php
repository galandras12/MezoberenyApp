<?php
/**
 * Egyszerű CSS -> XPath átalakító.
 *
 * A hírbeolvasó admin felületén CSS szelektorokat lehet megadni
 * (pl. `article .entry h2 a`), ezt fordítjuk XPath kifejezésre a
 * DOMXPath számára. A támogatott készlet szándékosan szűk, de
 * a hírlisták kigyűjtéséhez bőven elég.
 *
 * Támogatott: tag, .osztály, #azonosító, [attr], [attr="érték"],
 * [attr*="érték"], [attr^="érték"], leszármazott (szóköz),
 * közvetlen gyermek (>), felsorolás (,).
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Szelektor fordító.
 */
class MBapp_Selector {

	/**
	 * CSS szelektor (vagy nyers XPath) átalakítása XPath kifejezéssé.
	 *
	 * @param string $selector Szelektor.
	 * @return string XPath, vagy üres string ha nem értelmezhető.
	 */
	public static function to_xpath( $selector ) {
		$selector = trim( (string) $selector );

		if ( '' === $selector ) {
			return '';
		}

		// Ha már XPath-nak tűnik, hagyjuk békén.
		if ( 0 === strpos( $selector, '/' ) || 0 === strpos( $selector, '(' ) || 0 === strpos( $selector, './' ) ) {
			return $selector;
		}

		$groups = array_filter( array_map( 'trim', explode( ',', $selector ) ) );
		$paths  = array();

		foreach ( $groups as $group ) {
			$path = self::group_to_xpath( $group );

			if ( $path ) {
				$paths[] = $path;
			}
		}

		return implode( ' | ', $paths );
	}

	/**
	 * Egyetlen szelektor-ág fordítása.
	 *
	 * @param string $group Szelektor ág.
	 * @return string
	 */
	private static function group_to_xpath( $group ) {
		// A gyermek kombinátort külön tokenné tesszük.
		$group  = preg_replace( '/\s*>\s*/', ' > ', $group );
		$tokens = preg_split( '/\s+/', trim( $group ), -1, PREG_SPLIT_NO_EMPTY );

		if ( empty( $tokens ) ) {
			return '';
		}

		$xpath     = '.';
		$combinator = '//';

		foreach ( $tokens as $token ) {
			if ( '>' === $token ) {
				$combinator = '/';
				continue;
			}

			$step = self::simple_to_xpath( $token );

			if ( '' === $step ) {
				return '';
			}

			$xpath     .= $combinator . $step;
			$combinator = '//';
		}

		return $xpath;
	}

	/**
	 * Egyszerű szelektor (tag + osztályok + id + attribútumok) fordítása.
	 *
	 * @param string $token Token.
	 * @return string
	 */
	private static function simple_to_xpath( $token ) {
		$tag        = '*';
		$conditions = array();

		// Attribútum szűrők kiszedése.
		if ( preg_match_all( '/\[([^\]]+)\]/', $token, $matches ) ) {
			foreach ( $matches[1] as $attr_expr ) {
				$condition = self::attribute_condition( $attr_expr );

				if ( $condition ) {
					$conditions[] = $condition;
				}
			}

			$token = preg_replace( '/\[[^\]]+\]/', '', $token );
		}

		// Tag név.
		if ( preg_match( '/^([a-zA-Z][a-zA-Z0-9_-]*)/', $token, $m ) ) {
			$tag   = strtolower( $m[1] );
			$token = substr( $token, strlen( $m[1] ) );
		}

		// Azonosító.
		if ( preg_match_all( '/#([A-Za-z0-9_-]+)/', $token, $ids ) ) {
			foreach ( $ids[1] as $id ) {
				$conditions[] = sprintf( '@id=%s', self::quote( $id ) );
			}
		}

		// Osztályok.
		if ( preg_match_all( '/\.([A-Za-z0-9_-]+)/', $token, $classes ) ) {
			foreach ( $classes[1] as $class ) {
				$conditions[] = sprintf(
					'contains(concat(" ", normalize-space(@class), " "), %s)',
					self::quote( ' ' . $class . ' ' )
				);
			}
		}

		$xpath = $tag;

		if ( $conditions ) {
			$xpath .= '[' . implode( ' and ', $conditions ) . ']';
		}

		return $xpath;
	}

	/**
	 * Attribútum feltétel fordítása.
	 *
	 * @param string $expr Kifejezés, pl. `data-id="4"` vagy `href^="/hir"`.
	 * @return string
	 */
	private static function attribute_condition( $expr ) {
		$expr = trim( $expr );

		if ( ! preg_match( '/^([A-Za-z_:][-A-Za-z0-9_:.]*)\s*(\^=|\$=|\*=|~=|=)?\s*(.*)$/', $expr, $m ) ) {
			return '';
		}

		$attr     = $m[1];
		$operator = isset( $m[2] ) ? $m[2] : '';
		$value    = isset( $m[3] ) ? trim( $m[3], " \t\"'" ) : '';

		if ( '' === $operator ) {
			return sprintf( '@%s', $attr );
		}

		switch ( $operator ) {
			case '^=':
				return sprintf( 'starts-with(@%1$s, %2$s)', $attr, self::quote( $value ) );

			case '$=':
				return sprintf(
					'substring(@%1$s, string-length(@%1$s) - %2$d) = %3$s',
					$attr,
					max( 0, strlen( $value ) - 1 ),
					self::quote( $value )
				);

			case '*=':
			case '~=':
				return sprintf( 'contains(@%1$s, %2$s)', $attr, self::quote( $value ) );

			default:
				return sprintf( '@%1$s=%2$s', $attr, self::quote( $value ) );
		}
	}

	/**
	 * XPath sztring idézőjelezése (aposztrófot is tartalmazó értékekre is).
	 *
	 * @param string $value Érték.
	 * @return string
	 */
	private static function quote( $value ) {
		if ( false === strpos( $value, "'" ) ) {
			return "'" . $value . "'";
		}

		if ( false === strpos( $value, '"' ) ) {
			return '"' . $value . '"';
		}

		$parts  = explode( "'", $value );
		$pieces = array();

		foreach ( $parts as $index => $part ) {
			if ( $index > 0 ) {
				$pieces[] = '"\'"';
			}

			if ( '' !== $part ) {
				$pieces[] = "'" . $part . "'";
			}
		}

		return 'concat(' . implode( ', ', $pieces ) . ')';
	}
}
