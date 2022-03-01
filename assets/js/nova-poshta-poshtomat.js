jQuery(document).ready(function() {
// if ('checkout' == window.location.pathname.replace(/\\|\//g, '')) { // On Checkout page only
    console.log('Поточний js-файл nova-poshta-poshtomat.js');
  jQuery('#billing_address_1').on("change", function() {
    //console.log('update');
    jQuery('body').trigger('update_checkout');
  });
  jQuery('#billing_city').on("change", function() {
    //console.log('update');
    jQuery('body').trigger('update_checkout');
  });
  jQuery('input[name^=shipping_method]').on("change", function() {
    //console.log('update');
    jQuery('body').trigger('update_checkout');
  });

  // 'Місто + Відділення (search in DB)'. The div-blocks where custom select elements will be added for.
  var billingMrkNpCity = jQuery("#billing_mrk_nova_poshta_city");
  var billingMrkNpWarehouse = jQuery("#billing_mrk_nova_poshta_warehouse");
  billingMrkNpCity.after('<div id="npdatafetch">Почніть вводити назву...</div>');
  billingMrkNpWarehouse.after('<div id="npdatafetchwh">Почніть ввод...</div>');
  // 'Місто' and 'Склад(№)' fields styling like Select2/3 (option: 'Місто + Відділення (search in DB)').
  billingMrkNpCity.css("color", "transparent");
  billingMrkNpCity.on('focus', function() {
      jQuery(this).val("");
      jQuery(this).css("color", "#444");
      jQuery("#npdatafetch").show(300);
  });
  billingMrkNpWarehouse.css("color", "transparent");
  billingMrkNpWarehouse.on('focus', function() {
      jQuery(this).val("");
      jQuery(this).css("color", "#444");
      jQuery("#npdatafetchwh").show(300);
  });
  billingMrkNpCity.on('keyup', function() {
      if( this.value.length < 3 ) return;
      jQuery('#npdatafetch').css({"border":"1px solid #aaa","border-radius":"4px","border-top-left-radius":"0","border-top-right-radius":"0"});
      billingMrkNpCity.css({"border-bottom-left-radius":"0","border-bottom-right-radius":"0"});
      jQuery('#cities-list .npcityli').css({"border-bottom-right-radius":"0","margin-bottom":".6em","padding":"6px auto"});
  });
  billingMrkNpWarehouse.on('keyup', function() {
      jQuery('#npdatafetchwh').css({"border":"1px solid #aaa","border-radius":"4px","border-top-left-radius":"0","border-top-right-radius":"0"});
      billingMrkNpWarehouse.css({"border-bottom-left-radius":"0","border-bottom-right-radius":"0"});
      jQuery('#warehouses-list .npwhli').css({"border-bottom-right-radius":"0","margin-bottom":".6em","padding":"6px auto"});
  });

  function updatenpdb() { // Update plugin DB tables (this function is disabled now)
    var data2 = {
      action: 'novaposhta_updbasesnp'
    };
    jQuery.post(NovaPoshtaHelper.ajaxUrl, data2, function(response) {
      //console.log(response);
    });
  }

  function calcdelivery() { //function to show calculated delivery price
    //console.log('calcdelivery');
    var data = {
      action: 'my_actionfogetnpshippngcost',
      c2: jQuery('#billing_nova_poshta_city').val(),
      cod: jQuery('#payment_method_cod').attr('checked')
    };

    jQuery.post(NovaPoshtaHelper.ajaxUrl, data, function(response) {
      jQuery('#shipcost').remove();
      if (!(response.includes('01234')) && (response != 0)) {
        jQuery('.order-total').after('<tr id=shipcost class=order-total><th>Розрахунок вартості доставки</th><td><strong><span class="woocommerce-Price-currencySymbol">₴</span>' + response + '<strong><input type="hidden" name=deliveryprice value="' + response + '"/ ></td></tr>')
        //console.log(response);
      } else {
        //console.log(response);
        //console.log('shipping calculation not available. ');
      }

    });
  }

  function calculated() { //function to remove  calcuated post
    jQuery('#shipcost').remove();
  }

  //function custom select3 matcher
  function matchCustom(params, data) { //matcher function
    // If there are no search terms, return all of the data
    if (jQuery.trim(params.term) === '') {
      return data;
    }

    // Do not display the item if there is no 'text' property
    if (typeof data.text === 'undefined') {
      return null;
    }

    // `params.term` should be the term that is used for searching
    // `data.text` is the text that is displayed for the data object
    var s = jQuery.trim(params.term).toLowerCase();
    var s2 = jQuery.trim(data.text).toLowerCase();
    if (s === s2.substr(0, s.length)) {
      return data;
    }

    // Return `null` if the term should not be displayed
    return null;
  }

  //custom select3 func
  function isNotEmpty(res) {
    return false;
  }

  //template result funtion to display city options
  function myTemplateResult(res) { //custom func
    if (res.loading) return res.text;
    res.id = res.id;
    if (isNotEmpty(res.code)) {
      return res.code + ":" + res.text;
    } else {
      return res.text;
    }
  }

  //custom select3 function
  function myTemplateSelection(res) {
    return res.text;
  }

  function callchangecountry() {
    jQuery('#billing_country').trigger('change', []);
  }

  var ischeckoutpage = document.getElementById("billing_nova_poshta_city");
  if (ischeckoutpage) { // adding event listeners for custom calculating

    //ask update db if not enough
    updatenpdb();

    //fix bad checkout on some sites by change country trigger if not work, increase timeout
    setTimeout(callchangecountry, 500);

    //add event listener calculate shipping if billing city changed
    jQuery('#billing_nova_poshta_city').on('change', function() {
      calcdelivery();
      city = jQuery("#billing_nova_poshta_city").val();
      // document.cookie = "city=" + city;
      let date = new Date(Date.now() + 86400e3);
      date = date.toUTCString();
      // var d = new Date();
      // d.setTime(d.getTime() + (exdays*24*60*60*1000));
      // var expires = "expires="+ d.toUTCString();
      document.cookie = "city" + "=" + city + ";" + date + ";path=/";
    });

    //add event listener to calculate shipping if payment method changed and delays to do if late
    jQuery('body').on('click', '.wc_payment_method', function() { //what to do if payment city changed
      //console.log('changed payment method');
      setTimeout(function() {
        calcdelivery();
      }, 20);
      setTimeout(function() {
        calcdelivery();
      }, 500);
      setTimeout(function() {
        calcdelivery();
      }, 4000);
    });

    jQuery('body').on('click', '.shipping_method', function() { // Add event listener what to do if shipping method changed
      //console.log('changed shipping method');
      //console.log(jQuery(this).val());
      if (jQuery(this).val() == 'nova_poshta_shipping_method_poshtomat' || jQuery(this).val() == 'nova_poshta_shipping_method') {
        calcdelivery();
        setTimeout(function() {
          calcdelivery();
        }, 500);
        setTimeout(function() {
          calcdelivery();
        }, 4000);
      } else {
        calculated();
      }

    });

    //selectize meta
    var wp_lang = jQuery("html").attr("lang"); // Get current site language id ('uk' or 'ru-RU')
    var selectizecityfield = { //selectize billing city field

      sorter: function(data) {
        data.sort(function(a, b) {
          var jQuerysearch = jQuery('.select3-search__field');
          if (0 === jQuerysearch.length || '' === jQuerysearch.val()) {
            return data;
          }
          var textA = a.text.toLowerCase(),
            textB = b.text.toLowerCase(),
            search = jQuerysearch.val().toLowerCase();
          if (textA.indexOf(search) < textB.indexOf(search)) {
            return -1;
          }
          if (textA.indexOf(search) > textB.indexOf(search)) {
            return 1;
          }
          return 0;
        });
        return data;
      },

      minimumInputLength: 2,

        language: {
            errorLoading: function() {
                if ('ru-RU' == wp_lang) return "Загрузка.";
                if ('uk' == wp_lang) return "Завантаження.";
            },
            inputTooShort: function() {
                if ('ru-RU' == wp_lang) return "Введите больше символов...";
                if ('uk' == wp_lang) return "Введіть більше символів...";
            },
            noResults: function() {
                if ('ru-RU' == wp_lang) return "Ничего не найдено";
                if ('uk' == wp_lang) return "Нічого не знайдено";
            },
            searching: function() {
                if ('ru-RU' == wp_lang) return "Поиск";
                if ('uk' == wp_lang) return "Пошук…";
            }
        }

    }

    if (document.getElementById("billing_nova_poshta_region")) {
      //console.log('region found doing select2');
      jQuery("#billing_nova_poshta_city").select2();
      jQuery("#shipping_nova_poshta_city").select2();

      jQuery("#billing_nova_poshta_warehouse").select2();
      //jQuery("#billing_nova_poshta_region").select3();
      jQuery("#shipping_nova_poshta_warehouse").select2();
      //jQuery("#shipping_nova_poshta_region").select3();

      jQuery("#billing_nova_poshta_region").on("change", function() {

        jQuery("#billing_nova_poshta_city").select2({
          sorter: function(data) {
            data.sort(function(a, b) {
              var jQuerysearch = jQuery('.select2-search__field');
              if (0 === jQuerysearch.length || '' === jQuerysearch.val()) {
                return data;
              }
              var textA = a.text.toLowerCase(),
                textB = b.text.toLowerCase(),
                search = jQuerysearch.val().toLowerCase();
              if (textA.indexOf(search) < textB.indexOf(search)) {
                return -1;
              }
              if (textA.indexOf(search) > textB.indexOf(search)) {
                return 1;
              }
              return 0;
            });
            return data;
          }
        });
        jQuery("#billing_nova_poshta_warehouse").select2("val", "");
      });

      jQuery("#billing_nova_poshta_city").on("change", function() {
        jQuery("#billing_nova_poshta_warehouse").select2("val", "");
      });
      jQuery("#shipping_nova_poshta_region").on("change", function() {
        jQuery("#shipping_nova_poshta_city").select2({

          sorter: function(data) {
            data.sort(function(a, b) {
              var jQuerysearch = jQuery('.select2-search__field');
              if (0 === jQuerysearch.length || '' === jQuerysearch.val()) {
                return data;
              }
              var textA = a.text.toLowerCase(),
                textB = b.text.toLowerCase(),
                search = jQuerysearch.val().toLowerCase();
              if (textA.indexOf(search) < textB.indexOf(search)) {
                return -1;
              }
              if (textA.indexOf(search) > textB.indexOf(search)) {
                return 1;
              }
              return 0;
            });
            return data;
          }
        });
        jQuery("#shipping_nova_poshta_warehouse").select2("val", "");
      });
      jQuery("#shipping_nova_poshta_city").on("change", function() {
        jQuery("#shipping_nova_poshta_warehouse").select2("val", "");
      });
    } else {
      //console.log('region not found doing select3');
      jQuery("#billing_nova_poshta_city").select3(selectizecityfield);
      jQuery("#shipping_nova_poshta_city").select3(selectizecityfield);

      jQuery("#billing_nova_poshta_warehouse").select3();
      //jQuery("#billing_nova_poshta_region").select2();
      jQuery("#shipping_nova_poshta_warehouse").select3();
      //jQuery("#shipping_nova_poshta_region").select2();

      jQuery("#billing_nova_poshta_region").on("change", function() {
        jQuery("#billing_nova_poshta_city").select3("val", "");
        jQuery("#billing_nova_poshta_warehouse").select3("val", "");
      });

      jQuery("#billing_nova_poshta_city").on("change", function() {
        jQuery("#billing_nova_poshta_warehouse").select3("val", "");
      });
      jQuery("#shipping_nova_poshta_region").on("change", function() {
        jQuery("#shipping_nova_poshta_city").select3("val", "");
        jQuery("#shipping_nova_poshta_warehouse").select3("val", "");
      });
      jQuery("#shipping_nova_poshta_city").on("change", function() {
        jQuery("#shipping_nova_poshta_warehouse").select3("val", "");
      });
    }
  } // if (ischeckoutpage)

  var NovaPoshtaOptions = (function(jQuery) { // Checkout Nova Poshta fields controls for warehouses, poshtomats, addresses shipping methods for WooComerce (WC)
    var result = {};

    var novaPoshtaBillingOptions = jQuery('#billing_nova_poshta_region, #billing_nova_poshta_city, #billing_nova_poshta_warehouse, #billing_mrk_nova_poshta_city, #billing_mrk_nova_poshta_warehouse');
    var billingAreaSelect = jQuery('#billing_nova_poshta_region');
    var billingCitySelect = jQuery('#billing_nova_poshta_city');
    var billingWarehouseSelect = jQuery('#billing_nova_poshta_warehouse');

    var novaPoshtaShippingOptions = jQuery('#shipping_phone, #shipping_nova_poshta_region, #shipping_nova_poshta_city, #shipping_nova_poshta_warehouse, #shipping_mrk_nova_poshta_city, #shipping_mrk_nova_poshta_warehouse');
    var shippingAreaSelect = jQuery('#shipping_nova_poshta_region');
    var shippingCitySelect = jQuery('#shipping_nova_poshta_city');
    var shippingWarehouseSelect = jQuery('#shipping_nova_poshta_warehouse');

    var shippingMethods = 'nova_poshta_shipping_method';
    var shippingMethodsPoshtomat = 'nova_poshta_shipping_method_poshtomat';
    var shippinglocalpickup = 'wcso_local_shipping';

    var defaultBillingOptions = jQuery('#billing_company, #billing_address_1, #billing_address_2, #billing_city, #billing_state, #billing_postcode');
    var defaultShippingOptions = jQuery('#shipping_company, #shipping_address_1, #shipping_address_2, #shipping_city, #shipping_state, #shipping_postcode');

    var shippingMethod = jQuery("input[name^=shipping_method]");
    var shipToDifferentAddressCheckbox = jQuery('#ship-to-different-address-checkbox');

    var shipToDifferentAddress = function() {
      return shipToDifferentAddressCheckbox.is(':checked');
    };

    var ensureNovaPoshta = function() {
      //TODO this method should be more abstract
      var value = jQuery('input[name^=shipping_method][type=radio]:checked').val();
      if (!value) {
        value = jQuery('input#shipping_method_0').val();
      }
      if (!value) {
        value = jQuery('input[name^=shipping_method][type=hidden]').val();
      }
      if (!value) {
        jQuery.post(NovaPoshtaHelper.ajaxUrl, {
          action: 'my_action_for_wc_get_chosen_method_ids'
        }, function(response) {
          value = response;
        });
      }
      if ((jQuery('#billing_country').val())) {
        ////console.log( jQuery('#billing_country').val() );
        if (jQuery('#billing_country').val() != 'UA') {
          //console.log('185: return false')
          return false;
        }
      }
      if (!value) {
        return true;
      }
      if (value.includes('poshtomat')) {
          return value === 'nova_poshta_shipping_method_poshtomat';
      } else {
          return value === 'nova_poshta_shipping_method';
      }
    };

    //billing
    var enableNovaPoshtaBillingOptions = function() {
      //console.log('enableNovaPoshtaBillingOptionsg');
      novaPoshtaBillingOptions.each(function() {
        jQuery(this).removeAttr('disabled').closest('.form-row').show();
      });
      disableDefaultBillingOptions();
    };

    var disableNovaPoshtaBillingOptions = function() {
      novaPoshtaBillingOptions.each(function() {
        jQuery(this).attr('disabled', 'disabled').closest('.form-row').hide();
      });

      let currentShippingMethod = getNovaPoshta() ? getNovaPoshta() : '';
      let shippingMethodToAddDefaultBillingOptions = ['free_shipping', 'npttn_address_shipping_method', 'flat_rate', 'local_pickup'];
      let isAddDefaultFields = false;
      for (const element of shippingMethodToAddDefaultBillingOptions) {
        if (currentShippingMethod.includes(element)) {
          isAddDefaultFields = true;
          break;
        }
      }
      if (isAddDefaultFields) {
        enableDefaultBillingOptions();
      }
    };

    var enableDefaultBillingOptions = function() {
      //console.log('enableDefaultBillingOptions');
      if ( !(shippingMethods.includes(getNovaPoshta())) || !(shippingMethodsPoshtomat.includes(getNovaPoshta())) ) {

        var strladr = 'npttn_address_shipping_method';
        var str1 = getNovaPoshta() || '1';

        if ((str1.includes(strladr))) {
          //console.log('address_trigger');
          // jQuery('#billing_address_2_field').attr('disabled', 'disabled').css('display', 'none');
          jQuery('#billing_state_field').attr('value', '_').css('display', 'none');
          jQuery('#billing_postcode_field').attr('disabled', 'disabled').css('display', 'none');
          jQuery('#billing_address_1').removeAttr('disabled').css('display', 'block').closest('.form-row').show();
          jQuery('#billing_city').removeAttr('disabled').css('display', 'block').closest('.form-row').show();
        } else {
          //console.log('shipping method not nova poshta');
          defaultBillingOptions.each(function() {
            jQuery(this).removeAttr('disabled').closest('.form-row').show();
          });
        }
      }
    };

    var disableDefaultBillingOptions = function() {
      if ( ensureNovaPoshta() ) {
        //console.log('disable default billiing options');
        defaultBillingOptions.each(function() {
          //console.log(this);
          jQuery(this).attr('disabled', 'disabled').closest('.form-row').hide();
        });
      } else {
        //console.log('shippingMethods notincludes');
      }
    };

    var getNovaPoshta = function() {
      //console.log('getNovaPoshta');
      var value = jQuery('input[name^=shipping_method][type=radio]:checked').val();
      if (!value) {
        value = jQuery('input#shipping_method_0').val();
      }
      if (!value) {
        value = jQuery('input[name^=shipping_method][type=hidden]').val();
      }
      //console.log(value);
      return value;
    };

    //shipping
    var enableNovaPoshtaShippingOptions = function() {
      novaPoshtaShippingOptions.each(function() {
        jQuery(this).removeAttr('disabled').closest('.form-row').show();
      });
      disableDefaultBillingOptions();
      disableDefaultShippingOptions();
    };

    var disableNovaPoshtaShippingOptions = function() {
      novaPoshtaShippingOptions.each(function() {
        jQuery(this).attr('disabled', 'disabled').closest('.form-row').hide();
      });
      enableDefaultShippingOptions();
    };

    var enableDefaultShippingOptions = function() {
      defaultShippingOptions.each(function() {
        jQuery(this).removeAttr('disabled').closest('.form-row').show();
      });
    };

    var disableDefaultShippingOptions = function() {
      defaultShippingOptions.each(function() {
        jQuery(this).attr('disabled', 'disabled').closest('.form-row').hide();
      });
      jQuery('#shipping_phone').attr('disabled', 'disabled').closest('.form-row').hide();
    };

    //common
    var disableNovaPoshtaOptions = function() {
      disableNovaPoshtaBillingOptions();
      disableNovaPoshtaShippingOptions();
    };

    var handleShippingMethodChange = function() {
      disableNovaPoshtaOptions();
      if ( ensureNovaPoshta() ) {
        if (shipToDifferentAddress()) {
          enableNovaPoshtaShippingOptions();
        } else {
          enableNovaPoshtaBillingOptions();
        }
      }
    };

    var initShippingMethodHandlers = function() {
      //TODO check count of call of this method during initialisation and other actions
      jQuery(document).on('change', shippingMethod, function() {
        handleShippingMethodChange();
      });
      jQuery(document).on('change', shipToDifferentAddressCheckbox, function() {
        handleShippingMethodChange();
      });
      // jQuery(document.body).bind('updated_checkout', function() {
      jQuery(document.body).on('updated_checkout', function() {
        handleShippingMethodChange();
      });
      handleShippingMethodChange();
    };

    var initOptionsHandlers = function() {
      billingAreaSelect.on('change', function() { // Billing '3fields': 'Область + Місто + Відділення'
        //console.log('billing area change');
        var areaRef = this.value;
        jQuery.ajax({
          url: NovaPoshtaHelper.ajaxUrl,
          method: "POST",
          data: {
            'action': NovaPoshtaHelper.getCitiesAction,
            'parent_ref': areaRef
          },
          success: function(json) {
            try {
              var data = JSON.parse(json);
              billingCitySelect
                .find('option:not(:first-child)')
                .remove();

              jQuery.each(data, function(key, value) {
                billingCitySelect
                  .append(jQuery("<option></option>")
                    .attr("value", key)
                    .text(value)
                  );
              });
              billingWarehouseSelect.find('option:not(:first-child)').remove();

            } catch (s) {
              //console.log("Error. Response from server was: " + json);
            }
          },
          error: function() {
            //console.log('Error.');
          }
        });
      });
      billingCitySelect.on('change', function() {
          var cityRef = this.value;
        console.log('getNovaPoshta= '+getNovaPoshta());
        var dataObj = {
            'action': NovaPoshtaHelper.getWarehousesAction,
            'parent_ref': cityRef
        }; // console.log('if Warehouse');
        if ( getNovaPoshta().indexOf('poshtomat') > -1 ) { // console.log('if Poshtomat');
            dataObj = {
                'action': NovaPoshtaHelper.getPoshtomatsAction,
                'parent_ref': cityRef
            };
        }
        jQuery.ajax({
          url: NovaPoshtaHelper.ajaxUrl,
          method: "POST",
          data: dataObj,
          success: function(json) {
            try {
              var data = JSON.parse(json);
              billingWarehouseSelect
                .find('option:not(:first-child)')
                .remove();

              jQuery.each(data, function(key, value) {
                billingWarehouseSelect
                  .append(jQuery("<option></option>")
                    .attr("value", key)
                    .text(value)
                  );
              });

            } catch (s) {
              console.log("Error. Response from server was: " + json);
            }
          },
          error: function() {
            console.log('Error.');
          }
        });
      });

      shippingAreaSelect.on('change', function() {
        var areaRef = this.value;
        jQuery.ajax({
          url: NovaPoshtaHelper.ajaxUrl,
          method: "POST",
          data: {
            'action': NovaPoshtaHelper.getCitiesAction,
            'parent_ref': areaRef
          },
          success: function(json) {
            try {
              var data = JSON.parse(json);
              shippingCitySelect
                .find('option:not(:first-child)')
                .remove();

              jQuery.each(data, function(key, value) {
                shippingCitySelect
                  .append(jQuery("<option></option>")
                    .attr("value", key)
                    .text(value)
                  );
              });
              shippingWarehouseSelect.find('option:not(:first-child)').remove();

            } catch (s) {
              //console.log("Error. Response from server was: " + json);
            }
          },
          error: function() {
            //console.log('Error.');
          }
        });
      });
      shippingCitySelect.on('change', function() {
        var cityRef = this.value;console.log('if Warehouse2');
        jQuery.ajax({
          url: NovaPoshtaHelper.ajaxUrl,
          method: "POST",
          data: {
            'action': NovaPoshtaHelper.getPoshtomatsAction,
            'parent_ref': cityRef
          },
          success: function(json) {
            try {
              var data = JSON.parse(json);
              shippingWarehouseSelect
                .find('option:not(:first-child)')
                .remove();

              jQuery.each(data, function(key, value) {
                shippingWarehouseSelect
                  .append(jQuery("<option></option>")
                    .attr("value", key)
                    .text(value)
                  );
              });

            } catch (s) {
              //console.log("Error. Response from server was: " + json);
            }
          },
          error: function() {
            //console.log('Error.');
          }
        });
      });
    };

    result.init = function() {
      initShippingMethodHandlers();
      initOptionsHandlers();
    };

    return result;
  }(jQuery));
  var Calculator = (function(jQuery) {
    var result = {};

    var ensureNovaPoshta = function() {
      var value = jQuery('input[name^=shipping_method][type=radio]:checked').val();
      if (!value) {
          value = jQuery('input#shipping_method_0').val();
      }
      if (typeof value != "undefined") {
          if (value.includes('poshtomat')) {
              return value === 'nova_poshta_shipping_method_poshtomat';
          } else {
              return value === 'nova_poshta_shipping_method';
          }
      }
    };

    var addNovaPoshtaHandlers = function() {
      jQuery('#calc_shipping_country').find('option').each(function() {
        //Ship to Ukraine only
        if (jQuery(this).val() !== 'UA') {
          jQuery(this).remove();
        }
      });
      jQuery('#calc_shipping_state_field').hide();

      // var shippingMethod = jQuery('<input type="hidden" id="calc_nova_poshta_shipping_method_poshtomat" value="nova_poshta_shipping_method_poshtomat" name="shipping_method">');
      var shippingMethod = jQuery('<input type="hidden" id="calc_' +
          ensureNovaPoshta() + '" value="' + ensureNovaPoshta() + '" name="shipping_method">');
      var cityInputKey = jQuery('<input type="hidden" id="calc_nova_poshta_shipping_city" name="calc_nova_poshta_shipping_city">');
      jQuery('#calc_shipping_city_field').append(cityInputKey).append(shippingMethod);
      var cityInputName = jQuery('#calc_shipping_city');

      cityInputName.autocomplete({
        source: function(request, response) {
          jQuery.ajax({
            type: 'POST',
            url: NovaPoshtaHelper.ajaxUrl,
            data: {
              action: NovaPoshtaHelper.getCitiesByNameSuggestionAction,
              name: request.term
            },
            success: function(json) {
              var data = JSON.parse(json);
              response(jQuery.map(data, function(item, key) {
                return {
                  label: item,
                  value: key
                }
              }));
            }
          })
        },
        focus: function(event, ui) {
          cityInputName.val(ui.item.label);
          return false;
        },
        select: function(event, ui) {
          cityInputName.val(ui.item.label);
          cityInputKey.val(ui.item.value);
          return false;
        }
      });

      jQuery('form.woocommerce-shipping-calculator').on('submit', function() {
        if (jQuery('#calc_shipping_country').val() !== 'UA') {
          return false;
        }
      });
    };

    result.init = function() {
      jQuery(document.body).on('updated_wc_div updated_shipping_method', function() {
        if (ensureNovaPoshta()) {
          addNovaPoshtaHandlers();
        }
      });

      if (ensureNovaPoshta()) {
        addNovaPoshtaHandlers();
      }
    };

    return result;
  }(jQuery));

  NovaPoshtaOptions.init();
  Calculator.init();

// } // if ('checkout' == window.location.pathname.replace(/\\|\//g, ''))
}); // jQuery(document).ready(function()
