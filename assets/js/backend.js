
jQuery( document ).ready( function( $ ){

    var modal_sites = {};
    var current_page_builder = 'all';

    var getTemplate = _.memoize(function () {

        var compiled,
            /*
             * Underscore's default ERB-style templates are incompatible with PHP
             * when asp_tags is enabled, so WordPress uses Mustache-inspired templating syntax.
             *
             * @see trac ticket #22344.
             */
            options = {
                evaluate: /<#([\s\S]+?)#>/g,
                interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
                escape: /\{\{([^\}]+?)\}\}(?!\})/g,
                variable: 'data'
            };

        return function (data, id, data_variable_name ) {
            if (_.isUndefined(id)) {
                id = 'starter-template-item-html';
            }
            if ( ! _.isUndefined( data_variable_name ) && _.isString( data_variable_name ) ) {
                options.variable = data_variable_name;
            } else {
                options.variable = 'data';
            }
            compiled = _.template($('#' + id).html(), null, options);
            return compiled(data);
        };

    });


    var Starter_Modal_Site = function( $item, data ){
        var m = this;
        var steps;
        steps = {
            modal: null,
            item: $item,
            owl: null,
            current_step: 0, // mean first step
            breadcrumb: null,
            getTemplate: getTemplate,
            data: {},
            buttons: {},
            last_step: 4,
            xml_id: 0,
            json_id: 0,
            doing: false,
            current_builder: 'all',
            recommend_plugins: {},
            skip_plugins: false,
            add_modal: function () {
                var that = this;
                var template = that.getTemplate();

                that.data = data;
                var html = template( data, 'tpl-cs-item-modal' );
                that.modal = $( html );
                $( '#wpbody-content' ).append( that.modal );
                that.init();
            },
            _reset: function(){
                this.doing = false;
                $( '.cs-breadcrumb', this.modal ).removeClass('cs-hide');
                $( '.cs-action-buttons a, .cs-step', this.modal ).removeClass('loading circle-loading completed cs-hide');
            },
            _open: function(){
                var that = this;

                that.item.on('click', '.cs-open-modal, .theme-screenshot', function (e) {
                    e.preventDefault();
                    $('body').addClass('starter-templates-show-modal');
                    if (that.owl) {
                        that.owl.trigger('to.owl.carousel', [0, 0]);
                    }
                    that.modal.addClass('cs-show');
                    that._reset();
                    $(window).resize();

                    if ( that.data.pro && ! Starter_Templates.license_valid ) {
                        that.disable_button( 'start' );
                        that.buttons.start.addClass( 'pro-only' );
                        that.buttons.start.find( '.cs-btn-circle-text' ).text( Starter_Templates.pro_text );
                    }

                });

            },
            _install_plugins_notice: function (){
                //.cs-install-plugins
                var that = this;
                var manual_plugins      = '';

                if ( ! _.isObject( that.data.manual_plugins ) ) {
                    that.data.manual_plugins  ={};
                }

                _.each( that.data.manual_plugins, function( plugin_name, plugin_file ){
                    // The manual plugin in the list recommend plugin
                    if ( ! _.isUndefined( that.recommend_plugins[ plugin_file ] ) ) {
                        if ( ! that.is_installed( plugin_file ) ) {
                            if (_.isUndefined(Starter_Templates.installed_plugins[plugin_file])) {
                                manual_plugins += '<li><div class="circle-loader "><div class="checkmark draw"></div></div><span class="cs-plugin-name">' + plugin_name + '</span></li>';
                            }
                        }
                    }
                } );


                $( '.cs-installed-plugins', that.modal ).hide();
                $( '.cs-install-plugins', that.modal ).hide();

                if ( manual_plugins !==  '' ) {
                    $( '.cs-install-manual-plugins', that.modal ).show();
                    $( '.cs-install-manual-plugins ul', that.modal ).html( manual_plugins );
                } else {
                    $( '.cs-install-manual-plugins', that.modal ).hide();
                }
            },

            _setup_plugins: function(){
                var that = this;

                if ( _.size( that.recommend_plugins ) > 0 ) {
                    $('.cs-installing-plugins', that.modal).html('');
                    _.each(that.recommend_plugins, function (name, slug) {
                        var html = '';
                        // If plugin not in manual install
                        if (_.isUndefined(that.data.manual_plugins[slug])) {
                            if (that.is_activated(slug)) {
                                html = '<li data-slug="' + slug + '" class="is-activated"><div class="circle-loader load-complete"><div class="checkmark draw"></div></div><span class="cs-plugin-name">' + name + '</span></li>';
                            } else if (!that.is_installed(slug)) { // plugin not installed
                                html = '<li data-slug="' + slug + '" class="do-install-n-activate"><div class="circle-loader "><div class="checkmark draw"></div></div><span class="cs-plugin-name">' + name + '</span></li>';
                            } else { // Plugin install but not active
                                html = '<li data-slug="' + slug + '" class="do-activate"><div class="circle-loader"><div class="checkmark draw"></div></div><span class="cs-plugin-name">' + name + '</span></li>';
                            }
                        } else {
                            // Manual Plugin installed and activated
                            if (that.is_activated(slug)) {
                                html = '<li data-slug="' + slug + '" class="is-activated"><div class="circle-loader load-complete"><div class="checkmark draw"></div></div><span class="cs-plugin-name">' + name + '</span></li>';
                            } else if (that.is_installed(slug)) {   // Manual Plugin install but not activated
                                html = '<li data-slug="' + slug + '" class="do-activate"><div class="circle-loader"><div class="checkmark draw"></div></div><span class="cs-plugin-name">' + name + '</span></li>';
                            }
                        }

                        $('.cs-installing-plugins', that.modal).append(html);
                    });
                } else {
                    that.skip_plugins = true;
                }

            },

            disable_button: function( button ){
                if ( !_.isUndefined( this.buttons[ button ] ) ) {
                    this.buttons[ button ].addClass( 'disabled' );
                }
            },
            loading_button: function( button ){
                if ( !_.isUndefined( this.buttons[ button ] ) ) {
                    this.buttons[ button ].addClass( 'loading circle-loading' );
                }
            },
            completed_button: function( button ){
                if ( !_.isUndefined( this.buttons[ button ] ) ) {
                    this.buttons[ button ].addClass( 'completed' );
                }
            },
            active_button: function( button ){
                if ( !_.isUndefined( this.buttons[ button ] ) ) {
                    this.buttons[ button ].removeClass( 'disabled' );
                }
            },
            init: function(){
                var that = this;

                that.buttons.skip = $( '.cs-skip', that.modal );
                that.buttons.start = $( '.cs-do-start', that.modal );
                that.buttons.install_plugins = $( '.cs-do-install-plugins', that.modal );
                that.buttons.import_content = $( '.cs-do-import-content', that.modal );
                that.buttons.import_options = $( '.cs-do-import-options', that.modal );
                that.buttons.view_site = $( '.cs-do-view-site', that.modal );

                if ( _.isEmpty( that.data.plugins ) && _.isEmpty( that.data.manual_plugins )  ) {
                    that.last_step = 3;
                    $( '.cs-breadcrumb li[data-step="install_plugins"]', that.modal ).remove();
                    $( '.cs-step-install_plugins', that.modal ).remove();
                    that.buttons.install_plugins.remove();
                }
                that.breadcrumb = $( '.cs-breadcrumb li', that.modal );

                /**
                 * @see https://owlcarousel2.github.io/OwlCarousel2/docs/api-events.html
                 * @type {jQuery}
                 */
                that.owl = $(".owl-carousel", that.modal ).owlCarousel({
                    items: 1,
                    loop: false,
                    mouseDrag: false,
                    touchDrag: false,
                    pullDrag: false,
                    freeDrag: false,
                    rewind: false,
                    autoHeight:true
                });

                that._make_steps_clickable();

                that.owl.on( 'initialize.owl.carousel', function( e, a  ) {
                    that._make_steps_clickable();
                });

                that.owl.on( 'changed.owl.carousel', function( e, a  ) {
                    that.current_step = e.page.index;
                    that._make_steps_clickable();
                    that.doing = false;

                    if ( that.current_step === 1 && that.skip_plugins ) {
                        that.step_completed( 'install_plugins', 0 );
                    }
                });

                // back to list
                that.modal.on( 'click', '.cs-back-to-list', function( e  ) {
                    e.preventDefault();
                    $( 'body' ).removeClass( 'starter-templates-show-modal' );
                    that.modal.removeClass( 'cs-show' );
                } );

                that.modal.on( 'click', '.cs-skip', function( e  ) {
                    e.preventDefault();
                    if ( ! that.doing ) {
                        that.next_step();
                    }
                } );

                that._breadcrumb_actions();
                that._do_start_import();
                that._installing_plugins();
                that._importing_content();
                that._importing_options();
                that._open();
            },

            next_step: function(){
                this.owl.trigger('next.owl.carousel');
            },

            /**
             * Skip this step and next
             */
            skip: function(){
                this.owl.trigger('next.owl.carousel');
            },

            _make_steps_clickable: function(){
                var that = this;
                that._reset();
                that.breadcrumb.removeClass( 'cs-clickable' );
                for( var i = 0; i <= this.current_step; i++ ) {
                    that.breadcrumb.eq( i ).addClass( 'cs-clickable' );
                }

                if ( that.current_step === that.last_step ) {
                    that.buttons.skip.addClass( 'cs-hide' );
                    $('.cs-breadcrumb', that.modal ).addClass( 'cs-hide' );
                } else {
                    that.buttons.skip.removeClass( 'cs-hide' );
                    $('.cs-breadcrumb', that.modal ).removeClass( 'cs-hide' );
                }

                if ( that.current_step !== 0 && that.current_step !== that.last_step ) {
                    that.buttons.skip.removeClass( 'cs-hide' );
                } else {
                    that.buttons.skip.addClass( 'cs-hide' );
                }

                $( '.cs-action-buttons a', that.modal ).removeClass( 'current' );
                $( '.cs-action-buttons a', that.modal ).eq(that.current_step).addClass( 'current' );

            },

            _breadcrumb_actions: function(){
                var that = this;
                that.breadcrumb.on( 'click', function( e ){
                    e.preventDefault();
                    if ( ! that.doing ) {
                        var index = $(this).index();
                        if ($(this).hasClass('cs-clickable') && index !== that.current_step) {
                            that.current_step = index;
                            that.owl.trigger('to.owl.carousel', [index]);
                        }
                    }
                } );
            },

            is_activated: function( plugin_slug ){
                return _.isUndefined( Starter_Templates.activated_plugins[ plugin_slug ] ) ? false : true;
            },

            is_installed: function( plugin_slug ){
                return _.isUndefined( Starter_Templates.installed_plugins[ plugin_slug ] ) ? false : true;
            },

            step_completed: function( step, t ){
                var that = this;
                $( '.cs-step.cs-step-'+step, this.modal ).addClass('completed');
                this.completed_button( step );
                if ( _.isUndefined( t ) ) {
                    t = 2000;
                }
                setTimeout( function(){
                    that.next_step();
                }, t );
            },

            _do_start_import: function(){
                var that = this;

                // Skip or start import
                that.buttons.start.on( 'click', function( e  ) {
                    e.preventDefault();

                    if ( $( this ).hasClass( 'disabled' ) ) {
                        return;
                    }

                    if ( ! that.doing ) {
                        that.loading_button('start');
                        that.doing = true;
                        that.current_builder = $( '#starter-templates-filter-cat a.current' ).eq(0).attr( 'data-slug' ) || '';
                        var placeholder_only = $( 'input[name="import_placeholder_only"]', that.modal ).length > 0 ? $( 'input[name="import_placeholder_only"]', that.modal ).is(':checked') : false ;
                        $.ajax({
                            url: Starter_Templates.ajax_url,
                            dataType: 'json',
                            type: 'post',
                            data: {
                                action: 'cs_download_files',
                                resources: that.data.resources,
                                builder: that.current_builder,
                                site_slug: that.data.slug,
                                placeholder_only : placeholder_only
                            },
                            success: function (res) {
                                that.xml_id = res.xml_id;
                                that.json_id = res.json_id;
                                that.recommend_plugins = res._recommend_plugins;

                                if ( ! _.isObject( that.recommend_plugins ) ) {
                                    that.recommend_plugins = {};
                                }
                                if (that.xml_id <= 0) {
                                    that._reset();
                                    $('.cs-error-download-files', that.modal).removeClass('cs-hide');
                                    that.doing = false;
                                    that.buttons.start.find('.cs-btn-circle-text').text(Starter_Templates.try_again);
                                } else {
                                    _.each(res.texts, function (t, k) {
                                        $('.cs-' + k, that.modal).html(t);
                                    });

                                    that._install_plugins_notice();
                                    that._setup_plugins();

                                    that.step_completed('start');
                                }

                            }
                        });
                    } // end if doing
                } );
            },

            _installing_plugins: function() {
                var that = this;
                var list;
                var n_plugin_installed = 0;
                var n;

                that.buttons.install_plugins.on( 'click', function( e ){
                    e.preventDefault();
                    list = $( '.cs-installing-plugins li', that.modal );
                    n = list.length;
                    if ( n > 0 ) {
                        if (!that.doing) {
                            that.doing = true;
                            that.loading_button('install_plugins');
                            ajax_install_plugin();
                        }
                    } else {
                        that.step_completed( 'install_plugins' );
                    }
                } );

                var ajax_install_plugin = function () {
                    that.doing = true;
                    var plugin_data = list.eq(n_plugin_installed).attr( 'data-slug' ) || '';
                    if ( that.is_activated( plugin_data ) ){ // this plugin already installed
                        n_plugin_installed++;
                        if( n_plugin_installed < n ) {
                            ajax_install_plugin();
                        } else {
                            that.step_completed( 'install_plugins' );
                        }
                    } else if( that.is_installed( plugin_data ) ) {
                        ajax_active_plugin();
                    } else {
                        $( '.cs-installing-plugins li[data-slug="'+plugin_data+'"] .circle-loader', that.modal ).removeClass('load-complete').addClass('circle-loading');
                        $.ajax({
                            url: Starter_Templates.ajax_url,
                            data: {
                                action: 'cs_install_plugin',
                                plugin: plugin_data
                            },
                            success: function (res) {
                                ajax_active_plugin();
                            }
                        });
                    }

                };

                var ajax_active_plugin = function () {
                    that.doing = true;
                    var plugin_data = list.eq(n_plugin_installed).attr( 'data-slug' ) || '';
                    $( '.cs-installing-plugins li[data-slug="'+plugin_data+'"] .circle-loader', that.modal ).removeClass('load-complete').addClass('circle-loading');
                    $.ajax({
                        url: Starter_Templates.ajax_url,
                        data: {
                            action: 'cs_active_plugin',
                            plugin: plugin_data
                        },
                        success: function (res) {
                            n_plugin_installed++;
                            $( '.cs-installing-plugins li[data-slug="'+plugin_data+'"] .circle-loader', that.modal ).removeClass('circle-loading').addClass( 'load-complete' );
                            if( n_plugin_installed < n ) {
                                ajax_install_plugin();
                            } else {
                                that.step_completed( 'install_plugins' );
                            }
                        }
                    });
                };

            },

            _importing_content: function(){
                var that = this;

                $( '.cs-do-import-content', that.modal ).on( 'click', function( e ){
                    e.preventDefault();
                    if ( ! that.doing ) {
                        that.doing = true;
                        that.disable_button('import_content');
                        that.loading_button('import_content');
                        $('.cs-import-content-status .circle-loader', that.modal).addClass('circle-loading');

                        var cb = function(){
                            $.ajax({
                                url: Starter_Templates.ajax_url,
                                data: {
                                    action: 'cs_import_content',
                                    id: that.xml_id,

                                },
                                success: function (res) {
                                    console.log('Imported', res);
                                    $('.cs-import-content-status .circle-loader', that.modal).removeClass('circle-loading').addClass('load-complete');
                                    that.step_completed('import_content');
                                },
                                error: function (res) {
                                    console.log('Imported Error', res);
                                    $('.cs-import-content-status .circle-loader', that.modal).removeClass('circle-loading').addClass('load-complete');
                                    that.step_completed('import_content');
                                }
                            });
                        };

                        $.ajax({ // ajax_import__check
                            url: Starter_Templates.ajax_url,
                            data: {
                                action: 'ajax_import__check'
                            },
                            success: function (res) {
                                cb();
                            },
                            error: function (res) {
                                cb();
                            }
                        });



                    } // end if doing
                } );
            },

            _importing_options: function(){
                var that = this;
                var option_completed = function(){
                    that.step_completed('import_options');
                    $('.cs-import-options-status .circle-loader', that.modal).removeClass('circle-loading').addClass('load-complete');

                    // Clear cache and reset library
                    $.ajax({
                        url: Starter_Templates.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'elementor_clear_cache',
                            _nonce: Starter_Templates.elementor_clear_cache_nonce
                        }
                    });

                    $.ajax({
                        url: Starter_Templates.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'elementor_reset_library',
                            _nonce: Starter_Templates.elementor_reset_library_nonce
                        }
                    });

                };

                $( '.cs-do-import-options', that.modal ).on( 'click', function( e ){
                    e.preventDefault();
                    if ( ! that.doing ) {
                        that.doing = true;
                        that.disable_button('import_options');
                        that.loading_button('import_options');
                        $('.cs-import-options-status .circle-loader', that.modal).addClass('circle-loading');
                        $.ajax({
                            url: Starter_Templates.ajax_url,
                            data: {
                                action: 'cs_import_options',
                                id: that.json_id,
                                xml_id: that.xml_id
                            },
                            success: function (res) {
                                console.log('import_options', res);
                                option_completed();
                            },
                            error: function (res) {
                                console.log('import_options Error', res);
                                option_completed();
                            }
                        });
                    }
                } );

            }

        };

        return steps;
    };



    var Starter_Site = {
        data: {},
        filter_data: {},
        skip_render_filter: false,
        xhr: null,
        getTemplate: getTemplate,
        
        load_sites: function ( cb ) {
            var that = this;
            if ( that.xhr ) {
                //kill the request
                that.xhr.abort();
                that.xhr = null;
            }
            $( 'body' ).addClass('loading-content');
            $( '#starter-templates-listing-wrapper' ).hide();
            that.filter_data = that.get_filter_data();
            that.filter_data['_t'] = new Date().getTime();
            that.xhr = $.ajax( {
                url: Starter_Templates.api_url,
                data: that.filter_data,
                type: 'GET',
                success: function( res ){
                    that.data = res;
                    $( '#starter-templates-filter-count' ).text( res.total );
                    that.render_items();
                    if ( ! that.skip_render_filter ) {
                        that.render_categories( );
                        that.render_tags( );
                    }
                    $( 'body' ).removeClass('loading-content');
                    that.view_details();

                    if ( typeof cb === 'function' ) {
                        cb( res );
                    }
                }
            } );
        },

        render_items: function(){
            var that = this;
            var template = that.getTemplate();
            if ( that.data.total <= 0 ) {
                $( '#starter-templates-listing-wrapper' ).hide();
                $( '#starter-templates-no-demos' ).show();
                $( 'body' ).addClass('no-results');
            } else {
                $( '#starter-templates-no-demos' ).hide();
                $( '#starter-templates-listing-wrapper' ).show();
                $( 'body' ).removeClass('no-results');
            }
            $( '#starter-templates-listing .theme' ).remove();

            _.each( that.data.posts, function( item ) {
                // console.log( 'item', item );
                var html = template( item );
                var $item_html = $( html );
                $( '#starter-templates-listing' ).append( $item_html );
                var s = new Starter_Modal_Site( $item_html, item );
                s.add_modal();
                modal_sites[ item.slug ] = s;

            } );
        },

        render_categories: function(){
            var that = this;
            _.each( that.data.categories, function( item ){
                var html = '<li><a href="#" data-slug="'+item.slug+'">'+item.name+'</a></li>';
                $( '#starter-templates-filter-cat' ).append( html );
            } );
        },

        render_tags: function(){
            var that = this;
            _.each( that.data.tags, function( item ){
                var html = '<li><a href="#" data-slug="'+item.slug+'">'+item.name+'</a></li>';
                $( '#starter-templates-filter-tag' ).append( html );
            } );
        },


        get_filter_data: function(){
            var that = this;
            var cat = $( '#starter-templates-filter-cat a.current' ).eq(0).attr( 'data-slug' ) || '';
            var tag = $( '#starter-templates-filter-tag a.current' ).eq(0).attr( 'data-slug' ) || '';
            var s = $( '#starter-templates-search-input' ).val();
            if ( cat === 'all' ) {
                cat = '';
            }
            current_page_builder = _.clone( cat );
            if ( ! current_page_builder ) {
                current_page_builder = 'all';
            }
            that.current_builder = current_page_builder;
             return {
                cat: cat,
                tag: tag,
                builder: current_page_builder,
                s: s,
                license_key: ''
            }
        },

        filter: function(){
            var that = this;
            $( document ).on( 'click', '#starter-templates-filter-cat a', function( e ){
                e.preventDefault();
                if ( ! $( this ).hasClass( 'current' ) ) {
                    $('#starter-templates-filter-cat a').removeClass('current');
                    $('#starter-templates-filter-tag a').removeClass('current');
                    $(this).addClass('current');
                    that.filter_data = {};
                    that.filter_data = that.get_filter_data();

                    //console.log( 'that.filter_data',that.filter_data );

                    that.skip_render_filter = true;
                    that.load_sites();
                }
            } );

            $( document ).on( 'click', '#starter-templates-filter-tag a', function( e ){
                e.preventDefault();
                if ( ! $( this ).hasClass( 'current' ) ) {
                    $('#starter-templates-filter-tag a').removeClass('current');
                    $(this).addClass('current');
                    that.filter_data = that.get_filter_data();
                    that.skip_render_filter = true;
                    that.load_sites();
                }
            } );

            // Search demo
            $( document ).on( 'change keyup', '#starter-templates-search-input', function(){
                $( '#starter-templates-filter-cat a' ).removeClass( 'current' );
                $( '#starter-templates-filter-tag a' ).removeClass( 'current' );
                that.skip_render_filter = true;
                that.filter_data = that.get_filter_data();
                that.load_sites();

            } );

        },

        view_details: function(){
            // Test
            //$( 'body' ).addClass( 'starter-templates-show-modal' );
            /*
            $( document ).on( 'click', '#starter-templates-listing .theme', function( e ){
                e.preventDefault();

            } );

            $( '.cs-modal' ).each( function(){
                var s = new Starter_Modal_Site( $( this ) );
                s.init();
            } );
            */

        },

        init: function(){
            var that = this;
            that.filter_data = {};
            that.load_sites();
            that.filter();

        }
    };


    var Starter_Site_Preview = function(){
        var preview = {
            el: $( '#starter-template-preview' ),
            previewing: '',
            init: function(){
                var that = this;
                // open view
                $( document ).on( 'click', '.cs-open-preview', function( e ) {
                    e.preventDefault();
                    var slug = $( this ).attr( 'data-slug' ) || '';
                    console.log( 'Preview', slug );
                    if( ! _.isUndefined( Starter_Site.data.posts[ slug ] )  ) {
                        var data = Starter_Site.data.posts[ slug ];
                        that.previewing = data.slug;
                        $( '#starter-template-preview' ).removeClass( 'cs-iframe-loaded' );
                        that.el.find( '.cs-iframe iframe' ).attr( 'src', data.demo_url );
                        $( '.cs-iframe', that.el ).attr( 'data-device', '' );
                        $( '.cs-demo-name', that.el ).text( data.title );
                        that.el.removeClass( 'cs-hide' );
                    }
                } );


                // Device view
                $( document ).on( 'click', '.cs-device-view', function( e ) {
                    e.preventDefault();
                    $( '.cs-device-view' ).removeClass( 'current' );
                    $( this ).addClass( 'current' );
                    var device = $( this ).attr( 'data-device' ) || 'desktop';
                    $( '.cs-iframe', that.el ).attr( 'data-device', device );
                } );


                // Close
                var close = function(){
                    that.el.addClass( 'cs-hide' );
                    $( '.cs-iframe', that.el ).attr( 'data-device', '' );
                    that.el.find( '.cs-iframe iframe' ).attr( 'src', '' );
                    $( '#starter-template-preview' ).removeClass( 'cs-iframe-loaded' );
                };

                $( document ).on( 'click', '.cs-preview-close', function( e ) {
                    e.preventDefault();
                    close();
                } );

                $( window ).on( 'keydown', function( e ) {
                    if ( e.keyCode === 27 ){ // esc button
                        close();
                    }
                } );

                $( document ).on( 'click', '.cs-preview-nav', function( e ) {
                    e.preventDefault();
                    var action = $( this ).attr( 'data-action' ) || 'next';

                    var current_demo = $( '#starter-templates-listing .theme[data-slug="'+that.previewing+'"]' );
                    var $item;
                    if ( action === 'next' ) {
                        $item = current_demo.next();
                    } else {
                        $item = current_demo.prev();
                    }

                    if ( $item.length > 0 ) {
                        $( '.cs-open-preview', $item ).click();
                    }
                } );

                // Click import button
                $( document ).on( 'click', '.cs-preview-import', function( e ) {
                    e.preventDefault();
                    close();
                    var current_demo = $( '#starter-templates-listing .theme[data-slug="'+that.previewing+'"]' );
                    $('.cs-open-modal', current_demo ).click();
                } );




            }
        };

        return preview;
    };


    Starter_Site.init();
    Starter_Site_Preview().init();
    $( '#cs-preview-iframe' ).load( function(){
        $( '#starter-template-preview' ).addClass( 'cs-iframe-loaded' );
    } );


} );