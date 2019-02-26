var rvalin_translation = {
    tag: 'trans', // html tag used for the translations
    pluralSeparator: '|',
    translations: [],
    current_id: null,
    route_update: null,
    use_textarea: true,
    live_translation: true,

    init: function(translations, route_update, use_textarea, liveTranslation) {
        this.live_translation = liveTranslation;
        if(liveTranslation) {
            this.initEvents();
        }
        this.initToggleLiveTranslationButton();

        this.translations = JSON.parse(translations);
        this.route_update = route_update;
        this.use_textarea = use_textarea;
    },

    getPopupHTML: function(title, content, hasButtonFull) {
        hasButtonFull = (hasButtonFull === undefined) ? false : hasButtonFull;

        popup = '<div id="r_valin_translation_info">' +
            '<header>' + title + ' <span id="r_valin_translation_status"></span>'
        ;

        if(hasButtonFull) {
            popup += '<svg id="r_valin_fullscreen" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 18 18"><path d="M4.5 11H3v4h4v-1.5H4.5V11zM3 7h1.5V4.5H7V3H3v4zm10.5 6.5H11V15h4v-4h-1.5v2.5zM11 3v1.5h2.5V7H15V3h-4z"/></svg>';
        } else {
            popup += '<span class="close-popup">X</span>';
        }

        popup += '</header><section>' + content + '</section></div>'

        return popup;
    },

    initToggleLiveTranslationButton: function() {
        $('#rvalin-toggle-live-translation').change(function() {
            console.log('change');
            if($(this).is(':checked')) {
                console.log('unbind');
                rvalin_translation.unbindEvents();
            } else {
                console.log('bind');
                rvalin_translation.initEvents();
            }
        }).change();
    },

    getUpdateLink: function() {
        baseUrl = [location.protocol, '//', location.host, location.pathname].join('');
        urlQueryString = document.location.search;
        var newParam = 'update_translation=' + (this.live_translation ? '0': '1'),
            params = '?' + newParam;

        if (urlQueryString) {
            keyRegex = new RegExp('([\?&])update_translation[^&]*');
            // If param exists already, update it
            if (urlQueryString.match(keyRegex) !== null) {
                params = urlQueryString.replace(keyRegex, "$1" + newParam);
            } else { // Otherwise, add it to end of query string
                params = urlQueryString + '&' + newParam;
            }
        }
        return baseUrl + params;
    },

    initCancelButton: function() {
        $("#rvalin_translation-cancel").click(rvalin_translation.hideInfo);
    },

    initSaveButton: function() {
        $("#rvalin_translation-save").click(rvalin_translation.saveTranslation);
    },

    saveTranslation: function() {
        var id = rvalin_translation.current_id;
        if(!id || !rvalin_translation.translations[id]) {
            console.error('No translation selected');
        }
        var translation = rvalin_translation.translations[id];

        $.ajax({
            url: rvalin_translation.route_update,
            method: 'post',
            data: {
                key: translation.key,
                domain: translation.domain,
                locale: translation.locale,
                translationCode: rvalin_translation.getCurrentTranslationCode(id),
            },
            dataType: 'json'
        }).done(function(data) {
            rvalin_translation.updateStatus(id, 'saved');
        }).fail(function() {
            rvalin_translation.updateStatus(id, 'failed');
        });
    },

    updateStatus: function(id, status) {
        var message = '';
        switch (status) {
            case 'hasChange':
                message = 'Has change';
                break;
            case 'saved':
                message = 'Change saved';
                rvalin_translation.translations[id].translationCode = rvalin_translation.getCurrentTranslationCode(id);
                break;
            case 'failed':
                message = 'Save failed';
                break;
        }

        if(message) {
            $('#r_valin_translation_status').text("("+message+")");
        }

        if(status == 'saved') {
            rvalin_translation.hideInfo();
        }
    },

    events: {
        blur: function(){
            var transId = $(this).data('id');
            var translation = $(this).html();
            rvalin_translation.updateTranslation(transId, translation);
            rvalin_translation.updateText();
        },
        clickAndFocus: function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            var transId = $(this).data('id');

            if($(this).attr('contenteditable')) {
                $(this).html(rvalin_translation.getCurrentTranslationCode(transId));
            }
            rvalin_translation.showInfo(transId);
        }
    },

    initEvents: function() {
        $(this.tag).blur(rvalin_translation.events.blur);
        $(this.tag).click(rvalin_translation.events.clickAndFocus);
        $(this.tag).focus(rvalin_translation.events.clickAndFocus);
    },

    unbindEvents: function() {
        $(this.tag).unbind('blur', rvalin_translation.events.blur);
        $(this.tag).unbind('click',rvalin_translation.events.clickAndFocus);
        $(this.tag).unbind('focus', rvalin_translation.events.clickAndFocus);
    },

    translate: function(id) {
        var translation = rvalin_translation.translations[id];

        var translationCode = rvalin_translation.getCurrentTranslationCode(id);
        var message = translationCode;
        if(translation.plural) {
            message = rvalin_translation.pluralize(translationCode, translation.number, translation.locale);
        }

        return rvalin_translation.replace_placeholders(message, translation.parameters);
    },

    showInfo: function(id) {
        rvalin_translation.hideInfo();
        rvalin_translation.current_id = id;
        var translation = rvalin_translation.translations[id];

        var allowPluralize = "false";
        if (translation.plural) {
            allowPluralize = "true";
        }

        var div = '<div class="section-title">Information</div>' +
            '<div class="info"><span class="info-title">Key:</span> ' + translation.key +'</div>' +
            '<div class="info"><span class="info-title">Domain:</span> ' + translation.domain + '</div>' +
            '<div class="info"><span class="info-title">Pluralize:</span> ' + allowPluralize + '</div>'
        ;

        if (translation.plural) {
            div += '<div class="info"><span class="info-title">Number:</span> ' + translation.number + '</div>';
        }

        div += '<div class="section-title">Translation</div>';
        if (rvalin_translation.use_textarea) {
            div += '<textarea rows="4" id="r_valin_translation_edit"> ' + translation.translationCode + '</textarea>';
        } else {
            div += '<div> ' +translation.translationCode + '</div>';
        }


        if (Object.keys(translation.parameters).length > 0) {
            div += '<div class="section-title">Parameters</div>';
            for (var key in translation.parameters) {
                div += '<div class="info"><span class="info-title">${key}</span> ' + translation.parameters[key] + '</div>';
            }
        }

        div += '<div class="button-container"><button id="rvalin_translation-cancel">Cancel</button>' +
            '<button id="rvalin_translation-save">Save</button>' +
            '</div>'
        ;

        $(rvalin_translation.getPopupHTML('Edit this translation', div, true)).appendTo($('body'));
        rvalin_translation.initTextarea();
        rvalin_translation.initCancelButton();
        rvalin_translation.initSaveButton();
        rvalin_translation.initFullscreen();

        if(rvalin_translation.translations[id].translationCode !== rvalin_translation.getCurrentTranslationCode(id)) {
            rvalin_translation.updateStatus(id, 'hasChange');
        }
    },

    initFullscreen: function() {
        $('#r_valin_fullscreen').click(function(){
            $('#r_valin_translation_info').toggleClass('fullscreen');

            var rows = 4;
            if($('#r_valin_translation_info').hasClass('fullscreen')) {
                rows = 10;
            }

            $('#r_valin_translation_edit').prop('rows', rows);
        })
    },

    getCurrentTranslationCode: function(id) {
        if(rvalin_translation.translations[id].translationCodeUpdated) {
            return rvalin_translation.translations[id].translationCodeUpdated;
        }

        return rvalin_translation.translations[id].translationCode;
    },

    updateCurrentTranslationCode: function(id, translationCode) {
        rvalin_translation.translations[id].translationCodeUpdated = translationCode;
        rvalin_translation.updateStatus(id, 'hasChange');
    },

    initTextarea: function() {
        $('#r_valin_translation_edit').change(function() {
            rvalin_translation.updateCurrentTranslationCode(rvalin_translation.current_id, $(this).val());
            rvalin_translation.updateText();
        });
    },

    updateText: function() {
        $(rvalin_translation.tag+'[data-id="'+rvalin_translation.current_id+'"]').html(rvalin_translation.translate(rvalin_translation.current_id));
        $('#r_valin_translation_edit').val(rvalin_translation.getCurrentTranslationCode(rvalin_translation.current_id));
    },


    hideInfo: function() {
        $('#r_valin_translation_info').remove();
    },

    updateTranslation: function(id, translation) {
        translation = $('<div/>').html(translation).html();
        rvalin_translation.updateCurrentTranslationCode(id, translation);
    },

    /**
     * Replace placeholders in given message.
     *
     * **WARNING:** used placeholders are removed.
     *
     * @param {String} message      The translated message
     * @param {Object} placeholders The placeholders to replace
     * @return {String}             A human readable message
     * @api private
     */
    replace_placeholders: function(message, placeholders) {
        for (var _i in placeholders) {
            var _r = new RegExp(_i, 'g');

            if (_r.test(message)) {
                var _v = String(placeholders[_i]).replace(new RegExp('\\$', 'g'), '$$$$');
                message = message.replace(_r, _v);
            }
        }

        return message;
    },

    pluralize: function(message, number, locale) {
        var _p,
            _e,
            _explicitRules = [],
            _standardRules = [],
            _parts         = message.split(rvalin_translation.pluralSeparator),
            _matches       = [],
            _sPluralRegex = new RegExp(/^\w+\: +(.+)$/),
            _cPluralRegex = new RegExp(/^\s*((\{\s*(\-?\d+[\s*,\s*\-?\d+]*)\s*\})|([\[\]])\s*(-Inf|\-?\d+)\s*,\s*(\+?Inf|\-?\d+)\s*([\[\]]))\s?(.+?)$/),
            _iPluralRegex = new RegExp(/^\s*(\{\s*(\-?\d+[\s*,\s*\-?\d+]*)\s*\})|([\[\]])\s*(-Inf|\-?\d+)\s*,\s*(\+?Inf|\-?\d+)\s*([\[\]])/);

        for (_p = 0; _p < _parts.length; _p++) {
            var _part = _parts[_p];

            if (_cPluralRegex.test(_part)) {
                _matches = _part.match(_cPluralRegex);
                _explicitRules[_matches[0]] = _matches[_matches.length - 1];
            } else if (_sPluralRegex.test(_part)) {
                _matches = _part.match(_sPluralRegex);
                _standardRules.push(_matches[1]);
            } else {
                _standardRules.push(_part);
            }
        }

        for (_e in _explicitRules) {
            if (_iPluralRegex.test(_e)) {
                _matches = _e.match(_iPluralRegex);

                if (_matches[1]) {
                    var _ns = _matches[2].split(','),
                        _n;

                    for (_n in _ns) {
                        if (number == _ns[_n]) {
                            return _explicitRules[_e];
                        }
                    }
                } else {
                    var _leftNumber  = convert_number(_matches[4]);
                    var _rightNumber = convert_number(_matches[5]);

                    if (('[' === _matches[3] ? number >= _leftNumber : number > _leftNumber) &&
                        (']' === _matches[6] ? number <= _rightNumber : number < _rightNumber)) {
                        return _explicitRules[_e];
                    }
                }
            }
        }

        return _standardRules[rvalin_translation.plural_position(number, locale)] || _standardRules[0] || undefined;
    },

    /**
     * The logic comes from the Symfony2 PHP Framework.
     *
     * Returns the plural position to use for the given locale and number.
     *
     * @param {Number} number  The number to use to find the indice of the message
     * @param {String} locale  The locale
     * @return {Number}        The plural position
     * @api private
     */
    plural_position: function(number, locale) {
        var _locale = locale;

        if ('pt_BR' === _locale) {
            _locale = 'xbr';
        }

        if (_locale.length > 3) {
            _locale = _locale.split('_')[0];
        }

        switch (_locale) {
            case 'bo':
            case 'dz':
            case 'id':
            case 'ja':
            case 'jv':
            case 'ka':
            case 'km':
            case 'kn':
            case 'ko':
            case 'ms':
            case 'th':
            case 'tr':
            case 'vi':
            case 'zh':
                return 0;
            case 'af':
            case 'az':
            case 'bn':
            case 'bg':
            case 'ca':
            case 'da':
            case 'de':
            case 'el':
            case 'en':
            case 'eo':
            case 'es':
            case 'et':
            case 'eu':
            case 'fa':
            case 'fi':
            case 'fo':
            case 'fur':
            case 'fy':
            case 'gl':
            case 'gu':
            case 'ha':
            case 'he':
            case 'hu':
            case 'is':
            case 'it':
            case 'ku':
            case 'lb':
            case 'ml':
            case 'mn':
            case 'mr':
            case 'nah':
            case 'nb':
            case 'ne':
            case 'nl':
            case 'nn':
            case 'no':
            case 'om':
            case 'or':
            case 'pa':
            case 'pap':
            case 'ps':
            case 'pt':
            case 'so':
            case 'sq':
            case 'sv':
            case 'sw':
            case 'ta':
            case 'te':
            case 'tk':
            case 'ur':
            case 'zu':
                return (number == 1) ? 0 : 1;

            case 'am':
            case 'bh':
            case 'fil':
            case 'fr':
            case 'gun':
            case 'hi':
            case 'ln':
            case 'mg':
            case 'nso':
            case 'xbr':
            case 'ti':
            case 'wa':
                return ((number === 0) || (number == 1)) ? 0 : 1;

            case 'be':
            case 'bs':
            case 'hr':
            case 'ru':
            case 'sr':
            case 'uk':
                return ((number % 10 == 1) && (number % 100 != 11)) ? 0 : (((number % 10 >= 2) && (number % 10 <= 4) && ((number % 100 < 10) || (number % 100 >= 20))) ? 1 : 2);

            case 'cs':
            case 'sk':
                return (number == 1) ? 0 : (((number >= 2) && (number <= 4)) ? 1 : 2);

            case 'ga':
                return (number == 1) ? 0 : ((number == 2) ? 1 : 2);

            case 'lt':
                return ((number % 10 == 1) && (number % 100 != 11)) ? 0 : (((number % 10 >= 2) && ((number % 100 < 10) || (number % 100 >= 20))) ? 1 : 2);

            case 'sl':
                return (number % 100 == 1) ? 0 : ((number % 100 == 2) ? 1 : (((number % 100 == 3) || (number % 100 == 4)) ? 2 : 3));

            case 'mk':
                return (number % 10 == 1) ? 0 : 1;

            case 'mt':
                return (number == 1) ? 0 : (((number === 0) || ((number % 100 > 1) && (number % 100 < 11))) ? 1 : (((number % 100 > 10) && (number % 100 < 20)) ? 2 : 3));

            case 'lv':
                return (number === 0) ? 0 : (((number % 10 == 1) && (number % 100 != 11)) ? 1 : 2);

            case 'pl':
                return (number == 1) ? 0 : (((number % 10 >= 2) && (number % 10 <= 4) && ((number % 100 < 12) || (number % 100 > 14))) ? 1 : 2);

            case 'cy':
                return (number == 1) ? 0 : ((number == 2) ? 1 : (((number == 8) || (number == 11)) ? 2 : 3));

            case 'ro':
                return (number == 1) ? 0 : (((number === 0) || ((number % 100 > 0) && (number % 100 < 20))) ? 1 : 2);

            case 'ar':
                return (number === 0) ? 0 : ((number == 1) ? 1 : ((number == 2) ? 2 : (((number >= 3) && (number <= 10)) ? 3 : (((number >= 11) && (number <= 99)) ? 4 : 5))));

            default:
                return 0;
        }
    }
};
