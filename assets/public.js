(function($) {
    function set_geo_id( id, $this ) {
        if( $this ) {
            var text = $this.text();

            $this.closest('.city-select-wrap').fadeOut();
            $('.gz-current-city-name').each(function(index, el) {
                el.innerHTML = text;
            });
        }

        $.ajax({
            url: gz.ajax_url,
            type: 'POST',
            data: {
                action: 'gz_update_geo_item',
                nonce: gz.nonce,
                city_id: id,
            },
        })
        .done(function() {
            console.log("Geo position updated.");
        })
        .fail(function() {
            console.log("Error, geo posotion not will be changed.");
        });
    }

    /**
     * Update Geo Location
     */
    $('.gz-current-city-wrap .gz-current-city-name').on('click', function(event) {
        $(this).closest('.gz-current-city-wrap').find('.city-select-wrap').fadeToggle();
    });

    $( 'a[data-geo-id]' ).on('click', function(event) {
        set_geo_id( $(this).attr('data-geo-id'), $(this) );
    });

    /**
     * List Searches
     */
    var ajaxAllow = true;
    var typingTimer;
    var doneTypingInterval = 500;
    var $search = $('#city_search');
    var value = $search.val();

    $search.on('keyup change', function () {
        if( value != $('#city_search').val() ) {
            value = $('#city_search').val();
            clearTimeout(typingTimer);
            typingTimer = setTimeout(doneTyping, doneTypingInterval);
        }
    });

    $search.on('keydown', function () { clearTimeout(typingTimer); });
    function doneTyping () {
        if( ! value ) {
            $search.closest('fieldset').find('.result-list').fadeOut();
            return;
        }

        if( ajaxAllow ) {
            ajaxAllow = false;
            $.ajax({
                url: gz.ajax_url,
                type: 'POST',
                data: {
                    action: 'gz_cities_name_list',
                    nonce: gz.nonce,
                    string: value,
                },
            })
            .done(function( response ) {
                response = JSON.parse(response);
                if( response.length ) {
                    var $res = $search.closest('fieldset').find('.result-list');
                    $res.html('');

                    var list = '';
                    response.forEach( function(el, index) {
                        list+= '<li><a href="#" data-geo-id="'+el.city_id+'">'+el.city+'</a></li>\n';
                    });

                    $res.append( '<ul class="two-columns">' + list + '</ul>' );
                    $res.fadeIn();

                    $( '.result-list a[data-geo-id]' ).on('click', function(event) {
                        set_geo_id( $(this).attr('data-geo-id'), $(this) );
                    });
                }
            })
            .always(function() {
                ajaxAllow = true;
            });
        }
    }

})(jQuery);

