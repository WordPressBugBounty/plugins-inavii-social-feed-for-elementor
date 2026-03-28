( function () {
    'use strict';

    const FEED_SELECTOR = '[data-inavii-social-feed][data-inavii-render-mode="ajax"]';
    const INLINE_PAYLOAD_SELECTOR = 'script[data-inavii-inline-payload]';
    const APP_LOADING_KEY = '__inaviiFrontAppLoading';
    const APP_LOADED_KEY = '__inaviiFrontAppLoaded';

    function getConfig() {
        if ( typeof window === 'undefined' || typeof window.InaviiSocialFeedFrontConfig !== 'object' ) {
            return null;
        }

        const config = window.InaviiSocialFeedFrontConfig;
        if ( typeof config.feedsBaseUrl !== 'string' || config.feedsBaseUrl === '' ) {
            return null;
        }
        if ( typeof config.appScriptUrl !== 'string' || config.appScriptUrl === '' ) {
            return null;
        }

        return config;
    }

    async function fetchPayload( feedId, publicKey, config, cache ) {
        const cacheKey = String( feedId ) + '|' + String( publicKey || '' );
        if ( cache[ cacheKey ] ) {
            return cache[ cacheKey ];
        }

        const endpoint = String( config.feedsBaseUrl ).replace( /\/+$/, '' ) + '/' + encodeURIComponent( String( feedId ) );

        const request = requestPayload( endpoint, publicKey )
            .catch( () => ( { payload: null, invalidFeed: false } ) );

        cache[ cacheKey ] = request;
        return request;
    }

    async function requestPayload( endpoint, publicKey ) {
        if ( typeof publicKey !== 'string' || publicKey.trim() === '' ) {
            return { payload: null, invalidFeed: false };
        }

        const url = new URL( endpoint, window.location.origin );
        const response = await fetch( url.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Inavii-Feed-Key': publicKey,
            },
        } ).catch( () => null );

        if ( ! response ) {
            return { payload: null, invalidFeed: false };
        }

        const body = await response.json().catch( () => ( {} ) );

        if ( ! response.ok ) {
            return {
                payload: null,
                invalidFeed: response.status === 400 || response.status === 404,
            };
        }

        return {
            payload: normalizePayload( body ),
            invalidFeed: false,
        };
    }

    function normalizePayload( body ) {
        if ( ! body || typeof body !== 'object' ) {
            return null;
        }

        // Compatibility for wrapped responses: { success: true, data: {...} }.
        if ( typeof body.data === 'object' && body.data !== null && ! Array.isArray( body.data ) ) {
            return isFrontPayload( body.data ) ? body.data : null;
        }

        // Native WP REST response returns the payload object directly.
        if ( ! Array.isArray( body ) ) {
            return isFrontPayload( body ) ? body : null;
        }

        return null;
    }

    function isFrontPayload( payload ) {
        if ( ! payload || typeof payload !== 'object' || Array.isArray( payload ) ) {
            return false;
        }

        if ( typeof payload.options !== 'object' || payload.options === null ) {
            return false;
        }

        if ( ! Array.isArray( payload.media ) ) {
            return false;
        }

        return true;
    }

    function mountInlinePayload( container, payload ) {
        const existing = container.querySelector( INLINE_PAYLOAD_SELECTOR );
        if ( existing ) {
            existing.remove();
        }

        const script = document.createElement( 'script' );
        script.type = 'application/json';
        script.setAttribute( 'data-inavii-inline-payload', '' );
        script.textContent = JSON.stringify( payload );
        container.appendChild( script );
    }

    function markInvalidFeed( container, feedId ) {
        container.setAttribute( 'data-inavii-invalid-feed', '1' );

        const existing = container.querySelector( INLINE_PAYLOAD_SELECTOR );
        if ( existing ) {
            existing.remove();
        }

        const script = document.createElement( 'script' );
        script.type = 'application/json';
        script.setAttribute( 'data-inavii-inline-payload', '' );
        script.textContent = JSON.stringify( {
            options: {
                id: feedId,
                settings: {},
            },
            media: [],
        } );
        container.appendChild( script );
    }

    function loadFrontApp( config ) {
        if ( window[ APP_LOADED_KEY ] || window[ APP_LOADING_KEY ] ) {
            return;
        }

        window[ APP_LOADING_KEY ] = true;

        const script = document.createElement( 'script' );
        script.src = config.appScriptUrl;
        script.async = false;
        script.onload = function () {
            window[ APP_LOADED_KEY ] = true;
            window[ APP_LOADING_KEY ] = false;
        };
        script.onerror = function () {
            window[ APP_LOADING_KEY ] = false;
        };

        document.body.appendChild( script );
    }

    async function hydrateFeeds() {
        const config = getConfig();
        if ( ! config ) {
            return;
        }

        const containers = Array.from( document.querySelectorAll( FEED_SELECTOR ) );

        if ( containers.length === 0 ) {
            return;
        }

        const cache = {};
        let hydratedCount = 0;
        let invalidCount = 0;

        await Promise.all(
            containers.map( async ( container ) => {
                const feedId = Number.parseInt( container.getAttribute( 'data-feed-id' ) || '0', 10 );
                const feedPublicKey = container.getAttribute( 'data-inavii-feed-public-key' ) || '';
                if ( ! Number.isFinite( feedId ) || feedId <= 0 ) {
                    return;
                }

                const result = await fetchPayload( feedId, feedPublicKey, config, cache );

                if ( result.payload ) {
                    mountInlinePayload( container, result.payload );
                    hydratedCount += 1;
                    return;
                }

                if ( result.invalidFeed ) {
                    markInvalidFeed( container, feedId );
                    invalidCount += 1;
                }
            } )
        );

        if ( hydratedCount + invalidCount <= 0 ) {
            return;
        }

        loadFrontApp( config );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', hydrateFeeds, { once: true } );
    } else {
        hydrateFeeds();
    }
} )();
