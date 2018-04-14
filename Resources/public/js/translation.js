var rvalin_translation = {
    tag: 'trans', // html tag used for the translations
    pluralSeparator: '|',
    translations: [],
    current_id: null,
    route_update: null,
    use_textarea: true,

    init: function(translations, route_update, use_textarea) {
        this.initEvents();
        this.translations = JSON.parse(translations);
        this.route_update = route_update;
        this.use_textarea = use_textarea;
    },

    initCancelButton: function() {
        $("#rvalin_translation-cancel").click(rvalin_translation.hideInfo);
    },

    initSaveButton: function() {
        $("#rvalin_translation-save").click(rvalin_translation.saveTranslation);
    },

    saveTranslation: function() {
        let id = rvalin_translation.current_id;
        if(!id || !rvalin_translation.translations[id]) {
            console.error('No translation selected');
        }

        $.ajax({
            url: rvalin_translation.route_update,
            method: 'post',
            data: rvalin_translation.translations[id],
            dataType: 'json'
        }).done(function(data) {
            console.log('success');
        }).fail(function() {
            console.log('error');
        });
    },

    initEvents: function() {
        $(this.tag).blur(function(){
            let transId = $(this).data('id');
            let translation = $(this).html();
            rvalin_translation.updateTranslation(transId, translation);
            rvalin_translation.updateText();
        });

        $(this.tag).click(function(e) {
            e.preventDefault();
        });

        $(this.tag).on('click focus', function(e) {
            let transId = $(this).data('id');

            if($(this).attr('contenteditable')) {
                $(this).text(rvalin_translation.untranslate(transId));
            }
            rvalin_translation.showInfo(transId);
        });
    },

    translate: function(id) {
        let translation = rvalin_translation.translations[id];

        let message = translation.translationCode;
        if(translation.plural) {
            message = rvalin_translation.pluralize(translation.translationCode, translation.number, translation.locale);
        }

        return rvalin_translation.replace_placeholders(message, translation.parameters);
    },

    untranslate: function(id) {
        return rvalin_translation.translations[id].translationCode;
    },

    showInfo: function(id) {
        rvalin_translation.hideInfo();
        rvalin_translation.current_id = id;
        let translation = rvalin_translation.translations[id];

        let allowPluralize = "false";
        if (translation.plural) {
            allowPluralize = "true";
        }

        let div = '<div id="r_valin_translation_info">' +
            '<header>Edit this translation</header><section>' +
            '<div class="section-title">Information</div>' +
            `<div class="info"><span class="info-title">Key:</span> ${translation.key}</div>` +
            `<div class="info"><span class="info-title">Domain:</span> ${translation.domain}</div>` +
            `<div class="info"><span class="info-title">Pluralize:</span> ${allowPluralize}</div>`
        ;

        if (translation.plural) {
            div += `<div class="info"><span class="info-title">Number:</span> ${translation.number}</div>`;
        }

        div += '<div class="section-title">Translation</div>';
        if (rvalin_translation.use_textarea) {
            div += `<textarea rows="4" id="r_valin_translation_edit">${translation.translationCode}</textarea>`;
        } else {
            div += `<div>${translation.translationCode}</div>`;
        }


        if (Object.keys(translation.parameters).length > 0) {
            div += '<div class="section-title">Parameters</div>';
            for (let key in translation.parameters) {
                div += `<div class="info"><span class="info-title">${key}</span> ${translation.parameters[key]}</div>`;
            }
        }

        div += '<div class="button-container"><button id="rvalin_translation-cancel">Cancel</button>' +
            '<button id="rvalin_translation-save">Save</button>' +
            '</div></section></div>'
        ;

        $(div).appendTo('body');

        rvalin_translation.initTextarea();
        rvalin_translation.initCancelButton();
        rvalin_translation.initSaveButton();
    },

    initTextarea: function() {
        $('#r_valin_translation_edit').change(function() {
            rvalin_translation.translations[rvalin_translation.current_id].translationCode = $(this).val();
            rvalin_translation.updateText();
        });
    },

    updateText: function() {
        $(rvalin_translation.tag+'[data-id="'+rvalin_translation.current_id+'"]').html(rvalin_translation.translate(rvalin_translation.current_id));
        $('#r_valin_translation_edit').val(rvalin_translation.translations[rvalin_translation.current_id].translationCode);
    },


    hideInfo: function() {
        rvalin_translation.current_id = null;
        $('#r_valin_translation_info').remove();
    },

    updateTranslation: function(id, translation) {
        translation = $('<div/>').html(translation).text();
        rvalin_translation.translations[id].translationCode = translation;
        rvalin_translation.translations[id].translationValue = rvalin_translation.translate(id);
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
        for (let _i in placeholders) {
            let _r = new RegExp(_i, 'g');

            if (_r.test(message)) {
                let _v = String(placeholders[_i]).replace(new RegExp('\\$', 'g'), '$$$$');
                message = message.replace(_r, _v);
            }
        }

        return message;
    },

    pluralize: function(message, number, locale) {
        let _p,
            _e,
            _explicitRules = [],
            _standardRules = [],
            _parts         = message.split(rvalin_translation.pluralSeparator),
            _matches       = [],
            _sPluralRegex = new RegExp(/^\w+\: +(.+)$/),
            _cPluralRegex = new RegExp(/^\s*((\{\s*(\-?\d+[\s*,\s*\-?\d+]*)\s*\})|([\[\]])\s*(-Inf|\-?\d+)\s*,\s*(\+?Inf|\-?\d+)\s*([\[\]]))\s?(.+?)$/),
            _iPluralRegex = new RegExp(/^\s*(\{\s*(\-?\d+[\s*,\s*\-?\d+]*)\s*\})|([\[\]])\s*(-Inf|\-?\d+)\s*,\s*(\+?Inf|\-?\d+)\s*([\[\]])/);

        for (_p = 0; _p < _parts.length; _p++) {
            let _part = _parts[_p];

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
                    let _ns = _matches[2].split(','),
                        _n;

                    for (_n in _ns) {
                        if (number == _ns[_n]) {
                            return _explicitRules[_e];
                        }
                    }
                } else {
                    let _leftNumber  = convert_number(_matches[4]);
                    let _rightNumber = convert_number(_matches[5]);

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
        let _locale = locale;

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