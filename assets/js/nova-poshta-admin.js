jQuery(document).ready(function () {
    var NovaPoshtaSettings = (function ($) {

        var result = {};
        var areaInputName = $('#woocommerce_nova_poshta_shipping_method_area_name');
        var areaInputKey = $('#woocommerce_nova_poshta_shipping_method_area');
        var cityAllInputName = $('#woocommerce_nova_poshta_shipping_method_city_all_name');
        var cityInputName = $('#woocommerce_nova_poshta_shipping_method_city_name');
        var cityInputKey = $('#woocommerce_nova_poshta_shipping_method_city');
        var warehouseInputName = $('#woocommerce_nova_poshta_shipping_method_warehouse_name');
        var warehouseInputKey = $('#woocommerce_nova_poshta_shipping_method_warehouse');

        var addressInputName = $('#woocommerce_nova_poshta_shipping_method_address_name');//1
        var addressInputKey = $('#woocommerce_nova_poshta_shipping_method_address');//1

        var useFixedPrice = $("#woocommerce_nova_poshta_shipping_method_use_fixed_price_on_delivery");
        var fixedPrice = jQuery("#woocommerce_nova_poshta_shipping_method_fixed_price");

        var handleUseFixedPriceOnDeliveryChange = function () {
            if (useFixedPrice.prop('checked')) {
                fixedPrice.closest('tr').show();
            } else {
                fixedPrice.closest('tr').hide();
            }
        };

        var initUseFixedPriceOnDelivery = function () {
            useFixedPrice.change(function () {
                handleUseFixedPriceOnDeliveryChange();
            });
            handleUseFixedPriceOnDeliveryChange();
        };

        var initAutocomplete = function () {
          if (typeof areaInputName.autocomplete !== "undefined") {

            areaInputName.autocomplete({
                source: function (request, response) {
                    jQuery.ajax({
                        type: 'POST',
                        url: NovaPoshtaHelper.ajaxUrl,
                        data: {
                            action: NovaPoshtaHelper.getRegionsByNameSuggestionAction,
                            name: request.term
                        },
                        success: function (json) {
                            console.log('response start');
                            console.log(json);
                            console.log('response ends');
                            var data = JSON.parse(json);
                            response(jQuery.map(data, function (description, key) {
                                return {
                                    label: description,
                                    value: key
                                }
                            }));
                        }
                    })
                },
                focus: function (event, ui) {
                    areaInputName.val(ui.item.label);
                    return false;
                },
                select: function (event, ui) {
                    areaInputName.val(ui.item.label);
                    areaInputKey.val(ui.item.value);
                    clearCity();
                    clearWarehouse();
                    return false;
                }
            });

            cityInputName.autocomplete({
                source: function (request, response) {
                    jQuery.ajax({
                        type: 'POST',
                        url: NovaPoshtaHelper.ajaxUrl,
                        data: {
                            action: NovaPoshtaHelper.getCitiesByNameSuggestionAction,
                            name: request.term,
                            parent_ref: areaInputKey.val()
                        },
                        success: function (json) {
                            var data = JSON.parse(json);
                            response(jQuery.map(data, function (description, key) {
                                return {
                                    label: description,
                                    value: key
                                }
                            }));
                        }
                    })
                },
                focus: function (event, ui) {
                    cityInputName.val(ui.item.label);
                    return false;
                },
                select: function (event, ui) {
                    cityInputName.val(ui.item.label);
                    cityInputKey.val(ui.item.value);
                    clearWarehouse();
                    return false;
                }
            });
            cityAllInputName.autocomplete({
                source: function (request, response) {
                    jQuery.ajax({
                        type: 'POST',
                        url: NovaPoshtaHelper.ajaxUrl,
                        data: {
                            action: NovaPoshtaHelper.getCitiesByNameSuggestionAction,
                            name: request.term,
                            parent_ref: areaInputKey.val()
                        },
                        success: function (json) {
                            var data = JSON.parse(json);
                            response(jQuery.map(data, function (description, key) {
                                return {
                                    label: description,
                                    value: key
                                }
                            }));
                        }
                    })
                },
                focus: function (event, ui) {
                    cityAllInputName.val(ui.item.label);
                    return false;
                },
                select: function (event, ui) {
                    cityAllInputName.val(ui.item.label);
                    cityInputKey.val(ui.item.value);
                    clearWarehouse();
                    return false;
                }
            });
            warehouseInputName.autocomplete({
                source: function (request, response) {
                    jQuery.ajax({
                        type: 'POST',
                        url: NovaPoshtaHelper.ajaxUrl,
                        data: {
                            action: NovaPoshtaHelper.getWarehousesBySuggestionAction,
                            name: request.term,
                            parent_ref: cityInputKey.val()
                        },
                        success: function (json) {
                          console.log(json);
                            var data = JSON.parse(json);
                            response(jQuery.map(data, function (description, key) {
                                return {
                                    label: description,
                                    value: key
                                }
                            }));
                        }
                    })
                },
                focus: function (event, ui) {
                    warehouseInputName.val(ui.item.label);
                    return false;
                },
                select: function (event, ui) {
                    warehouseInputName.val(ui.item.label);
                    warehouseInputKey.val(ui.item.value);
                    return false;
                }
            });
            addressInputName.autocomplete({
                source: function (request, response) {

                                     jQuery.ajax({
                                         type: 'POST',
                                         beforeSend: function(xhr) {
                                            xhr.setRequestHeader("Content-type", "application/json;charset=UTF-8");
                                          },
                                         url: 'https://api.novaposhta.ua/v2.0/json/',
                                          data:JSON.stringify({
                                              apiKey: jQuery('#npttnapikey').val(),
                                              modelName: "Address",
                                              calledMethod: "getStreet",
                                              methodProperties: {
                                                CityRef:jQuery("#woocommerce_nova_poshta_shipping_method_city").val(),
                                                Limit: 55555
                                              }
                                          }),
                                          success: function (json) {
                                            console.log(json);
                                              var data = json.data;
                                              response(jQuery.map(data, function (obj, key) {
                                                console.log(obj);
                                                //console.log(key);
                                                searchval = obj.StreetsType + "" +obj.Description;
                                                if( searchval.includes( jQuery('#woocommerce_nova_poshta_shipping_method_address_name').val() )){
                                                  return {
                                                      label: obj.StreetsType + " " +obj.Description,
                                                      value: obj.Ref
                                                  }
                                                }
                                              }));
                                          }
                                     })
                },
                focus: function (event, ui) {
                    addressInputName.val(ui.item.label);
                    return false;
                },
                select: function (event, ui) {
                    addressInputName.val(ui.item.label);
                    addressInputKey.val(ui.item.value);
                    return false;
                }
            });



          }
          else{
            console.log('autocompete undefined');
          }


        };

        var clearCity = function () {
            cityInputName.val('');
            cityInputKey.val('');
        };

        var clearWarehouse = function () {
            warehouseInputName.val('');
            warehouseInputKey.val('');
        };
        var clearAddress = function () {
            addressInputName.val('');
            addressInputKey.val('');
        };

        var hideKeyRows = function () {
            $('.js-hide-nova-poshta-option').closest('tr').addClass('nova-poshta-option-hidden');
        };

        var initRating = function () {
            $('a.np-rating-link').on('click', function () {
                var link = $(this);
                $.ajax({
                    type: 'POST',
                    url: NovaPoshtaHelper.ajaxUrl,
                    data: {
                        action: NovaPoshtaHelper.markPluginsAsRated
                    },
                    success: function (json) {
                        var data = JSON.parse(json);
                        if (data.result) {
                            link.parent().text(data.message);
                        }
                    }
                });
                return true;
            });
        };

        result.init = function () {
            initAutocomplete();
            hideKeyRows();
            initUseFixedPriceOnDelivery();
            initRating();
        };

        return result;

    }(jQuery));
    NovaPoshtaSettings.init();
});
