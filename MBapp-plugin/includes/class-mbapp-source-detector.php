<?php
/**
 * Forrás felderítő.
 *
 * Két feladata van:
 *  1. Diagnosztika – megmondja, mit ad vissza valójában a forrás URL
 *     (státusz, méret, van-e RSS csatorna, JS-ből épül-e az oldal).
 *  2. Szerkezetfelismerés – megkeresi az oldalon az ismétlődő hírblokkokat,
 *     és javaslatot tesz a CSS szelektorokra.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Felderítő.
 */
class MBapp_Source_Detector {

	/**
	 * Beolvasó példány (a HTTP kéréshez).
	 *
	 * @var MBapp_News_Importer
	 */
	private $importer;

	/**
	 * Konstruktor.
	 *
	 * @param array $settings Felülírt beállítások.
	 */
	public function __construct( array $settings = array() ) {
		$this->importer = new MBapp_News_Importer( $settings );
	}

	/**
	 * Gyakori csatorna útvonalak, amiket végigpróbálunk.
	 *
	 * @return array
	 */
	public static function feed_paths() {
		return array(
			'/rss',
			'/feed',
			'/rss.xml',
			'/feed.xml',
			'/atom.xml',
			'/index.xml',
			'?format=rss',
			'/feed/',
			'/hirek/rss',
			'/hirek/feed',
		);
	}

	/**
	 * Teljes elemzés egy URL-re.
	 *
	 * @param string $url Forrás URL.
	 * @return array|WP_Error
	 */
	public function analyze( $url ) {
		$url = trim( (string) $url );

		if ( ! $url ) {
			return new WP_Error( 'mbapp_no_url', __( 'Nincs megadva URL.', 'mbapp' ) );
		}

		$response = $this->importer->fetch( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = $response['body'];

		$report = array(
			'url'          => $url,
			'content_type' => $response['content_type'],
			'size'         => strlen( $body ),
			'is_xml'       => (bool) preg_match( '/^\s*<\?xml|<(rss|feed)\b/i', substr( $body, 0, 600 ) ),
			'feeds'        => array(),
			'jsonld'       => 0,
			'text_length'  => 0,
			'notes'        => array(),
			'candidates'   => array(),
			'html_head'    => '',
		);

		// XML esetén nincs mit felismerni: kész a válasz.
		if ( $report['is_xml'] ) {
			$items                = $this->importer->parse_feed( $body, $url );
			$report['feed_items'] = count( $items );
			$report['notes'][]    = __( 'A megadott cím RSS/Atom csatorna – szelektorokra nincs is szükség, állítsd a forrás típusát „RSS / Atom” értékre.', 'mbapp' );

			return $report;
		}

		// A látható szöveg mennyisége: kevés szöveg = JavaScriptből épülő oldal.
		$text                  = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $body ) ) );
		$report['text_length'] = mb_strlen( $text );

		// Csatornák felderítése.
		$report['feeds'] = $this->discover_feeds( $body, $url );

		// JSON-LD strukturált adat.
		$jsonld            = self::extract_jsonld_items( $body, $url );
		$report['jsonld']  = count( $jsonld );

		// Ismétlődő blokkok keresése.
		$report['candidates'] = $this->suggest_selectors( $body, $url );

		// Az oldal eleje segítségként (megjeleníthető az adminban).
		$report['html_head'] = mb_substr( preg_replace( '/\s+/', ' ', $body ), 0, 1500 );

		/* --- Értelmező megjegyzések --- */

		if ( $report['feeds'] ) {
			$report['notes'][] = sprintf(
				/* translators: %s: csatorna URL */
				__( 'Találtunk RSS csatornát: %s – ezt érdemes forrásnak megadni, mert sokkal megbízhatóbb, mint a HTML olvasás.', 'mbapp' ),
				$report['feeds'][0]
			);
		}

		if ( $report['jsonld'] ) {
			$report['notes'][] = sprintf(
				/* translators: %d: elemek száma */
				__( 'Az oldal %d hírt tartalmaz JSON-LD strukturált adatként. Állítsd a forrás típusát „JSON-LD” értékre – így szelektorok nélkül is működik.', 'mbapp' ),
				$report['jsonld']
			);
		}

		if ( $report['text_length'] < 800 && ! $report['candidates'] ) {
			$report['notes'][] = __( 'Az oldal alig tartalmaz szöveget a letöltött HTML-ben. Ez általában azt jelenti, hogy a hírek JavaScripttel töltődnek be, ezért a szerverről nem olvashatók ki. Ilyenkor RSS csatornát, JSON-LD adatot vagy az oldal saját API címét kell forrásnak megadni.', 'mbapp' );
		}

		if ( $report['candidates'] ) {
			$report['notes'][] = sprintf(
				/* translators: %d: javaslatok száma */
				__( '%d lehetséges hírblokkot találtunk. Válaszd ki a legjobbat, és a szelektorok automatikusan kitöltődnek.', 'mbapp' ),
				count( $report['candidates'] )
			);
		} elseif ( $report['text_length'] >= 800 ) {
			$report['notes'][] = __( 'Nem találtunk egyértelműen ismétlődő hírblokkot. Nézd meg a böngésző fejlesztői eszközével (jobb gomb → Elem vizsgálata), milyen osztálynév ismétlődik a hírek körül, és írd be kézzel a „Hír elem” mezőbe.', 'mbapp' );
		}

		return $report;
	}

	/**
	 * RSS csatornák felderítése: fejléc + gyakori útvonalak.
	 *
	 * @param string $body HTML.
	 * @param string $url  Alap URL.
	 * @return array
	 */
	private function discover_feeds( $body, $url ) {
		$found = array();

		// 1. A HTML fejlécében hirdetett csatornák.
		if ( preg_match_all( '#<link[^>]+type=["\']application/(?:rss|atom)\+xml["\'][^>]*>#i', $body, $matches ) ) {
			foreach ( $matches[0] as $tag ) {
				if ( preg_match( '#href=["\']([^"\']+)["\']#i', $tag, $href ) ) {
					$found[] = mbapp_absolute_url( html_entity_decode( $href[1] ), $url );
				}
			}
		}

		if ( $found ) {
			return array_values( array_unique( $found ) );
		}

		// 2. Gyakori útvonalak kipróbálása (csak felderítéskor, importáláskor nem).
		$parts = wp_parse_url( $url );

		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return array();
		}

		$root = $parts['scheme'] . '://' . $parts['host'];
		$path = isset( $parts['path'] ) ? untrailingslashit( $parts['path'] ) : '';

		$candidates = array();

		foreach ( self::feed_paths() as $suffix ) {
			if ( 0 === strpos( $suffix, '?' ) ) {
				$candidates[] = $root . $path . $suffix;
			} else {
				$candidates[] = $root . $path . $suffix;
				$candidates[] = $root . $suffix;
			}
		}

		$candidates = array_slice( array_values( array_unique( $candidates ) ), 0, 12 );

		foreach ( $candidates as $candidate ) {
			$response = $this->importer->fetch( $candidate );

			if ( is_wp_error( $response ) ) {
				continue;
			}

			if ( ! preg_match( '/^\s*<\?xml|<(rss|feed)\b/i', substr( $response['body'], 0, 600 ) ) ) {
				continue;
			}

			$items = $this->importer->parse_feed( $response['body'], $candidate );

			if ( ! empty( $items ) ) {
				$found[] = $candidate;
				break;
			}
		}

		return $found;
	}

	/**
	 * JSON-LD hírek kiolvasása.
	 *
	 * @param string $body HTML.
	 * @param string $base Alap URL.
	 * @return array
	 */
	public static function extract_jsonld_items( $body, $base ) {
		if ( ! preg_match_all( '#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $body, $matches ) ) {
			return array();
		}

		$items = array();

		foreach ( $matches[1] as $raw ) {
			$data = json_decode( trim( $raw ), true );

			if ( ! is_array( $data ) ) {
				continue;
			}

			self::walk_jsonld( $data, $items, $base );
		}

		return $items;
	}

	/**
	 * JSON-LD fa bejárása cikkekért.
	 *
	 * @param array  $node  Csomópont.
	 * @param array  $items Gyűjtő (referencia).
	 * @param string $base  Alap URL.
	 */
	private static function walk_jsonld( $node, array &$items, $base ) {
		if ( ! is_array( $node ) ) {
			return;
		}

		$types = array();

		if ( isset( $node['@type'] ) ) {
			$types = array_map( 'strtolower', (array) $node['@type'] );
		}

		$article_types = array( 'article', 'newsarticle', 'blogposting', 'report', 'socialmediaposting' );

		if ( array_intersect( $types, $article_types ) ) {
			$title = '';

			foreach ( array( 'headline', 'name' ) as $key ) {
				if ( ! empty( $node[ $key ] ) && is_string( $node[ $key ] ) ) {
					$title = $node[ $key ];
					break;
				}
			}

			$link = '';

			if ( ! empty( $node['url'] ) && is_string( $node['url'] ) ) {
				$link = $node['url'];
			} elseif ( ! empty( $node['mainEntityOfPage'] ) ) {
				$link = is_array( $node['mainEntityOfPage'] )
					? ( isset( $node['mainEntityOfPage']['@id'] ) ? $node['mainEntityOfPage']['@id'] : '' )
					: $node['mainEntityOfPage'];
			}

			$image = '';

			if ( ! empty( $node['image'] ) ) {
				$img = $node['image'];

				if ( is_string( $img ) ) {
					$image = $img;
				} elseif ( is_array( $img ) ) {
					if ( isset( $img['url'] ) ) {
						$image = $img['url'];
					} elseif ( isset( $img[0] ) ) {
						$image = is_string( $img[0] ) ? $img[0] : ( isset( $img[0]['url'] ) ? $img[0]['url'] : '' );
					}
				}
			}

			$description = '';

			foreach ( array( 'description', 'articleBody' ) as $key ) {
				if ( ! empty( $node[ $key ] ) && is_string( $node[ $key ] ) ) {
					$description = $node[ $key ];
					break;
				}
			}

			$date = '';

			foreach ( array( 'datePublished', 'dateCreated', 'dateModified' ) as $key ) {
				if ( ! empty( $node[ $key ] ) && is_string( $node[ $key ] ) ) {
					$date = $node[ $key ];
					break;
				}
			}

			if ( $title ) {
				$items[] = array(
					'title'   => $title,
					'link'    => $link ? mbapp_absolute_url( $link, $base ) : '',
					'image'   => $image ? mbapp_absolute_url( $image, $base ) : '',
					'excerpt' => mbapp_make_excerpt( $description ),
					'content' => ( ! empty( $node['articleBody'] ) && is_string( $node['articleBody'] ) )
						? wpautop( $node['articleBody'] )
						: '',
					'date'    => $date,
					'guid'    => $link ? $link : $title,
				);
			}

			return;
		}

		// Lista vagy gráf: menjünk mélyebbre.
		foreach ( $node as $child ) {
			if ( is_array( $child ) ) {
				self::walk_jsonld( $child, $items, $base );
			}
		}
	}

	/**
	 * Ismétlődő hírblokkok keresése és szelektor javaslat.
	 *
	 * @param string $body HTML.
	 * @param string $base Alap URL.
	 * @return array
	 */
	public function suggest_selectors( $body, $base ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return array();
		}

		$doc      = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$loaded   = $doc->loadHTML( '<?xml encoding="utf-8" ?>' . $body );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			return array();
		}

		$xpath  = new DOMXPath( $doc );
		$groups = array();

		// Minden olyan elemet megnézünk, aminek több elem gyereke van.
		$parents = $xpath->query( '//body//*[count(*) >= 3]' );

		if ( ! $parents ) {
			return array();
		}

		foreach ( $parents as $parent ) {
			if ( ! $parent instanceof DOMElement || $this->in_chrome( $parent ) ) {
				continue;
			}

			$buckets = array();

			foreach ( $parent->childNodes as $child ) {
				if ( ! $child instanceof DOMElement ) {
					continue;
				}

				$signature = $this->signature( $child );

				if ( ! isset( $buckets[ $signature ] ) ) {
					$buckets[ $signature ] = array();
				}

				$buckets[ $signature ][] = $child;
			}

			foreach ( $buckets as $signature => $members ) {
				if ( count( $members ) < 3 ) {
					continue;
				}

				$stat = $this->score_group( $members );

				if ( $stat['links'] < 3 || $stat['avg_text'] < 25 ) {
					continue;
				}

				$selector = $this->selector_for( $members[0] );

				if ( ! $selector ) {
					continue;
				}

				if ( isset( $groups[ $selector ] ) && $groups[ $selector ]['score'] >= $stat['score'] ) {
					continue;
				}

				$groups[ $selector ] = array(
					'selector'  => $selector,
					'count'     => count( $members ),
					'score'     => $stat['score'],
					'avg_text'  => (int) $stat['avg_text'],
					'has_image' => $stat['images'] > 0,
					'sample'    => $stat['sample'],
					'fields'    => $this->field_selectors( $members[0] ),
				);
			}
		}

		usort(
			$groups,
			static function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		return array_slice( array_values( $groups ), 0, 5 );
	}

	/**
	 * Fejlécben / láblécben / navigációban van-e az elem.
	 *
	 * @param DOMElement $node Elem.
	 * @return bool
	 */
	private function in_chrome( DOMElement $node ) {
		$parent = $node;

		while ( $parent && $parent instanceof DOMElement ) {
			$tag = strtolower( $parent->tagName );

			if ( in_array( $tag, array( 'nav', 'header', 'footer' ), true ) ) {
				return true;
			}

			$class = strtolower( $parent->getAttribute( 'class' ) );

			if ( $class && preg_match( '/\b(nav|menu|footer|header|breadcrumb|cookie|sidebar)\b/', $class ) ) {
				return true;
			}

			$parent = $parent->parentNode;
		}

		return false;
	}

	/**
	 * Elem "ujjlenyomata" a csoportosításhoz.
	 *
	 * @param DOMElement $node Elem.
	 * @return string
	 */
	private function signature( DOMElement $node ) {
		$classes = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ), -1, PREG_SPLIT_NO_EMPTY );

		// Az egyedi azonosítót tartalmazó osztályokat (pl. post-1234) kihagyjuk.
		$classes = array_filter(
			$classes,
			static function ( $class ) {
				return ! preg_match( '/\d{2,}/', $class );
			}
		);

		sort( $classes );

		return strtolower( $node->tagName ) . '|' . implode( '.', $classes );
	}

	/**
	 * Egy csoport pontozása.
	 *
	 * @param array $members Elemek.
	 * @return array
	 */
	private function score_group( array $members ) {
		$links  = 0;
		$images = 0;
		$texts  = array();
		$sample = '';

		foreach ( $members as $member ) {
			$anchors = $member->getElementsByTagName( 'a' );
			$has_link = false;

			foreach ( $anchors as $anchor ) {
				if ( trim( $anchor->getAttribute( 'href' ) ) ) {
					$has_link = true;
					break;
				}
			}

			if ( $has_link ) {
				$links++;
			}

			if ( $member->getElementsByTagName( 'img' )->length ) {
				$images++;
			}

			$text    = trim( preg_replace( '/\s+/u', ' ', $member->textContent ) );
			$texts[] = mb_strlen( $text );

			if ( ! $sample && mb_strlen( $text ) > 20 ) {
				$sample = mb_substr( $text, 0, 120 );
			}
		}

		$avg = $texts ? array_sum( $texts ) / count( $texts ) : 0;

		// Pontszám: sok elem, mindegyikben link, tisztes szövegmennyiség, kép bónusz.
		$score = ( $links * 10 ) + min( 40, $avg / 5 ) + ( $images * 3 );

		return array(
			'links'    => $links,
			'images'   => $images,
			'avg_text' => $avg,
			'score'    => $score,
			'sample'   => $sample,
		);
	}

	/**
	 * CSS szelektor egy elemre.
	 *
	 * @param DOMElement $node Elem.
	 * @return string
	 */
	private function selector_for( DOMElement $node ) {
		$tag     = strtolower( $node->tagName );
		$classes = preg_split( '/\s+/', trim( $node->getAttribute( 'class' ) ), -1, PREG_SPLIT_NO_EMPTY );

		$classes = array_values(
			array_filter(
				$classes,
				static function ( $class ) {
					return ! preg_match( '/\d{2,}/', $class ) && preg_match( '/^[A-Za-z][A-Za-z0-9_-]*$/', $class );
				}
			)
		);

		if ( $classes ) {
			return $tag . '.' . implode( '.', array_slice( $classes, 0, 2 ) );
		}

		// Osztály nélkül a szülő osztályára támaszkodunk.
		$parent = $node->parentNode;

		if ( $parent instanceof DOMElement ) {
			$parent_classes = preg_split( '/\s+/', trim( $parent->getAttribute( 'class' ) ), -1, PREG_SPLIT_NO_EMPTY );

			$parent_classes = array_values(
				array_filter(
					$parent_classes,
					static function ( $class ) {
						return ! preg_match( '/\d{2,}/', $class ) && preg_match( '/^[A-Za-z][A-Za-z0-9_-]*$/', $class );
					}
				)
			);

			if ( $parent_classes ) {
				return '.' . $parent_classes[0] . ' > ' . $tag;
			}

			$parent_id = trim( $parent->getAttribute( 'id' ) );

			if ( $parent_id && preg_match( '/^[A-Za-z][A-Za-z0-9_-]*$/', $parent_id ) ) {
				return '#' . $parent_id . ' > ' . $tag;
			}
		}

		return in_array( $tag, array( 'article', 'li' ), true ) ? $tag : '';
	}

	/**
	 * Al-szelektorok javaslata egy mintaelemen belül.
	 *
	 * @param DOMElement $node Elem.
	 * @return array
	 */
	private function field_selectors( DOMElement $node ) {
		$fields = array(
			'title_selector'   => '',
			'link_selector'    => 'a',
			'image_selector'   => '',
			'excerpt_selector' => '',
			'date_selector'    => '',
		);

		// Cím: az első címsor, amiben van szöveg.
		foreach ( array( 'h1', 'h2', 'h3', 'h4' ) as $heading ) {
			$nodes = $node->getElementsByTagName( $heading );

			if ( $nodes->length && trim( $nodes->item( 0 )->textContent ) ) {
				$has_link = $nodes->item( 0 )->getElementsByTagName( 'a' )->length > 0;

				$fields['title_selector'] = $has_link ? $heading . ' a' : $heading;
				$fields['link_selector']  = $has_link ? $heading . ' a' : 'a';
				break;
			}
		}

		if ( ! $fields['title_selector'] ) {
			$fields['title_selector'] = 'a';
		}

		if ( $node->getElementsByTagName( 'img' )->length ) {
			$fields['image_selector'] = 'img';
		}

		if ( $node->getElementsByTagName( 'p' )->length ) {
			$fields['excerpt_selector'] = 'p';
		}

		if ( $node->getElementsByTagName( 'time' )->length ) {
			$fields['date_selector'] = 'time';
		} else {
			// Dátumra utaló osztálynév keresése.
			$xpath = new DOMXPath( $node->ownerDocument );
			$hits  = $xpath->query( './/*[contains(@class,"date") or contains(@class,"datum") or contains(@class,"ido")]', $node );

			if ( $hits && $hits->length ) {
				$class = preg_split( '/\s+/', trim( $hits->item( 0 )->getAttribute( 'class' ) ), -1, PREG_SPLIT_NO_EMPTY );

				if ( ! empty( $class[0] ) && preg_match( '/^[A-Za-z][A-Za-z0-9_-]*$/', $class[0] ) ) {
					$fields['date_selector'] = '.' . $class[0];
				}
			}
		}

		return $fields;
	}
}
