$(document).ready(function() {
    $.fn.extend({
        samsonSelect2: function(options) {
            options = $.extend({}, options);
            var $el = $(this);
            var url = undefined !== typeof(options.url) ? options.url : null;

            $(this).select2($.extend({
                placeholder: "",
                allowClear: true,
                initSelection: function(element, callback) {
                    callback({
                        id: this, 
                        text: element.data('display-value')
                    })
                },
                query: function(options) {
                    clearTimeout($el.data('timeout'));
                    
                    $el.data('timeout', setTimeout(function() {
                        if (typeof($el.data('ajax')) != 'undefined') {
                            $el.data('ajax').abort();
                        }
                        
                        var data = $el.closest('form').serialize();
                        data = data + "&__autocomplete_page_limit=" + 10;
                        data = data + "&__autocomplete_page=" + options.page;
                        data = data + "&__autocomplete_path=" + encodeURIComponent($el.attr('name'));
                        data = data + "&__autocomplete_search=" + encodeURIComponent(options.term);

                        $el.data('ajax', $.ajax({
                            url: url ? url : $el.closest('form').attr('action'),
                            dataType: 'json',
                            type: 'POST',
                            data: data,
                            beforeSend: function (request)
                            {
                                request.setRequestHeader("X-XSRF-TOKEN", $.cookie('XSRF-TOKEN') );
                            },
                            dataFilter: function(data, type ) {
                                if( data.substr(0,5) == ")]}'," ) {
                                    data = data.substr(6);
                                }
                                return data;
                            },
                            success: function(data) {
                                options.callback({
                                    results: data.results, 
                                    more: data.total > options.page * 10
                                });
                            },
                            error: function(xhr,b,c) {
                                console.log( xhr );
                                console.log( b );
                                console.log( c );
                                if (xhr.statusText == 'abort') {
                                    return;
                                }
                                $('.select2-searching').text('An error occurred! ');

                                var token = xhr.getResponseHeader('x-debug-token');
                                if (token) {
                                    $('.select2-searching').append($("<a>", { href: '/_profiler/'+token+"?panel=exception", target: '_blank' }).text('More info'));
                                }

                            }
                        }));
                    }, 100));

                },
                formatResult: function(object, container, query) {
                    return object.textHighlight;
                },
                dropdownCss: function() {
                    return {
                        width: 'auto', 
                        minWidth: $el.width()+'px'
                    }
                },
                dropdownCssClass: 'samson-autocomplete'
            }, options));
            if (!$(this).val() && $(this).data('display-value')) {
                $(this).select2('open')
                $(this).select2('container').find('input[type="text"]').val($(this).data('display-value')).trigger('keydown');
            }
        }
    });
});
