( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element ) {
		return;
	}

	var registerBlockType = wp.blocks.registerBlockType;
	var elementApi = wp.element;
	var createElement = elementApi.createElement;
	var Fragment = elementApi.Fragment;
	var useEffect = elementApi.useEffect;
	var i18nApi = wp.i18n || {};
	var __ = i18nApi.__ || function ( text ) { return text; };
	var blockEditor = wp.blockEditor || wp.editor || {};
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var components = wp.components || {};
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var Placeholder = components.Placeholder;
	var ServerSideRender = wp.serverSideRender || components.ServerSideRender || null;

	var config = window.InaviiEditorBlockConfig || {};
	var feeds = Array.isArray( config.feeds ) ? config.feeds : [];
	var settingsUrl = typeof config.settingsUrl === 'string' ? config.settingsUrl : '';

	var selectLabel = __( 'Select Feed', 'inavii-social-feed' );
	var options = [
		{ label: selectLabel, value: 0 },
	];

	feeds.forEach( function ( feed ) {
		if ( ! feed || typeof feed !== 'object' ) {
			return;
		}

		var id = parseInt( feed.id, 10 );
		if ( ! Number.isFinite( id ) || id <= 0 ) {
			return;
		}

		var title = typeof feed.title === 'string' && feed.title.trim() !== '' ? feed.title : 'Feed #' + id;
		options.push( { label: title, value: id } );
	} );

	registerBlockType( 'inavii/social-feed', {
		apiVersion: 2,
		title: __( 'Inavii Social Feed', 'inavii-social-feed' ),
		description: __( 'Display your selected Inavii feed.', 'inavii-social-feed' ),
		icon: 'instagram',
		category: 'widgets',
		supports: {
			html: false,
		},
		attributes: {
			feedId: {
				type: 'number',
				default: 0,
			},
		},
		edit: function ( props ) {
			var feedId = parseInt( props.attributes.feedId, 10 );
			if ( ! Number.isFinite( feedId ) ) {
				feedId = 0;
			}

			var blockProps = typeof useBlockProps === 'function'
				? useBlockProps()
				: ( props.className ? { className: props.className } : {} );

			if ( useEffect ) {
				useEffect(
					function () {
						if ( feedId > 0 || options.length <= 1 ) {
							return;
						}

						var firstValue = parseInt( options[1].value, 10 );
						if ( Number.isFinite( firstValue ) && firstValue > 0 ) {
							props.setAttributes( { feedId: firstValue } );
						}
					},
					[ feedId ]
				);
			}

			var inspector = InspectorControls && PanelBody && SelectControl ? createElement(
				InspectorControls,
				null,
				createElement(
					PanelBody,
					{
						title: __( 'Feed Settings', 'inavii-social-feed' ),
						initialOpen: true,
					},
					createElement( SelectControl, {
						label: selectLabel,
						value: feedId,
						options: options,
						onChange: function ( value ) {
							var next = parseInt( value, 10 );
							props.setAttributes( { feedId: Number.isFinite( next ) && next > 0 ? next : 0 } );
						},
					} )
				)
			) : null;

			if ( options.length <= 1 ) {
				return createElement(
					Fragment,
					null,
					inspector,
					createElement(
						'div',
						blockProps,
						createElement(
							Placeholder,
							{
								label: __( 'Inavii Social Feed', 'inavii-social-feed' ),
								instructions: __( 'No feeds found. Create your first feed in Inavii settings.', 'inavii-social-feed' ),
							},
							settingsUrl ? createElement(
								'a',
								{
									href: settingsUrl,
									target: '_blank',
									rel: 'noopener noreferrer',
								},
								__( 'Open Settings', 'inavii-social-feed' )
							) : null
						)
					)
				);
			}

			if ( ! ServerSideRender ) {
				return createElement(
					Fragment,
					null,
					inspector,
					createElement(
						'div',
						blockProps,
						createElement( Placeholder, {
							label: __( 'Inavii Social Feed', 'inavii-social-feed' ),
							instructions: __( 'Select a feed to preview.', 'inavii-social-feed' ),
						} )
					)
				);
			}

			return createElement(
				Fragment,
				null,
				inspector,
				createElement(
					'div',
					blockProps,
					createElement( ServerSideRender, {
						block: 'inavii/social-feed',
						attributes: {
							feedId: feedId,
						},
					} )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
}( window.wp ) );
