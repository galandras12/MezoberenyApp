<?php
/**
 * Automatikus hírbeolvasó.
 *
 * A megadott forrásoldalról (RSS vagy HTML) beolvassa a híreket:
 * címet, szöveget, képet és dátumot, majd létrehozza belőlük a
 * "Hírek" bejegyzéseket – a tartalom végére illesztett forrás gombbal.
 *
 * @package MBapp_Plugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hírbeolvasó.
 */
class MBapp_News_Importer {

	/**
	 * Az aktuális futás azonosítója.
	 *
	 * @var string
	 */
	private $run_id = '';

	/**
	 * Beállítások.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Konstruktor.
	 *
	 * @param array $settings Felülírt beállítások (teszteléshez).
	 */
	public function __construct( array $settings = array() ) {
		$this->settings = array_merge( MBapp_Settings::all( 'news' ), $settings );
	}

	/**
	 * Import futtatása.
	 *
	 * @param bool $manual Kézi indítás-e.
	 * @return array Összegzés.
	 */
	public function run( $manual = false ) {
		$this->run_id = substr( md5( uniqid( 'mbapp', true ) ), 0, 12 );

		$summary = array(
			'run_id'    => $this->run_id,
			'imported'  => 0,
			'duplicate' => 0,
			'skipped'   => 0,
			'errors'    => 0,
			'found'     => 0,
			'message'   => '',
		);

		$source = trim( (string) $this->settings['source_url'] );

		if ( ! $source ) {
			$summary['errors']++;
			$summary['message'] = __( 'Nincs megadva forrás URL.', 'mbapp' );
			$this->log( 'error', $summary['message'] );

			return $summary;
		}

		$this->log(
			'info',
			sprintf(
				/* translators: 1: forrás URL, 2: indítás módja */
				__( 'Import indul. Forrás: %1$s (%2$s)', 'mbapp' ),
				$source,
				$manual ? __( 'kézi', 'mbapp' ) : __( 'ütemezett', 'mbapp' )
			),
			array( 'source_url' => $source )
		);

		$items = $this->collect_items( $source );

		if ( is_wp_error( $items ) ) {
			$summary['errors']++;
			$summary['message'] = $items->get_error_message();
			$this->log( 'error', $items->get_error_message(), array( 'source_url' => $source ) );

			update_option( 'mbapp_news_last_run', array(
				'time'    => mbapp_now(),
				'summary' => $summary,
			) );

			return $summary;
		}

		$summary['found'] = count( $items );
		$max              = max( 1, (int) $this->settings['max_items'] );
		$items            = array_slice( $items, 0, $max );

		foreach ( $items as $item ) {
			$result = $this->import_item( $item );

			if ( isset( $summary[ $result ] ) ) {
				$summary[ $result ]++;
			} elseif ( 'error' === $result ) {
				$summary['errors']++;
			}
		}

		$summary['message'] = sprintf(
			/* translators: 1: talált elemek, 2: importált, 3: már meglévő, 4: kihagyott, 5: hibás */
			__( 'Kész. Talált: %1$d, új: %2$d, már megvolt: %3$d, kihagyva: %4$d, hiba: %5$d.', 'mbapp' ),
			$summary['found'],
			$summary['imported'],
			$summary['duplicate'],
			$summary['skipped'],
			$summary['errors']
		);

		$this->log( 'info', $summary['message'], array( 'source_url' => $source ) );

		update_option(
			'mbapp_news_last_run',
			array(
				'time'    => mbapp_now(),
				'summary' => $summary,
			)
		);

		// Régi naplósorok takarítása.
		MBapp_Logger::prune( (int) $this->settings['log_retention_days'] );

		return $summary;
	}

	/**
	 * Elemek összegyűjtése a forrásból.
	 *
	 * @param string $source Forrás URL.
	 * @return array|WP_Error
	 */
	public function collect_items( $source ) {
		$response = $this->fetch( $source );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = $response['body'];
		$type = $this->settings['source_type'];

		if ( 'auto' === $type ) {
			$type = $this->detect_type( $body, $response['content_type'] );

			// HTML esetén megnézzük, hirdet-e RSS csatornát.
			if ( 'html' === $type ) {
				$feed_url = $this->discover_feed( $body, $source );

				if ( $feed_url ) {
					$feed_response = $this->fetch( $feed_url );

					if ( ! is_wp_error( $feed_response ) ) {
						$items = $this->parse_feed( $feed_response['body'], $feed_url );

						if ( ! empty( $items ) ) {
							$this->log(
								'info',
								sprintf(
									/* translators: %s: csatorna URL */
									__( 'RSS csatorna használata: %s', 'mbapp' ),
									$feed_url
								),
								array( 'source_url' => $feed_url )
							);

							return $items;
						}
					}
				}
			}
		}

		if ( 'rss' === $type ) {
			return $this->parse_feed( $body, $source );
		}

		return $this->parse_html( $body, $source );
	}

	/**
	 * HTTP kérés.
	 *
	 * @param string $url URL.
	 * @return array|WP_Error
	 */
	public function fetch( $url ) {
		$args = array(
			'timeout'     => max( 5, (int) $this->settings['request_timeout'] ),
			'redirection' => 5,
			'sslverify'   => true,
			'headers'     => array(
				'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'Accept-Language' => 'hu-HU,hu;q=0.9,en;q=0.6',
			),
		);

		if ( ! empty( $this->settings['user_agent'] ) ) {
			$args['user-agent'] = $this->settings['user_agent'];
		} else {
			$args['user-agent'] = sprintf(
				'Mozilla/5.0 (compatible; MBapp/%s; +%s)',
				MBAPP_PLUGIN_VERSION,
				home_url( '/' )
			);
		}

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'mbapp_http_error',
				sprintf(
					/* translators: 1: HTTP státuszkód, 2: URL */
					__( 'A forrás %1$d státuszkóddal válaszolt (%2$s).', 'mbapp' ),
					$code,
					$url
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );

		if ( '' === trim( (string) $body ) ) {
			return new WP_Error( 'mbapp_empty_body', __( 'A forrás üres választ adott.', 'mbapp' ) );
		}

		return array(
			'body'         => $body,
			'content_type' => (string) wp_remote_retrieve_header( $response, 'content-type' ),
		);
	}

	/**
	 * Forrástípus felismerése.
	 *
	 * @param string $body         Törzs.
	 * @param string $content_type Tartalomtípus fejléc.
	 * @return string 'rss' vagy 'html'
	 */
	private function detect_type( $body, $content_type ) {
		if ( preg_match( '#(rss|atom)\+xml|text/xml|application/xml#i', (string) $content_type ) ) {
			return 'rss';
		}

		$head = ltrim( substr( $body, 0, 600 ) );

		if ( preg_match( '/^<\?xml/i', $head ) || preg_match( '/<(rss|feed)\b/i', $head ) ) {
			return 'rss';
		}

		return 'html';
	}

	/**
	 * RSS csatorna felderítése a HTML fejlécből.
	 *
	 * @param string $body HTML.
	 * @param string $base Alap URL.
	 * @return string
	 */
	private function discover_feed( $body, $base ) {
		if ( preg_match(
			'#<link[^>]+type=["\']application/(?:rss|atom)\+xml["\'][^>]*>#i',
			$body,
			$match
		) && preg_match( '#href=["\']([^"\']+)["\']#i', $match[0], $href ) ) {
			return mbapp_absolute_url( html_entity_decode( $href[1] ), $base );
		}

		return '';
	}

	/**
	 * RSS / Atom feldolgozása.
	 *
	 * @param string $body XML.
	 * @param string $base Alap URL.
	 * @return array
	 */
	public function parse_feed( $body, $base ) {
		$previous = libxml_use_internal_errors( true );
		$xml      = simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( false === $xml ) {
			return array();
		}

		$entries = array();

		if ( isset( $xml->channel->item ) ) {
			$entries = $xml->channel->item;      // RSS 2.0.
		} elseif ( isset( $xml->item ) ) {
			$entries = $xml->item;               // RSS 1.0.
		} elseif ( isset( $xml->entry ) ) {
			$entries = $xml->entry;              // Atom.
		}

		$items = array();

		foreach ( $entries as $entry ) {
			$namespaces = $entry->getNamespaces( true );

			$title = trim( (string) $entry->title );
			$link  = trim( (string) $entry->link );

			// Atom: <link href="...">.
			if ( ! $link && isset( $entry->link['href'] ) ) {
				$link = trim( (string) $entry->link['href'] );
			}

			$link = mbapp_absolute_url( $link, $base );

			$content = '';

			if ( isset( $namespaces['content'] ) ) {
				$content_ns = $entry->children( $namespaces['content'] );

				if ( isset( $content_ns->encoded ) ) {
					$content = (string) $content_ns->encoded;
				}
			}

			if ( ! $content && isset( $entry->content ) ) {
				$content = (string) $entry->content;
			}

			$excerpt = (string) ( isset( $entry->description ) ? $entry->description : ( isset( $entry->summary ) ? $entry->summary : '' ) );

			if ( ! $content ) {
				$content = $excerpt;
			}

			// Dátum.
			$date_raw = '';

			foreach ( array( 'pubDate', 'published', 'updated' ) as $field ) {
				if ( isset( $entry->{$field} ) && (string) $entry->{$field} ) {
					$date_raw = (string) $entry->{$field};
					break;
				}
			}

			if ( ! $date_raw && isset( $namespaces['dc'] ) ) {
				$dc = $entry->children( $namespaces['dc'] );

				if ( isset( $dc->date ) ) {
					$date_raw = (string) $dc->date;
				}
			}

			// Kép.
			$image = '';

			if ( isset( $entry->enclosure['url'] ) && preg_match( '/image/i', (string) $entry->enclosure['type'] ) ) {
				$image = (string) $entry->enclosure['url'];
			}

			if ( ! $image && isset( $namespaces['media'] ) ) {
				$media = $entry->children( $namespaces['media'] );

				if ( isset( $media->content['url'] ) ) {
					$image = (string) $media->content['url'];
				} elseif ( isset( $media->thumbnail['url'] ) ) {
					$image = (string) $media->thumbnail['url'];
				}
			}

			if ( ! $image && $content && preg_match( '#<img[^>]+src=["\']([^"\']+)["\']#i', $content, $img ) ) {
				$image = html_entity_decode( $img[1] );
			}

			$guid = isset( $entry->guid ) ? trim( (string) $entry->guid ) : '';

			if ( ! $guid && isset( $entry->id ) ) {
				$guid = trim( (string) $entry->id );
			}

			if ( ! $title && ! $link ) {
				continue;
			}

			$items[] = array(
				'title'   => $title,
				'link'    => $link,
				'image'   => $image ? mbapp_absolute_url( $image, $base ) : '',
				'excerpt' => mbapp_make_excerpt( $excerpt ),
				'content' => $content,
				'date'    => $this->parse_date( $date_raw ),
				'guid'    => $guid ? $guid : $link,
			);
		}

		return $items;
	}

	/**
	 * HTML lista feldolgozása.
	 *
	 * @param string $body HTML.
	 * @param string $base Alap URL.
	 * @return array
	 */
	public function parse_html( $body, $base ) {
		$xpath = $this->make_xpath( $body );

		if ( ! $xpath ) {
			return array();
		}

		$item_xpath = MBapp_Selector::to_xpath( $this->settings['item_selector'] );

		if ( ! $item_xpath ) {
			$item_xpath = './/article';
		}

		$nodes = @$xpath->query( $item_xpath ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

		if ( false === $nodes || 0 === $nodes->length ) {
			return array();
		}

		$items = array();
		$seen  = array();

		foreach ( $nodes as $node ) {
			$item = $this->extract_item( $xpath, $node, $base );

			if ( ! $item ) {
				continue;
			}

			$key = $item['link'] ? $item['link'] : $item['title'];

			if ( isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$items[]      = $item;
		}

		return $items;
	}

	/**
	 * Egy hírelem kiolvasása egy csomópontból.
	 *
	 * @param DOMXPath $xpath XPath.
	 * @param DOMNode  $node  Csomópont.
	 * @param string   $base  Alap URL.
	 * @return array|null
	 */
	private function extract_item( DOMXPath $xpath, DOMNode $node, $base ) {
		$title = $this->query_text( $xpath, $node, $this->settings['title_selector'] );
		$link  = $this->query_attr( $xpath, $node, $this->settings['link_selector'], 'href' );
		$image = $this->query_image( $xpath, $node, $this->settings['image_selector'] );

		if ( ! $title ) {
			// Ha nincs cím szelektor találat, próbáljuk a link szövegét.
			$title = $this->query_text( $xpath, $node, 'a' );
		}

		if ( ! $title || mb_strlen( $title ) < 3 ) {
			return null;
		}

		$excerpt  = $this->query_text( $xpath, $node, $this->settings['excerpt_selector'] );
		$date_raw = $this->query_attr( $xpath, $node, $this->settings['date_selector'], 'datetime' );

		if ( ! $date_raw ) {
			$date_raw = $this->query_text( $xpath, $node, $this->settings['date_selector'] );
		}

		$link = $link ? mbapp_absolute_url( html_entity_decode( $link ), $base ) : '';

		return array(
			'title'   => $title,
			'link'    => $link,
			'image'   => $image ? mbapp_absolute_url( $image, $base ) : '',
			'excerpt' => mbapp_make_excerpt( $excerpt ),
			'content' => '',
			'date'    => $this->parse_date( $date_raw ),
			'guid'    => $link ? $link : md5( $title ),
		);
	}

	/**
	 * DOMXPath példány készítése HTML-ből.
	 *
	 * @param string $body HTML.
	 * @return DOMXPath|null
	 */
	private function make_xpath( $body ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return null;
		}

		$doc      = new DOMDocument();
		$previous = libxml_use_internal_errors( true );

		// A karakterkódolás megőrzése.
		$loaded = $doc->loadHTML(
			'<?xml encoding="utf-8" ?>' . $body,
			defined( 'LIBXML_NONET' ) ? LIBXML_NONET : 0
		);

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			return null;
		}

		return new DOMXPath( $doc );
	}

	/**
	 * Szöveg kiolvasása szelektorral.
	 *
	 * @param DOMXPath $xpath    XPath.
	 * @param DOMNode  $context  Kontextus.
	 * @param string   $selector Szelektor.
	 * @return string
	 */
	private function query_text( DOMXPath $xpath, $context, $selector ) {
		$node = $this->query_first( $xpath, $context, $selector );

		if ( ! $node ) {
			return '';
		}

		$text = trim( preg_replace( '/\s+/u', ' ', $node->textContent ) );

		return $text;
	}

	/**
	 * Attribútum kiolvasása szelektorral.
	 *
	 * @param DOMXPath $xpath    XPath.
	 * @param DOMNode  $context  Kontextus.
	 * @param string   $selector Szelektor.
	 * @param string   $attr     Attribútum.
	 * @return string
	 */
	private function query_attr( DOMXPath $xpath, $context, $selector, $attr ) {
		$node = $this->query_first( $xpath, $context, $selector );

		if ( ! $node || ! $node instanceof DOMElement ) {
			return '';
		}

		return trim( $node->getAttribute( $attr ) );
	}

	/**
	 * Kép URL kiolvasása (lusta betöltésű attribútumokat is figyelve).
	 *
	 * @param DOMXPath $xpath    XPath.
	 * @param DOMNode  $context  Kontextus.
	 * @param string   $selector Szelektor.
	 * @return string
	 */
	private function query_image( DOMXPath $xpath, $context, $selector ) {
		$node = $this->query_first( $xpath, $context, $selector );

		if ( ! $node || ! $node instanceof DOMElement ) {
			// Háttérképes megoldás: style="background-image:url(...)".
			$node = $this->query_first( $xpath, $context, '*[style]' );

			if ( $node instanceof DOMElement && preg_match( '#url\(["\']?([^"\')]+)#i', $node->getAttribute( 'style' ), $m ) ) {
				return html_entity_decode( trim( $m[1] ) );
			}

			return '';
		}

		foreach ( array( 'src', 'data-src', 'data-lazy-src', 'data-original' ) as $attr ) {
			$value = trim( $node->getAttribute( $attr ) );

			if ( $value && 0 !== strpos( $value, 'data:image' ) ) {
				return html_entity_decode( $value );
			}
		}

		$srcset = trim( $node->getAttribute( 'srcset' ) );

		if ( $srcset ) {
			$first = explode( ',', $srcset );
			$parts = preg_split( '/\s+/', trim( $first[0] ) );

			if ( ! empty( $parts[0] ) ) {
				return html_entity_decode( $parts[0] );
			}
		}

		return '';
	}

	/**
	 * Első találat lekérése.
	 *
	 * @param DOMXPath     $xpath    XPath.
	 * @param DOMNode|null $context  Kontextus (null esetén a teljes dokumentum).
	 * @param string       $selector Szelektor.
	 * @return DOMNode|null
	 */
	private function query_first( DOMXPath $xpath, $context, $selector ) {
		$expression = MBapp_Selector::to_xpath( $selector );

		if ( ! $expression ) {
			return null;
		}

		$nodes = $context instanceof DOMNode
			? @$xpath->query( $expression, $context )  // phpcs:ignore WordPress.PHP.NoSilencedErrors
			: @$xpath->query( $expression );           // phpcs:ignore WordPress.PHP.NoSilencedErrors

		if ( false === $nodes || 0 === $nodes->length ) {
			return null;
		}

		return $nodes->item( 0 );
	}

	/**
	 * Dátumszöveg átalakítása időbélyeggé.
	 *
	 * Kezeli az ISO formátumot és a magyar dátumokat is (2026. szeptember 9.).
	 *
	 * @param string $raw Nyers dátum.
	 * @return int Időbélyeg, vagy 0.
	 */
	public function parse_date( $raw ) {
		$raw = trim( (string) $raw );

		if ( '' === $raw ) {
			return 0;
		}

		$months = array(
			'január'    => '01',
			'február'   => '02',
			'március'   => '03',
			'április'   => '04',
			'május'     => '05',
			'június'    => '06',
			'július'    => '07',
			'augusztus' => '08',
			'szeptember' => '09',
			'október'   => '10',
			'november'  => '11',
			'december'  => '12',
		);

		// Gépi formátumok (ISO 8601 időponttal, RFC 2822): ezekben benne van
		// az időzóna is, ezért a PHP saját értelmezője a pontosabb.
		if ( preg_match( '/^\s*\d{4}-\d{2}-\d{2}[T ]\d{1,2}:\d{2}/', $raw )
			|| preg_match( '/^\s*[A-Za-z]{3},\s*\d{1,2}\s+[A-Za-z]{3}\s+\d{4}/', $raw ) ) {
			$timestamp = strtotime( $raw );

			if ( $timestamp ) {
				return (int) $timestamp;
			}
		}

		$normalized = mb_strtolower( $raw );

		// "2026. szeptember 9." vagy "2026. szept. 9. 14:30"
		if ( preg_match( '/(\d{4})\.?\s*([a-záéíóöőúüű]+)\.?\s*(\d{1,2})\.?(?:\s+(\d{1,2}):(\d{2}))?/u', $normalized, $m ) ) {
			foreach ( $months as $name => $number ) {
				if ( 0 === strpos( $name, rtrim( $m[2], '.' ) ) || 0 === strpos( rtrim( $m[2], '.' ), mb_substr( $name, 0, 4 ) ) ) {
					$date = sprintf(
						'%s-%s-%02d %02d:%02d:00',
						$m[1],
						$number,
						(int) $m[3],
						isset( $m[4] ) ? (int) $m[4] : 0,
						isset( $m[5] ) ? (int) $m[5] : 0
					);

					$timestamp = mbapp_local_to_timestamp( $date );

					if ( $timestamp ) {
						return $timestamp;
					}
				}
			}
		}

		// "2026. 09. 09." / "2026-09-09" / "2026.09.09 14:30"
		if ( preg_match( '/(\d{4})[.\-\/]\s*(\d{1,2})[.\-\/]\s*(\d{1,2})(?:[.\sTt]+(\d{1,2}):(\d{2}))?/', $normalized, $m ) ) {
			$date = sprintf(
				'%s-%02d-%02d %02d:%02d:00',
				$m[1],
				(int) $m[2],
				(int) $m[3],
				isset( $m[4] ) ? (int) $m[4] : 0,
				isset( $m[5] ) ? (int) $m[5] : 0
			);

			$timestamp = mbapp_local_to_timestamp( $date );

			if ( $timestamp ) {
				return $timestamp;
			}
		}

		// Szabványos formátumok (RFC 2822, ISO 8601).
		$timestamp = strtotime( $raw );

		return $timestamp ? (int) $timestamp : 0;
	}

	/**
	 * Egy hírelem importálása.
	 *
	 * @param array $item Elem.
	 * @return string 'imported' | 'duplicate' | 'skipped' | 'error'
	 */
	public function import_item( array $item ) {
		$title = sanitize_text_field( $item['title'] );
		$link  = $item['link'];

		if ( ! $title ) {
			$this->log( 'skipped', __( 'Cím nélküli elem.', 'mbapp' ), array( 'source_url' => $link ) );

			return 'skipped';
		}

		$existing = $this->find_existing( $item );

		if ( $existing ) {
			$this->log(
				'duplicate',
				sprintf(
					/* translators: %d: bejegyzés azonosító */
					__( 'Ez a hír már szerepel az oldalon (#%d).', 'mbapp' ),
					$existing
				),
				array(
					'title'      => $title,
					'source_url' => $link,
					'post_id'    => $existing,
				)
			);

			return 'duplicate';
		}

		$content = $item['content'];
		$image   = $item['image'];

		// Teljes cikk letöltése a hír saját oldaláról.
		if ( $link && ! empty( $this->settings['fetch_full_content'] ) ) {
			$full = $this->fetch_article( $link );

			if ( ! empty( $full['content'] ) ) {
				$content = $full['content'];
			}

			if ( ! $image && ! empty( $full['image'] ) ) {
				$image = $full['image'];
			}

			if ( empty( $item['date'] ) && ! empty( $full['date'] ) ) {
				$item['date'] = $full['date'];
			}
		}

		if ( ! $content ) {
			$content = $item['excerpt'] ? wpautop( $item['excerpt'] ) : '';
		}

		$content = $this->clean_content( $content, $link ? $link : $this->settings['source_url'] );

		// A kért forrás gomb a tartalom végére.
		if ( ! empty( $this->settings['append_source'] ) && $link ) {
			$content .= "\n\n" . mbapp_source_button( $link, $this->settings['source_label'], 'news' );
		}

		$timestamp = $item['date'] ? (int) $item['date'] : mbapp_now();

		$postarr = array(
			'post_type'     => MBAPP_CPT_NEWS,
			'post_title'    => $title,
			'post_content'  => $content,
			'post_excerpt'  => $item['excerpt'] ? $item['excerpt'] : mbapp_make_excerpt( $content ),
			'post_status'   => in_array( $this->settings['post_status'], array( 'publish', 'draft', 'pending' ), true )
				? $this->settings['post_status']
				: 'publish',
			'post_date'     => wp_date( 'Y-m-d H:i:s', $timestamp ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $timestamp ),
		);

		$post_id = wp_insert_post( wp_slash( $postarr ), true );

		if ( is_wp_error( $post_id ) ) {
			$this->log(
				'error',
				$post_id->get_error_message(),
				array(
					'title'      => $title,
					'source_url' => $link,
				)
			);

			return 'error';
		}

		update_post_meta( $post_id, '_mbapp_source_url', esc_url_raw( $link ) );
		update_post_meta( $post_id, '_mbapp_source_guid', sanitize_text_field( $item['guid'] ) );
		update_post_meta( $post_id, '_mbapp_source_hash', md5( $item['guid'] ? $item['guid'] : $title ) );
		update_post_meta( $post_id, '_mbapp_imported_at', mbapp_now() );
		update_post_meta( $post_id, '_mbapp_source_site', sanitize_text_field( $this->settings['source_site_name'] ) );

		if ( $item['date'] ) {
			update_post_meta( $post_id, '_mbapp_source_date', wp_date( 'Y-m-d H:i:s', $item['date'] ) );
		}

		// Kiemelt kép.
		$attachment_id = 0;

		if ( $image && ! empty( $this->settings['import_images'] ) ) {
			$attachment_id = $this->sideload_image( $image, $post_id, $title );
		}

		$this->log(
			'imported',
			$attachment_id
				? __( 'Sikeres import képpel együtt.', 'mbapp' )
				: __( 'Sikeres import (kép nélkül).', 'mbapp' ),
			array(
				'title'       => $title,
				'source_url'  => $link,
				'image_url'   => $image,
				'post_id'     => $post_id,
				'source_date' => $item['date'] ? wp_date( 'Y-m-d H:i:s', $item['date'] ) : null,
			)
		);

		/**
		 * Egy hír importálása után fut.
		 *
		 * @param int   $post_id A létrejött bejegyzés azonosítója.
		 * @param array $item    A forrásból kiolvasott adatok.
		 */
		do_action( 'mbapp_news_imported', $post_id, $item );

		return 'imported';
	}

	/**
	 * Már létező hír keresése.
	 *
	 * @param array $item Elem.
	 * @return int Bejegyzés azonosító vagy 0.
	 */
	private function find_existing( array $item ) {
		$meta_query = array( 'relation' => 'OR' );

		if ( ! empty( $item['link'] ) ) {
			$meta_query[] = array(
				'key'   => '_mbapp_source_url',
				'value' => esc_url_raw( $item['link'] ),
			);
		}

		if ( ! empty( $item['guid'] ) ) {
			$meta_query[] = array(
				'key'   => '_mbapp_source_hash',
				'value' => md5( $item['guid'] ),
			);
		}

		if ( count( $meta_query ) > 1 ) {
			$found = get_posts(
				array(
					'post_type'        => MBAPP_CPT_NEWS,
					'post_status'      => 'any',
					'posts_per_page'   => 1,
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => false,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'meta_query'       => $meta_query,
				)
			);

			if ( ! empty( $found ) ) {
				return (int) $found[0];
			}
		}

		// Cím szerinti egyezés végső biztosítékként.
		return $this->find_by_title( $item['title'] );
	}

	/**
	 * Bejegyzés keresése pontos cím alapján.
	 *
	 * @param string $title Cím.
	 * @return int
	 */
	private function find_by_title( $title ) {
		global $wpdb;

		$title = trim( (string) $title );

		if ( '' === $title ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$post_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_status != 'trash' LIMIT 1",
				$title,
				MBAPP_CPT_NEWS
			)
		);

		return (int) $post_id;
	}

	/**
	 * Egy cikk oldalának letöltése és feldolgozása.
	 *
	 * @param string $url Cikk URL.
	 * @return array
	 */
	public function fetch_article( $url ) {
		$result = array(
			'content' => '',
			'image'   => '',
			'date'    => 0,
		);

		$response = $this->fetch( $url );

		if ( is_wp_error( $response ) ) {
			$this->log(
				'skipped',
				sprintf(
					/* translators: %s: hibaüzenet */
					__( 'A teljes cikk nem tölthető le: %s', 'mbapp' ),
					$response->get_error_message()
				),
				array( 'source_url' => $url )
			);

			return $result;
		}

		$body = $response['body'];

		// Nyitott gráf kép.
		if ( preg_match( '#<meta[^>]+property=["\']og:image["\'][^>]*>#i', $body, $meta )
			&& preg_match( '#content=["\']([^"\']+)["\']#i', $meta[0], $content ) ) {
			$result['image'] = mbapp_absolute_url( html_entity_decode( $content[1] ), $url );
		}

		// Publikálás dátuma.
		if ( preg_match( '#<meta[^>]+property=["\']article:published_time["\'][^>]*>#i', $body, $meta )
			&& preg_match( '#content=["\']([^"\']+)["\']#i', $meta[0], $content ) ) {
			$result['date'] = $this->parse_date( $content[1] );
		}

		$xpath = $this->make_xpath( $body );

		if ( ! $xpath ) {
			return $result;
		}

		$node = $this->query_first( $xpath, null, $this->settings['content_selector'] );

		if ( $node ) {
			$html = '';

			foreach ( $node->childNodes as $child ) {
				$html .= $node->ownerDocument->saveHTML( $child );
			}

			$result['content'] = $html;

			if ( ! $result['image'] ) {
				$img = $this->query_image( $xpath, $node, 'img' );

				if ( $img ) {
					$result['image'] = mbapp_absolute_url( $img, $url );
				}
			}

			if ( ! $result['date'] ) {
				$raw = $this->query_attr( $xpath, $node, 'time', 'datetime' );

				if ( ! $raw ) {
					$raw = $this->query_text( $xpath, $node, 'time' );
				}

				$result['date'] = $this->parse_date( $raw );
			}
		}

		return $result;
	}

	/**
	 * Tartalom tisztítása és linkek abszolúttá tétele.
	 *
	 * @param string $html HTML.
	 * @param string $base Alap URL.
	 * @return string
	 */
	public function clean_content( $html, $base ) {
		$html = (string) $html;

		if ( '' === trim( $html ) ) {
			return '';
		}

		// Veszélyes / felesleges elemek eltávolítása.
		$html = preg_replace( '#<(script|style|noscript|iframe|form|button|svg)\b[^>]*>.*?</\1>#is', '', $html );
		$html = preg_replace( '#<(script|style|noscript|iframe|form|button|svg)\b[^>]*/?>#is', '', $html );

		// Megosztás / navigáció blokkok kiszűrése.
		$html = preg_replace(
			'#<(div|nav|aside|ul)\b[^>]*class=["\'][^"\']*(share|social|breadcrumb|related|comment|sidebar|tags?)[^"\']*["\'][^>]*>.*?</\1>#is',
			'',
			$html
		);

		// Relatív hivatkozások abszolúttá alakítása.
		$html = preg_replace_callback(
			'#\b(href|src)=(["\'])([^"\']+)\2#i',
			static function ( $matches ) use ( $base ) {
				return $matches[1] . '=' . $matches[2] . esc_url_raw( mbapp_absolute_url( html_entity_decode( $matches[3] ), $base ) ) . $matches[2];
			},
			$html
		);

		// srcset eltávolítása (a helyi méretek úgysem stimmelnének).
		$html = preg_replace( '#\s+(srcset|sizes|loading|decoding)=(["\'])[^"\']*\2#i', '', $html );

		$html = wp_kses_post( $html );
		$html = preg_replace( '#(\s*<p>\s*(&nbsp;)?\s*</p>\s*)+#i', "\n", $html );

		return trim( $html );
	}

	/**
	 * Kép letöltése a médiatárba és kiemelt képnek állítása.
	 *
	 * @param string $url     Kép URL.
	 * @param int    $post_id Bejegyzés.
	 * @param string $title   Cím (alt szöveghez).
	 * @return int Csatolmány azonosító vagy 0.
	 */
	public function sideload_image( $url, $post_id, $title ) {
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$url = esc_url_raw( $url );

		if ( ! $url ) {
			return 0;
		}

		$attachment_id = media_sideload_image( $url, $post_id, $title, 'id' );

		if ( is_wp_error( $attachment_id ) ) {
			$this->log(
				'skipped',
				sprintf(
					/* translators: %s: hibaüzenet */
					__( 'A kép nem tölthető le: %s', 'mbapp' ),
					$attachment_id->get_error_message()
				),
				array(
					'post_id'   => $post_id,
					'image_url' => $url,
				)
			);

			return 0;
		}

		update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $title ) );
		set_post_thumbnail( $post_id, $attachment_id );
		update_post_meta( $post_id, '_mbapp_source_image', $url );

		return (int) $attachment_id;
	}

	/**
	 * Próbalekérés az admin felülethez.
	 *
	 * @param string $url Forrás URL (üres esetén a beállított).
	 * @return array|WP_Error
	 */
	public function preview( $url = '' ) {
		$url = $url ? $url : $this->settings['source_url'];

		$items = $this->collect_items( $url );

		if ( is_wp_error( $items ) ) {
			return $items;
		}

		return array_slice( $items, 0, 8 );
	}

	/**
	 * Naplózás.
	 *
	 * @param string $status  Státusz.
	 * @param string $message Üzenet.
	 * @param array  $extra   Egyéb mezők.
	 */
	private function log( $status, $message, array $extra = array() ) {
		MBapp_Logger::add(
			array_merge(
				array(
					'run_id'  => $this->run_id,
					'status'  => $status,
					'message' => $message,
				),
				$extra
			)
		);
	}
}
