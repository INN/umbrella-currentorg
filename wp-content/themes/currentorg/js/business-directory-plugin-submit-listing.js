// this is a CUSTOMIZED copy of /plugins/business-directory-plugin/assets/js/submit-listing.js
// we needed to be able to mess with the form interactions, hence the copied file
jQuery(function($) {

    $(document).ready(function(){
        $('.wpbdp-category-selection-with-tip').hide();
        $('.wpbdp-category-selection-with-tip .wpbdp-msg').hide();
        $('.wpbdp-plan-selection-wrapper').addClass('display-block-important');
        $('.wpbdp-plan-selection.wpbdp-plan-selection-with-tip').addClass('display-block-important');
        $('.wpbdp-plan-selection-wrapper').append($('.wpbdp-category-selection-with-tip'));
        $('.wpbdp-plan-price input').removeAttr("disabled");
        $('#wpbdp-submit-listing h2').text('Create a Listing').show();
    });

    // grab all wpbdp_category terms with the child tags from our rest endpoint
    var category_child_tags = $.get("/wp-json/currentorg/v1/wpbdp_categories", function(data, status){
        return data;
    });

    // empty arr of allowed tags
    var allowed_tags = [];

    // grab selected category
    $('#wpbdp-field-2').on('select2:select', function (e) {
        
        // grab category data and id
        var selected_category = e.params.data;
        var category_id = e.params.data.id;

        // find the index in our category_child_tags response that matches the id of the selected category
        var category_with_child_tags_index = category_child_tags.responseJSON.findIndex(function(category){
            return category.wpbdp_category_id == category_id;
        });

        // loop through each of the category child tags and add their names to the `allowed_tags` arr
        $.each(category_child_tags.responseJSON[category_with_child_tags_index]['wpbdp_category_child_tags'], function(index, tag){
            allowed_tags.push(tag.name);
        });

    });

    // when a category is removed, remove its child tags from `allowed_tags` arr
    $('#wpbdp-field-2').on('select2:unselect', function (e) {
        
        // grab category data and id
        var removed_category = e.params.data;
        var removed_category_id = e.params.data.id;

        // find the index in our category_child_tags response that matches the id of the selected category
        var category_with_child_tags_index = category_child_tags.responseJSON.findIndex(function(category){
            return category.wpbdp_category_id == removed_category_id;
        });

        // loop through each of the category child tags and remove their names to the `allowed_tags` arr
        $.each(category_child_tags.responseJSON[category_with_child_tags_index]['wpbdp_category_child_tags'], function(index, tag){
            allowed_tags.splice($.inArray(tag['name'], allowed_tags), 1);
        });

    });

    var last_valid_selected_tag = null;

    // when the listing plan input is selected, create a custom "continue" button
    $('input[name="listing_plan"]').on('click', function(){
        if($('.wpbdp-custom-continue').length == 0){
            $('.wpbdp-submit-listing-section-content').append('<button class="wpbdp-custom-continue">Continue</button>');
        }
    });

    var wpbdp = window.wpbdp || {};
    wpbdp.submit_listing = wpbdp.submit_listing || {};

    // Fee_Selection_Helper {{
    wpbdp.submit_listing.Fee_Selection_Helper = function( $submit, editing ) {
        this.editing = ( 'undefined' === typeof(editing) || ! editing ) ? false : true;
        this.reset();
    };
    $.extend( wpbdp.submit_listing.Fee_Selection_Helper.prototype, {
        reset: function() {
            this.field_wrapper = $( '.wpbdp-submit-listing-section-plan_selection .wpbdp-form-field-association-category' );
            this.field_type = '';
            this.plan_autoselect = false;

            $('.wpbdp-plan-selection-wrapper').append($('.wpbdp-category-selection-with-tip'));

            this.field_type = 'select2';
    
            this.field = this.field_wrapper.find( 'select, input[type="checkbox"], input[type="radio"]' );

            if ( ! this.field_type ) {
                // This shouldn't happen.
                return;
            }

            // First mark categories that were disabled since the beginning via HTML.
            if ( 'select2' == this.field_type ) {
                this.field.find( 'option[disabled="disabled"]' ).data( 'keep_disabled', true );
                // Workaround for https://github.com/select2/select2/issues/3992.
                var self = this;
                setTimeout(function() {
                    self.field.select2({placeholder: wpbdpSubmitListingL10n.categoriesPlaceholderTxt});
                } );
            }

            if ( this.editing ) {
                return;
            }

            this.skip_plan_selection = ( 1 == $( 'input[type="hidden"][name="skip_plan_selection"][value="1"]' ).length );
            if ( this.skip_plan_selection ) {
                return;
                // alert('skip plan');
                // this.field.change( function() {
                // } );
                // return;
            }

            this.$plans_container = $( '.wpbdp-plan-selection-wrapper' );
            this.$plan_selection = this.$plans_container.find( '.wpbdp-plan-selection' );
            this.plans = this.$plan_selection.find( '.wpbdp-plan' );

            this.$plan_selection.hide();

            this.selected_categories = [];
            this.available_plans = this.plans.map(function() {
                return $(this).data('id');
            }).get();

            this.field.change( $.proxy( this.categories_changed, this ) );
            this.maybe_limit_category_options();
            this.field.first().trigger('change');

            this.field.select2();

            // turn the tags field into a select2 dropdown
            $('#wpbdp-field-9').select2({
                width: '100%',
                containerCssClass: 'wpbdp-field-9-dropdown',
                maximumSelectionLength: localStorage.getItem("max_tags")
            });

            $('input[name="listing_plan"]').on('click', function(){
                if($('.wpbdp-custom-continue').length == 0){
                    $('.wpbdp-submit-listing-section-content').append('<button class="wpbdp-custom-continue">Continue</button>');
                }
            });
        },

        categories_changed: function() {
            this.selected_categories = [];

            if ( 'select2' === this.field_type ) {
                this.selected_categories = this.field.val();
            } else if ( 'checkbox' === this.field_type ) {
                this.selected_categories = this.field.filter( ':checked' ).map(function() {
                    return $( this ).val();
                }).get();
            } else if ( 'radio' === this.field_type ) {
                this.selected_categories = this.field.filter( ':checked' ).val();
            }

            if ( ! this.selected_categories ) {
                this.selected_categories = [];
            }

            if ( ! $.isArray( this.selected_categories ) )
                this.selected_categories = [this.selected_categories];

            if ( ! this.selected_categories ) {
                this.selected_categories = [];
            }

            this.selected_categories = $.map( this.selected_categories, function(x) { return parseInt( x ); } );

            this.update_plan_list();
            this.update_plan_prices();

            if ( 'checkbox' == this.field_type || this.field.is( '[multiple]' ) ) {
                this.maybe_limit_category_options();
            }

            if ( 0 == this.selected_categories.length ) {
                this.plans.find( 'input[name="listing_plan"]' ).prop( {
                    'disabled': 0 == this.selected_categories.length,
                    'checked': false
                } );
            } else {
                this.plans.find( 'input[name="listing_plan"]' ).prop( 'disabled', false );
            }

            var self = this;
            if ( this.selected_categories.length > 0 ) {
                this.$plans_container.show();
                Reusables.Breakpoints.evaluate();

                this.$plan_selection.fadeIn( 'fast' );
            } else {
                this.$plans_container.fadeOut( 'fast', function() {
                    self.$plan_selection.hide();
                } );
            }

            if ( this.available_plans.length === 1 && this.plan_autoselect ) {
                $( '#wpbdp-plan-select-radio-' + this.available_plans[0] ).trigger( "click" );
            }

            if ( ! this.plan_autoselect && 'checkbox' !== this.field_type && !$( this.field_wrapper ).hasClass( 'wpbdp-form-field-type-multiselect' ) ) {
                this.plan_autoselect = true;
            }

        },

        _enable_categories: function( categories ) {
            if ( 'none' != categories && 'all' != categories ) {
                this._enable_categories( 'none' );
            }

            if ( 'none' == categories || 'all' == categories ) {
                if ( 'select2' == this.field_type ) {
                    this.field.find( 'option' ).each(function(i, v) {
                        if ( true === $( this ).data( 'keep_disabled' ) ) {
                            // $( this ).prop( 'disabled', true );
                        } else {
                            $( this ).prop( 'disabled', ( 'all' == categories ) ? false : true );
                        }
                    });
                } else {
                    this.field.prop( 'disabled', ( 'all' == categories ) ? false : true );

                    if ( 'all' == categories ) {
                        this.field_wrapper.find( '.wpbdp-form-field-checkbox-item, .wpbdp-form-field-radio-item' ).removeClass( 'disabled' );
                    } else {
                        this.field_wrapper.find( '.wpbdp-form-field-checkbox-item, .wpbdp-form-field-radio-item' ).addClass( 'disabled' );
                    }
                }

                return;
            }

            if ( 'select2' == this.field_type ) {
                this.field.find( 'option' ).each(function(i, v) {
                    if ( true === $( this ).data( 'keep_disabled' ) ) {
                    } else {
                        $( this ).prop( 'disabled', -1 == $.inArray( parseInt( $( this ).val() ), categories ) );
                    }
                });
            } else {
                this.field.each(function(i, v) {
                    if ( -1 != $.inArray( parseInt( $( this ).val() ), categories ) ) {
                        $( this ).prop( 'disabled', false );
                        $( this ).parents().filter( '.wpbdp-form-field-checkbox-item, .wpbdp-form-field-radio-item' ).removeClass( 'disabled' );
                    }
                });
            }

        },

        maybe_limit_category_options: function() {
            var all_cats = false;
            var cats = [];
            var self = this;

            $.each(this.available_plans, function(i, v) {
                if ( all_cats )
                    return;

                var plan_cats = self.plans.filter('[data-id="' + v + '"]').data('categories');

                if ( 'all' == plan_cats ) {
                    all_cats = true;
                } else {
                    cats = $.unique( cats.concat( plan_cats.toString().split( ',' ) ) );
                    cats = $.map( cats, function(x) { return parseInt( x ); } );
                }
            });

            if ( all_cats ) {
                this._enable_categories( 'all' );
            } else {
                this._enable_categories( cats );
            }
        },

        update_plan_list: function() {
            var self = this;
            var plans = [];

            // Recompute available plans depending on category selection.
            $.each( this.plans, function( i, v ) {
                var $plan = $( v );
                var plan_cats = $plan.data('categories').toString();
                var plan_supports_selection = true;

                if ( 'all' != plan_cats && self.selected_categories ) {
                    plan_cats = $.map( plan_cats.split(','), function( x ) { return parseInt(x); } );

                    $.each( self.selected_categories, function( j, c ) {
                        if ( ! plan_supports_selection )
                            return;

                        if ( -1 == $.inArray( c, plan_cats ) ) {
                            plan_supports_selection = false;
                        }
                    } );
                }

                if ( plan_supports_selection ) {
                    plans.push( $plan.data('id') );
                    $plan.show();
                } else {
                    $plan.hide();
                }
            } );

            self.available_plans = plans;
        },

        update_plan_prices: function() {
            var self = this;

            $.each( self.available_plans, function( i, plan_id ) {
                var $plan = self.plans.filter('[data-id="' + plan_id + '"]');
                var pricing = $plan.data('pricing-details');
                var price = null;

                switch ( $plan.data( 'pricing-model' ) ) {
                    case 'variable':
                        price = 0.0;

                        $.each( self.selected_categories, function( j, cat_id ) {
                            price += parseFloat(pricing[cat_id]);
                        } );
                        break;
                    case 'extra':
                        price = parseFloat( $plan.data( 'amount' ) ) + ( parseFloat( pricing.extra ) * self.selected_categories.length );
                        break;
                    case 'flat':
                    default:
                        price = parseFloat( $plan.data( 'amount' ) );
                        break;
                }

                $plan.find( '.wpbdp-plan-price-amount' ).text( price ? $plan.data( 'amount-format' ).replace( '[amount]', price.toFixed(2) ) : $plan.data( 'free-text' ) );

                if ( self.available_plans.length === 1 ) {
                    $plan.find( '#wpbdp-plan-select-radio-' + plan_id ).prop( "checked", true );
                }

            } );
        }
    });

    wpbdp.submit_listing.Handler = function( $submit ) {
        this.$submit = $submit;
        this.$form = this.$submit.find( 'form' );
        this.editing = ( this.$form.find( 'input[name="editing"]' ).val() == '1' );
        this.$sections = this.$submit.find( '.wpbdp-submit-listing-section' );
        this.skip_plan_selection = ( 1 == $( 'input[type="hidden"][name="skip_plan_selection"][value="1"]' ).length );

        this.listing_id = this.$form.find( 'input[name="listing_id"]' ).val();
        this.ajax_url = this.$form.attr( 'data-ajax-url' );
        this.doing_ajax = false;

        this.setup_section_headers();
        this.plan_handling();

        var self = this;
        this.$form.on( 'click', ':reset', function( e ) {
            e.preventDefault();
            self.$form.find('input[name="save_listing"]').val( '' );
            self.$form.find('input[name="reset"]').val( 'reset' );
            self.$form.submit();
        } );

        $( window ).on( 'wpbdp_submit_refresh', function( event, submit, section_id ) {
            self.fee_helper.reset();
        } );

        // Create account form.
        $( '#wpbdp-submit-listing' ).on( 'change', '#wpbdp-submit-listing-create_account', function( e ) {
            $( '#wpbdp-submit-listing-account-details' ).toggle();
        } );

        $( '#wpbdp-submit-listing' ).on( 'keyup', '#wpbdp-submit-listing-account-details input[type="password"]', function( e ) {
            self.check_password_strength( $( this) );
        } );

        $( '#wpbdp-submit-listing' ).on( 'click', '.wpbdp-inner-field-option-select_all', function( e ) {
            var $options = $( this ).parent().find( 'input[type="checkbox"]' );
            $options.prop( 'checked', $( this ).find( 'input' ).is(':checked') );
        } );

        $submit.on( 'change', '.wpbdp-form-field-association-category .select2-selection ul', function ( e ) {
            if ( self.skip_plan_selection ) {
                var data = self.$form.serialize();
                data += '&action=wpbdp_ajax&handler=submit_listing__sections';
        
                self.ajax( data, function( res ) {
                    self.refresh( res );
                    $( 'html, body' ).delay(100).animate({
                        scrollTop: self.$form.find('.wpbdp-submit-listing-section-plan_selection').offset().top
                    }, 500);
                } );
            }
        } );

        $( window ).trigger( 'wpbdp_submit_init' );
    };
    $.extend( wpbdp.submit_listing.Handler.prototype, {
        ajax: function( data, callback ) {
            if ( this.doing_ajax ) {
                alert( wpbdpSubmitListingL10n.waitAMoment );
                return;
            }

            this.doing_ajax = true;
            var self = this;

            $.post( this.ajax_url, data, function( res ) {
                if ( ! res.success ) {
                    alert( wpbdpSubmitListingL10n.somethingWentWrong );
                    return;
                }

                self.doing_ajax = false;
                callback.call( self, res.data );
            }, 'json' );
        },

        setup_section_headers: function() {
            this.$sections.find( '.wpbdp-submit-listing-section-header' ).click(function() {
                var $section = $( this ).parent( '.wpbdp-submit-listing-section' );
                $section.toggleClass( 'collapsed' );
            });
        },

        plan_handling: function() {
            this.fee_helper = new wpbdp.submit_listing.Fee_Selection_Helper( this.$submit, this.editing );

            if ( this.editing ) {
                var $plan = this.$form.find( this.skip_plan_selection ? '.wpbdp-plan-selection .wpbdp-plan' : '.wpbdp-current-plan .wpbdp-plan' );
                var plan_cats = $plan.length ? $plan.data( 'categories' ).toString() : '';

                if ( 'all' != plan_cats ) {
                    var supported_categories = $.map( $.unique( plan_cats.split( ',' ) ), function(x) { return parseInt(x); } );
                    this.fee_helper._enable_categories( supported_categories );
                }

                return;
            }

            var self = this;
            this.$submit.on( 'change, click', 'input[name="listing_plan"], input[name="continue-to-fields"]', function( e ) {

                $('.wpbdp-category-selection-with-tip').show();

                // grab the selected plan
                var selected_plan = $(this).closest('.wpbdp-plan-info-box').index();
                
                // determine the maximum number of allowed categories for the selected plan
                if(selected_plan == 1){
                    max_categories = 2;
                    max_tags = 6;
                } else if(selected_plan == 2){
                    max_categories = 3;
                    max_tags = -1;
                } else {
                    max_categories = 1;
                    max_tags = 3;
                }

                // set max tags as a local item so we can use it later once the page refreshes if there's an error
                localStorage.setItem("max_tags", max_tags);

                // update the select2 with the new maximum number of categories
                $('#wpbdp-field-2').select2({
                    width: '100%',
                    maximumSelectionLength: max_categories
                });

                // grab selected categories and count how many currently are selected
                var selected_categories = $('#wpbdp-field-2').select2('data');
                var count_selected_categories = selected_categories.length;

                // if there are more selected categories than allowed, we need to remove some
                if(count_selected_categories > max_categories){
                    
                    // determine how many to remove
                    var remove_categories = count_selected_categories - max_categories;

                    // if excess categories still exist, remove them
                    for(remove_categories > 0; remove_categories--;){
                        $('.select2-selection__choice__remove:last-of-type').trigger('click');
                    }

                }

            } );

            // this is our custom "continue" button
            this.$submit.on( 'change, click', '.wpbdp-custom-continue', function( e ) {

                e.preventDefault();

                 if ( $( this ).parents( '.wpbdp-plan' ).attr( 'data-disabled' ) == 1 ) {
                    return false;
                }

                if( $('#wpbdp-field-2').select2('data') == '' ){
                    alert('You must select at least 1 category before continuing.');
                    return false;
                }

                var data = self.$form.serialize();
                data += '&action=wpbdp_ajax&handler=submit_listing__sections';

                self.ajax( data, function( res ) {
                    self.refresh( res );
                    $( 'html, body' ).delay(100).animate({
                        scrollTop: self.$form.find('.wpbdp-submit-listing-section-plan_selection').offset().top
                    }, 500);
                } );

            } );

            this.$submit.on( 'click', '#change-plan-link a', function(e) {
                e.preventDefault();

                var data = self.$form.serialize();
                data += '&action=wpbdp_ajax&handler=submit_listing__reset_plan';

                self.ajax( data, function( res ) {
                    self.refresh( res );
                } );
            }) ;
        },

        refresh: function(data) {
            var sections = data.sections;
            var messages = data.messages;

            var current_sections = this.$form.find( '.wpbdp-submit-listing-section' );
            var new_sections = sections;

            var self = this;

            // Update sections.
            $.each( new_sections, function( section_id, section_details ) {
                var $section = current_sections.filter( '[data-section-id="' + section_id + '"]' );
                var $new_html = $( section_details.html );

                $section.find( '.wpbdp-editor-area' ).each( function() {
                    wp.editor.remove( $( this ).attr( 'id' ) );
                } );

                $section.attr( 'class', $new_html.attr( 'class' ) );
                $section.find( '.wpbdp-submit-listing-section-content' ).fadeOut( 'fast', function() {
                    var $new_content = $new_html.find( '.wpbdp-submit-listing-section-content' );

                    $( this ).replaceWith( $new_content );

                    // Refresh things.
                    Reusables.Breakpoints.scan( $new_content );

                    $section.find( '.wpbdp-editor-area' ).each( function() {
                        var id = $( this ).attr( 'id' );
                        wp.editor.initialize( id, WPBDPTinyMCESettings[ id ] );
                    } );

                    $( window ).trigger( 'wpbdp_submit_refresh', [self, section_id, $section] );

                    // loop through each available tag and remove any
                    // not present in `allowed_tags`
                    $('#wpbdp-field-9 option').each(function(){
                        if(!allowed_tags.includes($(this).val())){
                            $(this).remove();
                        }
                    });
                    
                    // turn the tags field into a select2 dropdown
                    $('#wpbdp-field-9').select2({
                        width: '100%',
                        containerCssClass: 'wpbdp-field-9-dropdown',
                        maximumSelectionLength: max_tags
                    });

                } );
            } );
        },

        check_password_strength: function( $input ) {
            var pass = $input.val();
            var $result = $input.siblings( '.wpbdp-password-strength-meter' );

            $result.removeClass( 'strength-0 strength-2 strength-3 strength-4' )
                   .html('');

            if ( ! pass ) {
                return;
            }

            var strength = wp.passwordStrength.meter( pass, wp.passwordStrength.userInputBlacklist(), '' );
            var strength_msg = '';

            switch ( strength ) {
                case 2:
                    strength_msg = pwsL10n.bad;
                    break;
                case 3:
                    strength_msg = pwsL10n.good;
                    break;
                case 4:
                    strength_msg = pwsL10n.strong;
                    break;
                case 5:
                    strength_msg = pwsL10n.mismatch;
                    break;
                default:
                    strength_msg = pwsL10n.short;
                    break;
            }

            $result.addClass( 'strength-' + ( ( strength < 5 && strength >= 2 ) ? strength : '0' ) );
            $result.html( strength_msg );

        }

        // function checkPasswordStrength( $pass1,
        //                                 $pass2,
        //                                 $strengthResult,
        //                                 $submitButton,
        //                                 blacklistArray ) {
        //         var pass1 = $pass1.val();
        //     var pass2 = $pass2.val();
        //
        //     // Reset the form & meter
        //     $submitButton.attr( 'disabled', 'disabled' );
        //         $strengthResult.removeClass( 'short bad good strong' );
        //
        //     // Extend our blacklist array with those from the inputs & site data
        //     blacklistArray = blacklistArray.concat( wp.passwordStrength.userInputBlacklist() )
        //
        //     // Get the password strength
        //     var strength = wp.passwordStrength.meter( pass1, blacklistArray, pass2 );
        //
        //     // Add the strength meter results
        //     return strength;
        // }

    });

    var $submit = $( '#wpbdp-submit-listing' );

    $( window ).on( 'wpbdp_submit_init', function() {
        $submit.find( '.wpbdp-editor-area' ).each( function() {
            var id = $( this ).attr( 'id' );
            wp.editor.initialize( id, WPBDPTinyMCESettings[ id ] );
        } );
    } );

    if ( $submit.length > 0 )
        var x = new wpbdp.submit_listing.Handler( $submit );

});
