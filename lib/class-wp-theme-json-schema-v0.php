<?php
/**
 * Processes structures that adhere to the theme.json schema V0.
 *
 * @package gutenberg
 */

/**
 * Class that encapsulates the processing of
 * structures that adhere to the theme.json V0.
 */
class WP_Theme_JSON_Schema_V0 {

	/**
	 * Data schema of each block within a theme.json.
	 *
	 * Example:
	 *
	 * {
	 *   'block-one': {
	 *     'styles': {
	 *       'color': {
	 *         'background': 'color'
	 *       }
	 *     },
	 *     'settings': {
	 *       'color': {
	 *         'custom': true
	 *       }
	 *     }
	 *   },
	 *   'block-two': {
	 *     'styles': {
	 *       'color': {
	 *         'link': 'color'
	 *       }
	 *     }
	 *   }
	 * }
	 */
	const SCHEMA = array(
		'customTemplates' => null,
		'templateParts'   => null,
		'styles'          => array(
			'border'     => array(
				'radius' => null,
				'color'  => null,
				'style'  => null,
				'width'  => null,
			),
			'color'      => array(
				'background' => null,
				'gradient'   => null,
				'link'       => null,
				'text'       => null,
			),
			'spacing'    => array(
				'padding' => array(
					'top'    => null,
					'right'  => null,
					'bottom' => null,
					'left'   => null,
				),
			),
			'typography' => array(
				'fontFamily'     => null,
				'fontSize'       => null,
				'fontStyle'      => null,
				'fontWeight'     => null,
				'lineHeight'     => null,
				'textDecoration' => null,
				'textTransform'  => null,
			),
		),
		'settings'        => array(
			'border'     => array(
				'customRadius' => null,
				'customColor'  => null,
				'customStyle'  => null,
				'customWidth'  => null,
			),
			'color'      => array(
				'custom'         => null,
				'customGradient' => null,
				'gradients'      => null,
				'link'           => null,
				'palette'        => null,
			),
			'spacing'    => array(
				'customPadding' => null,
				'units'         => null,
			),
			'typography' => array(
				'customFontSize'        => null,
				'customLineHeight'      => null,
				'dropCap'               => null,
				'fontFamilies'          => null,
				'fontSizes'             => null,
				'customFontStyle'       => null,
				'customFontWeight'      => null,
				'customTextDecorations' => null,
				'customTextTransforms'  => null,
			),
			'custom'     => null,
			'layout'     => null,
		),
	);

	/**
	 * Constructor.
	 *
	 * @param array $theme_json A structure that follows the theme.json schema.
	 */
	public static function sanitize( $input = array(), $block_metadata ) {
		$output = array();

		if ( ! is_array( $input ) ) {
			return $output;
		}

		// Remove top-level keys that aren't present in the schema.
		$output = array_intersect_key( $input, self::SCHEMA );

		foreach ( array( 'settings', 'styles' ) as $subtree ) {
			// Remove settings & styles subtrees if they aren't arrays.
			if ( isset( $output[ $subtree ] ) && ! is_array( $output[ $subtree ] ) ) {
				unset( $output[ $subtree ] );
			}

			// Remove block selectors subtrees declared within settings & styles if that aren't registered.
			if ( isset( $output[ $subtree ] ) ) {
				$output[ $subtree ] = array_intersect_key( $output[ $subtree ], $block_metadata );
			}
		}

		foreach ( $block_metadata as $block_selector => $metadata ) {
			if ( isset( $output['styles'][ $block_selector ] ) ) {
				// Remove the block selector subtree if it's not an array.
				if ( ! is_array( $output['styles'][ $block_selector ] ) ) {
					unset( $output['styles'][ $block_selector ] );
					continue;
				}

				$styles_schema                       = self::SCHEMA['styles'];
				$output['styles'][ $block_selector ] = self::remove_keys_not_in_schema(
					$output['styles'][ $block_selector ],
					$styles_schema
				);

				// Remove the block selector subtree if it is empty after having processed it.
				if ( empty( $output['styles'][ $block_selector ] ) ) {
					unset( $output['styles'][ $block_selector ] );
				}
			}

			if ( isset( $output['settings'][ $block_selector ] ) ) {
				// Remove the block selector subtree if it's not an array.
				if ( ! is_array( $output['settings'][ $block_selector ] ) ) {
					unset( $output['settings'][ $block_selector ] );
					continue;
				}

				// Remove the properties that aren't present in the schema.
				$output['settings'][ $block_selector ] = self::remove_keys_not_in_schema(
					$output['settings'][ $block_selector ],
					self::SCHEMA['settings']
				);

				// Remove the block selector subtree if it is empty after having processed it.
				if ( empty( $output['settings'][ $block_selector ] ) ) {
					unset( $output['settings'][ $block_selector ] );
				}
			}
		}

		// Remove the settings & styles subtrees if they're empty after having processed them.
		foreach ( array( 'settings', 'styles' ) as $subtree ) {
			if ( empty( $output[ $subtree ] ) ) {
				unset( $output[ $subtree ] );
			}
		}

		return $output;
	}

	/**
	 * Given a tree, removes the keys that are not present in the schema.
	 *
	 * It is recursive and modifies the input in-place.
	 *
	 * @param array $tree Input to process.
	 * @param array $schema Schema to adhere to.
	 *
	 * @return array Returns the modified $tree.
	 */
	private static function remove_keys_not_in_schema( $tree, $schema ) {
		$tree = array_intersect_key( $tree, $schema );

		foreach ( $schema as $key => $data ) {
			if ( is_array( $schema[ $key ] ) && isset( $tree[ $key ] ) ) {
				$tree[ $key ] = self::remove_keys_not_in_schema( $tree[ $key ], $schema[ $key ] );

				if ( empty( $tree[ $key ] ) ) {
					unset( $tree[ $key ] );
				}
			}
		}

		return $tree;
	}

	public static function to_v1( $old, $version ) {
		// Copy everything.
		$new = $old;

		$blocks_to_consolidate = array(
			'core/heading/h1'     => 'core/heading',
			'core/heading/h2'     => 'core/heading',
			'core/heading/h3'     => 'core/heading',
			'core/heading/h4'     => 'core/heading',
			'core/heading/h5'     => 'core/heading',
			'core/heading/h6'     => 'core/heading',
			'core/post-title/h1'  => 'core/post-title',
			'core/post-title/h2'  => 'core/post-title',
			'core/post-title/h3'  => 'core/post-title',
			'core/post-title/h4'  => 'core/post-title',
			'core/post-title/h5'  => 'core/post-title',
			'core/post-title/h6'  => 'core/post-title',
			'core/query-title/h1' => 'core/query-title',
			'core/query-title/h2' => 'core/query-title',
			'core/query-title/h3' => 'core/query-title',
			'core/query-title/h4' => 'core/query-title',
			'core/query-title/h5' => 'core/query-title',
			'core/query-title/h6' => 'core/query-title',
		);

		// Overwrite the things that change.
		$new['settings'] = self::process_settings( $old['settings'], $blocks_to_consolidate );
		$new['styles']   = self::process_styles( $old['styles'], $blocks_to_consolidate );
		$new['version']  = $version;

		return $new;
	}

	private static function process_settings( $settings, $blocks_to_consolidate ) {
		$new = array();

		$paths_to_override = array(
			array( 'color', 'palette' ),
			array( 'color', 'gradients' ),
			array( 'spacing', 'units' ),
			array( 'typography', 'fontSizes' ),
			array( 'typography', 'fontFamilies' ),
			array( 'custom' ),
		);

		// 'defaults' settings become top-level.
		if ( isset( $settings[ WP_Theme_JSON::ALL_BLOCKS_NAME] ) ) {
			$new = $settings[ WP_Theme_JSON::ALL_BLOCKS_NAME ];
			unset( $settings[ WP_Theme_JSON::ALL_BLOCKS_NAME ] );
		}

		// 'root' settings override 'defaults'.
		if ( isset( $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ] ) ) {
			$new = array_replace_recursive( $new, $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ] );

			// The array_replace_recursive algorithm merges at the leaf level.
			// This means that when a leaf value is an array,
			// the incoming array won't replace the existing,
			// but the numeric indexes are used for replacement.
			//
			// These cases we hold into $paths_to_override
			// and need to replace them with the new data.
			foreach( $paths_to_override as $path ) {
				$root_value = _wp_array_get(
					$settings,
					array_merge( array( WP_Theme_JSON::ROOT_BLOCK_NAME), $path ),
					null
				);
				if ( null !== $root_value ) {
					gutenberg_experimental_set( $new, $path, $root_value );
				}
			}

			unset( $settings[ WP_Theme_JSON::ROOT_BLOCK_NAME ] );
		}

		if ( empty( $settings ) ) {
			return $new;
		}

		// At this point, it only contains block's data.
		// However, some block data we need to consolidate
		// into a different section, as it's the case for:
		//
		// - core/heading/h1, core/heading/h2, ...         => core/heading
		// - core/post-title/h1, core/post-title/h2, ...   => core/post-title
		// - core/query-title/h1, core/query-title/h2, ... => core/query-title
		//
		$new['blocks'] = $settings;
		foreach( $blocks_to_consolidate as $old_name => $new_name ) {
			// Unset the $old_name.
			unset( $new[ $old_name ] );

			// Consolidate the $new value.
			$block_settings = _wp_array_get( $settings, array( $old_name ), null );
			if ( null !== $block_settings ) {
				$new_path     = array('blocks', $new_name );
				$new_settings = array();
				gutenberg_experimental_set( $new_settings, $new_path, $block_settings );

				$new = array_replace_recursive( $new, $new_settings );
				foreach( $paths_to_override as $path ) {
					$block_value  = _wp_array_get( $block_settings, $path, null );
					if ( null !== $block_value ) {
						gutenberg_experimental_set( $new, array_merge( $new_path, $path ), $block_value );
					}
				}
			}
		}

		return $new;
	}

	private static function process_styles( $styles, $blocks_to_consolidate ) {
		$new = array();

		// Styles within root become top-level.
		if ( isset( $styles[ WP_Theme_JSON::ROOT_BLOCK_NAME ] ) ) {
			$new = $styles[ WP_Theme_JSON::ROOT_BLOCK_NAME ];
			unset( $styles[ WP_Theme_JSON::ROOT_BLOCK_NAME ] );

			// Transform root.styles.color.link into elements.link.color.text.
			if ( isset( $new['color']['link'] ) ) {
				$new['elements']['link']['color']['text'] = $new['color']['link'];
				unset( $new['color']['link'] );
			}
		}

		if ( empty( $styles ) ) {
			return $new;
		}

		// At this point, it only contains block's data.
		// However, we still need to consolidate a few things:
		//
		// - link color
		// - blocks that were previously many (core/heading/h1, core/heading/h2)
		//   need to be consolidated in only one + elements (core/heading with elements h1, h2, etc).
		$new['blocks'] = $styles;
		foreach( $new['blocks'] as $block_name => $metadata ) {
			// Transform root.styles.color.link into elements.link.color.text.
			if ( isset( $metadata['color']['link'] ) ) {
				$new['blocks'][ $block_name ]['elements']['link']['color']['text'] = $metadata['color']['link'];
				unset( $new['blocks'][ $block_name ]['color']['link'] );
			}
		}

		foreach( $blocks_to_consolidate as $old_name => $new_name ) {
			// Unset the $old_name.
			unset( $new[ $old_name ] );

			// Consolidate the $new value.
			$element_name = explode( '/', $old_name )[2];
			$block_styles = _wp_array_get( $styles, array( $old_name ), null );
			if ( null !== $block_styles ) {
				$new_path   = array('blocks', $new_name, 'elements', $element_name );
				$new_styles = array();
				gutenberg_experimental_set( $new_styles, $new_path, $block_styles );
				$new = array_replace_recursive( $new, $new_styles );
			}
		}

		return $new;
	}
}
