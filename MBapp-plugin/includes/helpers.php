<?php
/**
 * Általános segédfüggvények.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Beépített SVG ikonkészlet a lebegő menühöz és a kártyákhoz.
 *
 * @param string $name Ikon neve.
 * @param int    $size Méret.
 * @return string
 */
function mbapp_icon( $name, $size = 22 ) {
	$paths = array(
		'home'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V20h13V9.5"/>',
		'news'     => '<path d="M4 5h13v14H4z"/><path d="M17 9h3v8.5a1.5 1.5 0 0 1-3 0z"/><path d="M7 9h7M7 12.5h7M7 16h4"/>',
		'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
		'map'      => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
		'phone'    => '<path d="M6.5 3.5h3l1.6 4-2 1.5a12 12 0 0 0 5.9 5.9l1.5-2 4 1.6v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4.5 5.7a2 2 0 0 1 2-2.2z"/>',
		'user'     => '<circle cx="12" cy="8.2" r="3.8"/><path d="M4.8 20.2a7.2 7.2 0 0 1 14.4 0"/>',
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>',
		'star'     => '<path d="m12 3.8 2.6 5.3 5.8.8-4.2 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L3.6 9.9l5.8-.8z"/>',
		'clock'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.8"/>',
		'pin'      => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
		'ticket'   => '<path d="M4 8.5A1.5 1.5 0 0 1 5.5 7h13A1.5 1.5 0 0 1 20 8.5v2a2 2 0 0 0 0 4v2a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 16.5v-2a2 2 0 0 0 0-4z"/>',
		'external' => '<path d="M14 4h6v6"/><path d="M20 4l-8.5 8.5"/><path d="M18 14v5.5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 19.5v-11A1.5 1.5 0 0 1 5.5 7H11"/>',
		'info'     => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 7.8v.2"/>',
		'plus'     => '<path d="M12 5v14M5 12h14"/>',
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'heart'    => '<path d="M12 20s-7.2-4.5-7.2-9.4A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.2 3c0 4.9-7.2 9.4-7.2 9.4z"/>',
		'bell'     => '<path d="M6.5 10a5.5 5.5 0 1 1 11 0c0 4 1.5 5.5 1.5 5.5H5S6.5 14 6.5 10z"/><path d="M10 18.5a2 2 0 0 0 4 0"/>',
		'grid'     => '<rect x="4" y="4" width="7" height="7" rx="1.6"/><rect x="13" y="4" width="7" height="7" rx="1.6"/><rect x="4" y="13" width="7" height="7" rx="1.6"/><rect x="13" y="13" width="7" height="7" rx="1.6"/>',
		'gallery'  => '<rect x="3.5" y="5" width="17" height="14" rx="2.5"/><path d="m6 16 4-4 3 3 2-2 3 3"/><circle cx="9" cy="9.5" r="1.3"/>',
		'doc'      => '<path d="M6 3.5h7l5 5V20a.5.5 0 0 1-.5.5h-11A.5.5 0 0 1 6 20z"/><path d="M13 3.5V9h5"/>',
	);

	$key = isset( $paths[ $name ] ) ? $name : 'info';

	return sprintf(
		'<svg class="mbapp-icon mbapp-icon--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $key ),
		(int) $size,
		$paths[ $key ]
	);
}

/**
 * Az elérhető beépített ikonok listája (admin választóhoz).
 *
 * @return array
 */
function mbapp_icon_choices() {
	return array(
		'home'     => __( 'Kezdőlap', 'mbapp' ),
		'news'     => __( 'Hírek', 'mbapp' ),
		'calendar' => __( 'Naptár / Események', 'mbapp' ),
		'map'      => __( 'Térkép', 'mbapp' ),
		'phone'    => __( 'Telefon', 'mbapp' ),
		'user'     => __( 'Profil', 'mbapp' ),
		'search'   => __( 'Keresés', 'mbapp' ),
		'star'     => __( 'Csillag', 'mbapp' ),
		'clock'    => __( 'Óra', 'mbapp' ),
		'pin'      => __( 'Helyszín', 'mbapp' ),
		'ticket'   => __( 'Jegy', 'mbapp' ),
		'external' => __( 'Külső link', 'mbapp' ),
		'info'     => __( 'Információ', 'mbapp' ),
		'plus'     => __( 'Plusz', 'mbapp' ),
		'heart'    => __( 'Szív', 'mbapp' ),
		'bell'     => __( 'Értesítés', 'mbapp' ),
		'grid'     => __( 'Rács', 'mbapp' ),
		'gallery'  => __( 'Galéria', 'mbapp' ),
		'doc'      => __( 'Dokumentum', 'mbapp' ),
	);
}

/**
 * Forrás gomb HTML-je.
 *
 * A kérésnek megfelelően minden hír és esemény végére bekerül.
 *
 * @param string $url   Forrás URL.
 * @param string $label Gomb felirata (üres esetén a beállításból).
 * @param string $group Melyik beállításcsoportból vegye a feliratot ('news' vagy 'events').
 * @return string
 */
function mbapp_source_button( $url, $label = '', $group = 'news' ) {
	$url = trim( (string) $url );

	if ( ! $url ) {
		return '';
	}

	$url = esc_url_raw( $url );

	if ( ! $url ) {
		return '';
	}

	if ( ! $label ) {
		$label = MBapp_Settings::get(
			$group,
			'source_label',
			__( 'Pontos részleteket a Mezobereny.hu oldalon olvashatod', 'mbapp' )
		);
	}

	return sprintf(
		'<p class="mbapp-source"><a class="mbapp-source__btn" href="%1$s" target="_blank" rel="noopener noreferrer nofollow">%2$s<span class="mbapp-source__icon">%3$s</span></a></p>',
		esc_url( $url ),
		esc_html( $label ),
		mbapp_icon( 'external', 16 )
	);
}

/**
 * A WordPress aktuális ideje másodpercben (helyi időzóna szerinti UTC bélyeg).
 *
 * @return int
 */
function mbapp_now() {
	return (int) current_time( 'timestamp', true ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
}

/**
 * Helyi dátumszöveg átalakítása UTC időbélyeggé.
 *
 * @param string $datetime 'Y-m-d H:i:s' formátumú helyi idő.
 * @return int
 */
function mbapp_local_to_timestamp( $datetime ) {
	$datetime = trim( (string) $datetime );

	if ( ! $datetime ) {
		return 0;
	}

	// A 'datetime-local' mező T-vel választja el a dátumot és az időt.
	$datetime = str_replace( 'T', ' ', $datetime );

	if ( 16 === strlen( $datetime ) ) {
		$datetime .= ':00';
	}

	try {
		$tz   = wp_timezone();
		$date = new DateTimeImmutable( $datetime, $tz );

		return $date->getTimestamp();
	} catch ( Exception $e ) {
		return 0;
	}
}

/**
 * UTC időbélyeg formázása a helyi időzónában.
 *
 * @param int    $timestamp Időbélyeg.
 * @param string $format    Formátum.
 * @return string
 */
function mbapp_format_timestamp( $timestamp, $format = '' ) {
	$timestamp = (int) $timestamp;

	if ( ! $timestamp ) {
		return '';
	}

	if ( ! $format ) {
		$format = MBapp_Settings::get( 'events', 'date_format', 'Y. F j. H:i' );
	}

	return wp_date( $format, $timestamp );
}

/**
 * Hány nap van hátra egy időpontig (naptári napokban, helyi idő szerint).
 *
 * @param int $timestamp Cél időbélyeg.
 * @return int Napok száma (negatív, ha már elmúlt).
 */
function mbapp_days_until( $timestamp ) {
	$timestamp = (int) $timestamp;

	if ( ! $timestamp ) {
		return 0;
	}

	$tz = wp_timezone();

	try {
		$today  = new DateTimeImmutable( 'now', $tz );
		$target = ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $tz );

		$today  = $today->setTime( 0, 0, 0 );
		$target = $target->setTime( 0, 0, 0 );

		$diff = $today->diff( $target );

		return (int) $diff->days * ( $diff->invert ? -1 : 1 );
	} catch ( Exception $e ) {
		return 0;
	}
}

/**
 * Visszaszámláló felirat.
 *
 * @param int $timestamp Cél időbélyeg.
 * @return string
 */
function mbapp_countdown_label( $timestamp ) {
	$days = mbapp_days_until( $timestamp );

	if ( $days < 0 ) {
		return sprintf(
			/* translators: %d: napok száma */
			_n( '%d napja volt', '%d napja volt', abs( $days ), 'mbapp' ),
			abs( $days )
		);
	}

	if ( 0 === $days ) {
		return __( 'Ma!', 'mbapp' );
	}

	if ( 1 === $days ) {
		return __( 'Holnap', 'mbapp' );
	}

	return sprintf(
		/* translators: %d: napok száma */
		_n( '%d nap múlva', '%d nap múlva', $days, 'mbapp' ),
		$days
	);
}

/**
 * Relatív URL abszolúttá alakítása egy alap URL alapján.
 *
 * @param string $url  Feldolgozandó URL.
 * @param string $base Alap URL.
 * @return string
 */
function mbapp_absolute_url( $url, $base ) {
	$url = trim( (string) $url );

	if ( '' === $url ) {
		return '';
	}

	if ( preg_match( '#^(https?:)?//#i', $url ) ) {
		return 0 === strpos( $url, '//' ) ? 'https:' . $url : $url;
	}

	if ( 0 === strpos( $url, 'data:' ) || 0 === strpos( $url, 'mailto:' ) || 0 === strpos( $url, 'tel:' ) ) {
		return $url;
	}

	$parts = wp_parse_url( $base );

	if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
		return $url;
	}

	$root = $parts['scheme'] . '://' . $parts['host'];

	if ( ! empty( $parts['port'] ) ) {
		$root .= ':' . $parts['port'];
	}

	if ( 0 === strpos( $url, '/' ) ) {
		return $root . $url;
	}

	if ( 0 === strpos( $url, '#' ) || 0 === strpos( $url, '?' ) ) {
		return rtrim( $base, '#?' ) . $url;
	}

	$path = isset( $parts['path'] ) ? $parts['path'] : '/';
	$path = preg_replace( '#/[^/]*$#', '/', $path );

	return $root . $path . $url;
}

/**
 * Rövid, olvasható kivonat készítése HTML-ből.
 *
 * @param string $html  HTML.
 * @param int    $words Szavak száma.
 * @return string
 */
function mbapp_make_excerpt( $html, $words = 28 ) {
	$text = wp_strip_all_tags( (string) $html, true );
	$text = preg_replace( '/\s+/u', ' ', $text );

	return wp_trim_words( trim( $text ), $words, '…' );
}
