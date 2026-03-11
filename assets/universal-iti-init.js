(function ($) {
    'use strict';

    /**
     * Universal Phone Input Initialization Script
     */

    // Configuration from localized script data
    const config = window.UniversalPhoneData || {
        utilsScript: '',
        defaultCountry: 'us', // Fallback
        overwriteInput: false
    };

    // IANA Timezone → ISO 3166-1 alpha-2 country code mapping
    const timezoneCountryMap = {
        'Africa/Abidjan': 'ci', 'Africa/Accra': 'gh', 'Africa/Addis_Ababa': 'et', 'Africa/Algiers': 'dz',
        'Africa/Asmara': 'er', 'Africa/Bamako': 'ml', 'Africa/Bangui': 'cf', 'Africa/Banjul': 'gm',
        'Africa/Bissau': 'gw', 'Africa/Blantyre': 'mw', 'Africa/Brazzaville': 'cg', 'Africa/Bujumbura': 'bi',
        'Africa/Cairo': 'eg', 'Africa/Casablanca': 'ma', 'Africa/Ceuta': 'es', 'Africa/Conakry': 'gn',
        'Africa/Dakar': 'sn', 'Africa/Dar_es_Salaam': 'tz', 'Africa/Djibouti': 'dj', 'Africa/Douala': 'cm',
        'Africa/El_Aaiun': 'eh', 'Africa/Freetown': 'sl', 'Africa/Gaborone': 'bw', 'Africa/Harare': 'zw',
        'Africa/Johannesburg': 'za', 'Africa/Juba': 'ss', 'Africa/Kampala': 'ug', 'Africa/Khartoum': 'sd',
        'Africa/Kigali': 'rw', 'Africa/Kinshasa': 'cd', 'Africa/Lagos': 'ng', 'Africa/Libreville': 'ga',
        'Africa/Lome': 'tg', 'Africa/Luanda': 'ao', 'Africa/Lubumbashi': 'cd', 'Africa/Lusaka': 'zm',
        'Africa/Malabo': 'gq', 'Africa/Maputo': 'mz', 'Africa/Maseru': 'ls', 'Africa/Mbabane': 'sz',
        'Africa/Mogadishu': 'so', 'Africa/Monrovia': 'lr', 'Africa/Nairobi': 'ke', 'Africa/Ndjamena': 'td',
        'Africa/Niamey': 'ne', 'Africa/Nouakchott': 'mr', 'Africa/Ouagadougou': 'bf', 'Africa/Porto-Novo': 'bj',
        'Africa/Sao_Tome': 'st', 'Africa/Tripoli': 'ly', 'Africa/Tunis': 'tn', 'Africa/Windhoek': 'na',
        'America/Adak': 'us', 'America/Anchorage': 'us', 'America/Anguilla': 'ai', 'America/Antigua': 'ag',
        'America/Araguaina': 'br', 'America/Argentina/Buenos_Aires': 'ar', 'America/Argentina/Catamarca': 'ar',
        'America/Argentina/Cordoba': 'ar', 'America/Argentina/Jujuy': 'ar', 'America/Argentina/La_Rioja': 'ar',
        'America/Argentina/Mendoza': 'ar', 'America/Argentina/Rio_Gallegos': 'ar', 'America/Argentina/Salta': 'ar',
        'America/Argentina/San_Juan': 'ar', 'America/Argentina/San_Luis': 'ar', 'America/Argentina/Tucuman': 'ar',
        'America/Argentina/Ushuaia': 'ar', 'America/Aruba': 'aw', 'America/Asuncion': 'py',
        'America/Atikokan': 'ca', 'America/Bahia': 'br', 'America/Bahia_Banderas': 'mx', 'America/Barbados': 'bb',
        'America/Belem': 'br', 'America/Belize': 'bz', 'America/Blanc-Sablon': 'ca', 'America/Boa_Vista': 'br',
        'America/Bogota': 'co', 'America/Boise': 'us', 'America/Cambridge_Bay': 'ca', 'America/Campo_Grande': 'br',
        'America/Cancun': 'mx', 'America/Caracas': 've', 'America/Cayenne': 'gf', 'America/Cayman': 'ky',
        'America/Chicago': 'us', 'America/Chihuahua': 'mx', 'America/Costa_Rica': 'cr', 'America/Creston': 'ca',
        'America/Cuiaba': 'br', 'America/Curacao': 'cw', 'America/Danmarkshavn': 'gl', 'America/Dawson': 'ca',
        'America/Dawson_Creek': 'ca', 'America/Denver': 'us', 'America/Detroit': 'us', 'America/Dominica': 'dm',
        'America/Edmonton': 'ca', 'America/Eirunepe': 'br', 'America/El_Salvador': 'sv', 'America/Fort_Nelson': 'ca',
        'America/Fortaleza': 'br', 'America/Glace_Bay': 'ca', 'America/Goose_Bay': 'ca', 'America/Grand_Turk': 'tc',
        'America/Grenada': 'gd', 'America/Guadeloupe': 'gp', 'America/Guatemala': 'gt', 'America/Guayaquil': 'ec',
        'America/Guyana': 'gy', 'America/Halifax': 'ca', 'America/Havana': 'cu', 'America/Hermosillo': 'mx',
        'America/Indiana/Indianapolis': 'us', 'America/Indiana/Knox': 'us', 'America/Indiana/Marengo': 'us',
        'America/Indiana/Petersburg': 'us', 'America/Indiana/Tell_City': 'us', 'America/Indiana/Vevay': 'us',
        'America/Indiana/Vincennes': 'us', 'America/Indiana/Winamac': 'us', 'America/Inuvik': 'ca',
        'America/Iqaluit': 'ca', 'America/Jamaica': 'jm', 'America/Juneau': 'us', 'America/Kentucky/Louisville': 'us',
        'America/Kentucky/Monticello': 'us', 'America/Kralendijk': 'bq', 'America/La_Paz': 'bo',
        'America/Lima': 'pe', 'America/Los_Angeles': 'us', 'America/Lower_Princes': 'sx', 'America/Maceio': 'br',
        'America/Managua': 'ni', 'America/Manaus': 'br', 'America/Marigot': 'mf', 'America/Martinique': 'mq',
        'America/Matamoros': 'mx', 'America/Mazatlan': 'mx', 'America/Menominee': 'us', 'America/Merida': 'mx',
        'America/Metlakatla': 'us', 'America/Mexico_City': 'mx', 'America/Miquelon': 'pm', 'America/Moncton': 'ca',
        'America/Monterrey': 'mx', 'America/Montevideo': 'uy', 'America/Montserrat': 'ms', 'America/Nassau': 'bs',
        'America/New_York': 'us', 'America/Nipigon': 'ca', 'America/Nome': 'us', 'America/Noronha': 'br',
        'America/North_Dakota/Beulah': 'us', 'America/North_Dakota/Center': 'us', 'America/North_Dakota/New_Salem': 'us',
        'America/Nuuk': 'gl', 'America/Ojinaga': 'mx', 'America/Panama': 'pa', 'America/Pangnirtung': 'ca',
        'America/Paramaribo': 'sr', 'America/Phoenix': 'us', 'America/Port-au-Prince': 'ht',
        'America/Port_of_Spain': 'tt', 'America/Porto_Velho': 'br', 'America/Puerto_Rico': 'pr',
        'America/Punta_Arenas': 'cl', 'America/Rainy_River': 'ca', 'America/Rankin_Inlet': 'ca',
        'America/Recife': 'br', 'America/Regina': 'ca', 'America/Resolute': 'ca', 'America/Rio_Branco': 'br',
        'America/Santarem': 'br', 'America/Santiago': 'cl', 'America/Santo_Domingo': 'do',
        'America/Sao_Paulo': 'br', 'America/Scoresbysund': 'gl', 'America/Sitka': 'us',
        'America/St_Barthelemy': 'bl', 'America/St_Johns': 'ca', 'America/St_Kitts': 'kn',
        'America/St_Lucia': 'lc', 'America/St_Thomas': 'vi', 'America/St_Vincent': 'vc',
        'America/Swift_Current': 'ca', 'America/Tegucigalpa': 'hn', 'America/Thule': 'gl',
        'America/Thunder_Bay': 'ca', 'America/Tijuana': 'mx', 'America/Toronto': 'ca', 'America/Tortola': 'vg',
        'America/Vancouver': 'ca', 'America/Whitehorse': 'ca', 'America/Winnipeg': 'ca', 'America/Yakutat': 'us',
        'America/Yellowknife': 'ca',
        'Antarctica/Casey': 'aq', 'Antarctica/Davis': 'aq', 'Antarctica/DumontDUrville': 'aq',
        'Antarctica/Macquarie': 'au', 'Antarctica/Mawson': 'aq', 'Antarctica/McMurdo': 'nz',
        'Antarctica/Palmer': 'aq', 'Antarctica/Rothera': 'aq', 'Antarctica/Syowa': 'aq',
        'Antarctica/Troll': 'aq', 'Antarctica/Vostok': 'aq',
        'Arctic/Longyearbyen': 'sj',
        'Asia/Aden': 'ye', 'Asia/Almaty': 'kz', 'Asia/Amman': 'jo', 'Asia/Anadyr': 'ru',
        'Asia/Aqtau': 'kz', 'Asia/Aqtobe': 'kz', 'Asia/Ashgabat': 'tm', 'Asia/Atyrau': 'kz',
        'Asia/Baghdad': 'iq', 'Asia/Bahrain': 'bh', 'Asia/Baku': 'az', 'Asia/Bangkok': 'th',
        'Asia/Barnaul': 'ru', 'Asia/Beirut': 'lb', 'Asia/Bishkek': 'kg', 'Asia/Brunei': 'bn',
        'Asia/Chita': 'ru', 'Asia/Choibalsan': 'mn', 'Asia/Colombo': 'lk', 'Asia/Damascus': 'sy',
        'Asia/Dhaka': 'bd', 'Asia/Dili': 'tl', 'Asia/Dubai': 'ae', 'Asia/Dushanbe': 'tj',
        'Asia/Famagusta': 'cy', 'Asia/Gaza': 'ps', 'Asia/Hebron': 'ps', 'Asia/Ho_Chi_Minh': 'vn',
        'Asia/Hong_Kong': 'hk', 'Asia/Hovd': 'mn', 'Asia/Irkutsk': 'ru', 'Asia/Istanbul': 'tr',
        'Asia/Jakarta': 'id', 'Asia/Jayapura': 'id', 'Asia/Jerusalem': 'il', 'Asia/Kabul': 'af',
        'Asia/Kamchatka': 'ru', 'Asia/Karachi': 'pk', 'Asia/Kathmandu': 'np', 'Asia/Khandyga': 'ru',
        'Asia/Kolkata': 'in', 'Asia/Krasnoyarsk': 'ru', 'Asia/Kuala_Lumpur': 'my', 'Asia/Kuching': 'my',
        'Asia/Kuwait': 'kw', 'Asia/Macau': 'mo', 'Asia/Magadan': 'ru', 'Asia/Makassar': 'id',
        'Asia/Manila': 'ph', 'Asia/Muscat': 'om', 'Asia/Nicosia': 'cy', 'Asia/Novokuznetsk': 'ru',
        'Asia/Novosibirsk': 'ru', 'Asia/Omsk': 'ru', 'Asia/Oral': 'kz', 'Asia/Phnom_Penh': 'kh',
        'Asia/Pontianak': 'id', 'Asia/Pyongyang': 'kp', 'Asia/Qatar': 'qa', 'Asia/Qostanay': 'kz',
        'Asia/Qyzylorda': 'kz', 'Asia/Riyadh': 'sa', 'Asia/Sakhalin': 'ru', 'Asia/Samarkand': 'uz',
        'Asia/Seoul': 'kr', 'Asia/Shanghai': 'cn', 'Asia/Singapore': 'sg', 'Asia/Srednekolymsk': 'ru',
        'Asia/Taipei': 'tw', 'Asia/Tashkent': 'uz', 'Asia/Tbilisi': 'ge', 'Asia/Tehran': 'ir',
        'Asia/Thimphu': 'bt', 'Asia/Tokyo': 'jp', 'Asia/Tomsk': 'ru', 'Asia/Ulaanbaatar': 'mn',
        'Asia/Urumqi': 'cn', 'Asia/Ust-Nera': 'ru', 'Asia/Vientiane': 'la', 'Asia/Vladivostok': 'ru',
        'Asia/Yakutsk': 'ru', 'Asia/Yangon': 'mm', 'Asia/Yekaterinburg': 'ru', 'Asia/Yerevan': 'am',
        'Atlantic/Azores': 'pt', 'Atlantic/Bermuda': 'bm', 'Atlantic/Canary': 'es', 'Atlantic/Cape_Verde': 'cv',
        'Atlantic/Faroe': 'fo', 'Atlantic/Madeira': 'pt', 'Atlantic/Reykjavik': 'is',
        'Atlantic/South_Georgia': 'gs', 'Atlantic/St_Helena': 'sh', 'Atlantic/Stanley': 'fk',
        'Australia/Adelaide': 'au', 'Australia/Brisbane': 'au', 'Australia/Broken_Hill': 'au',
        'Australia/Darwin': 'au', 'Australia/Eucla': 'au', 'Australia/Hobart': 'au',
        'Australia/Lindeman': 'au', 'Australia/Lord_Howe': 'au', 'Australia/Melbourne': 'au',
        'Australia/Perth': 'au', 'Australia/Sydney': 'au',
        'Europe/Amsterdam': 'nl', 'Europe/Andorra': 'ad', 'Europe/Astrakhan': 'ru', 'Europe/Athens': 'gr',
        'Europe/Belgrade': 'rs', 'Europe/Berlin': 'de', 'Europe/Bratislava': 'sk', 'Europe/Brussels': 'be',
        'Europe/Bucharest': 'ro', 'Europe/Budapest': 'hu', 'Europe/Busingen': 'de', 'Europe/Chisinau': 'md',
        'Europe/Copenhagen': 'dk', 'Europe/Dublin': 'ie', 'Europe/Gibraltar': 'gi', 'Europe/Guernsey': 'gg',
        'Europe/Helsinki': 'fi', 'Europe/Isle_of_Man': 'im', 'Europe/Istanbul': 'tr', 'Europe/Jersey': 'je',
        'Europe/Kaliningrad': 'ru', 'Europe/Kiev': 'ua', 'Europe/Kirov': 'ru', 'Europe/Lisbon': 'pt',
        'Europe/Ljubljana': 'si', 'Europe/London': 'gb', 'Europe/Luxembourg': 'lu', 'Europe/Madrid': 'es',
        'Europe/Malta': 'mt', 'Europe/Mariehamn': 'ax', 'Europe/Minsk': 'by', 'Europe/Monaco': 'mc',
        'Europe/Moscow': 'ru', 'Europe/Nicosia': 'cy', 'Europe/Oslo': 'no', 'Europe/Paris': 'fr',
        'Europe/Podgorica': 'me', 'Europe/Prague': 'cz', 'Europe/Riga': 'lv', 'Europe/Rome': 'it',
        'Europe/Samara': 'ru', 'Europe/San_Marino': 'sm', 'Europe/Sarajevo': 'ba', 'Europe/Saratov': 'ru',
        'Europe/Simferopol': 'ua', 'Europe/Skopje': 'mk', 'Europe/Sofia': 'bg', 'Europe/Stockholm': 'se',
        'Europe/Tallinn': 'ee', 'Europe/Tirane': 'al', 'Europe/Ulyanovsk': 'ru', 'Europe/Uzhgorod': 'ua',
        'Europe/Vaduz': 'li', 'Europe/Vatican': 'va', 'Europe/Vienna': 'at', 'Europe/Vilnius': 'lt',
        'Europe/Volgograd': 'ru', 'Europe/Warsaw': 'pl', 'Europe/Zagreb': 'hr', 'Europe/Zaporozhye': 'ua',
        'Europe/Zurich': 'ch',
        'Indian/Antananarivo': 'mg', 'Indian/Chagos': 'io', 'Indian/Christmas': 'cx', 'Indian/Cocos': 'cc',
        'Indian/Comoro': 'km', 'Indian/Kerguelen': 'tf', 'Indian/Mahe': 'sc', 'Indian/Maldives': 'mv',
        'Indian/Mauritius': 'mu', 'Indian/Mayotte': 'yt', 'Indian/Reunion': 're',
        'Pacific/Apia': 'ws', 'Pacific/Auckland': 'nz', 'Pacific/Bougainville': 'pg', 'Pacific/Chatham': 'nz',
        'Pacific/Chuuk': 'fm', 'Pacific/Easter': 'cl', 'Pacific/Efate': 'vu', 'Pacific/Enderbury': 'ki',
        'Pacific/Fakaofo': 'tk', 'Pacific/Fiji': 'fj', 'Pacific/Funafuti': 'tv', 'Pacific/Galapagos': 'ec',
        'Pacific/Gambier': 'pf', 'Pacific/Guadalcanal': 'sb', 'Pacific/Guam': 'gu', 'Pacific/Honolulu': 'us',
        'Pacific/Kiritimati': 'ki', 'Pacific/Kosrae': 'fm', 'Pacific/Kwajalein': 'mh', 'Pacific/Majuro': 'mh',
        'Pacific/Marquesas': 'pf', 'Pacific/Midway': 'um', 'Pacific/Nauru': 'nr', 'Pacific/Niue': 'nu',
        'Pacific/Norfolk': 'nf', 'Pacific/Noumea': 'nc', 'Pacific/Pago_Pago': 'as', 'Pacific/Palau': 'pw',
        'Pacific/Pitcairn': 'pn', 'Pacific/Pohnpei': 'fm', 'Pacific/Port_Moresby': 'pg',
        'Pacific/Rarotonga': 'ck', 'Pacific/Tahiti': 'pf', 'Pacific/Tarawa': 'ki', 'Pacific/Tongatapu': 'to',
        'Pacific/Wake': 'um', 'Pacific/Wallis': 'wf'
    };

    /**
     * Detect country code from browser timezone and language.
     * @returns {string} Two-letter country code (lowercase)
     */
    function detectCountry() {
        // 1. Try timezone
        try {
            const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            if (tz && timezoneCountryMap[tz]) {
                return timezoneCountryMap[tz];
            }
        } catch (e) { /* Intl not supported */ }

        // 2. Fallback: extract region from browser language (e.g. "en-US" → "us")
        try {
            if (navigator.language) {
                const parts = navigator.language.split('-');
                if (parts.length > 1 && parts[1].length === 2) {
                    return parts[1].toLowerCase();
                }
            }
        } catch (e) { /* ignore */ }

        // 3. Final fallback
        return config.defaultCountry;
    }

    /**
     * Check if an input element should be initialized as a phone input.
     * @param {HTMLInputElement} input 
     * @returns {boolean}
     */
    function shouldInitialize(input) {
        if (input.dataset.itiInitialized) {
            return false;
        }

        // Must be an input element
        if (input.tagName !== 'INPUT') {
            return false;
        }

        const type = input.type.toLowerCase();

        // 1. Check type="tel"
        if (type === 'tel') {
            return true;
        }

        // 2. Check "phone-like" text inputs
        if (type === 'text') {
            const name = (input.name || '').toLowerCase();
            const id = (input.id || '').toLowerCase();
            const autocomplete = (input.autocomplete || '').toLowerCase();
            const inputmode = (input.inputMode || '').toLowerCase();

            // Keywords to match
            const phoneKeywords = ['phone', 'mobile', 'tel', 'whatsapp'];

            // Check if name or id contains keywords
            if (phoneKeywords.some(keyword => name.includes(keyword) || id.includes(keyword))) {
                return true;
            }

            // Check attributes
            if (autocomplete === 'tel' || inputmode === 'tel') {
                return true;
            }
        }

        return false;
    }

    /**
     * Initialize intl-tel-input on a specific input element.
     * @param {HTMLInputElement} input 
     */
    function initPhoneInput(input) {
        if (!shouldInitialize(input)) {
            return;
        }

        // Mark as initialized immediately to prevent race conditions
        input.dataset.itiInitialized = '1';

        // Prepare hidden input for E.164 format
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = (input.name || input.id) + '_e164'; // e.g. 'phone_e164'
        hiddenInput.className = 'iti-e164-hidden';

        // Insert hidden input after the visible input
        if (input.parentNode) {
            input.parentNode.insertBefore(hiddenInput, input.nextSibling);
        }

        // Initialize intl-tel-input with timezone-based country detection
        const iti = window.intlTelInput(input, {
            initialCountry: detectCountry(),
            separateDialCode: true,
            nationalMode: false,
            utilsScript: config.utilsScript
        });

        // Function to update hidden input with E.164 number
        const updateHiddenInput = () => {
            if (iti.isValidNumber()) {
                hiddenInput.value = iti.getNumber();
            } else {
                // If invalid or empty, clear the hidden input or leave it empty
                hiddenInput.value = '';
            }
        };

        // Update on change and blur
        input.addEventListener('change', updateHiddenInput);
        input.addEventListener('blur', updateHiddenInput);
        input.addEventListener('countrychange', updateHiddenInput);

        // Handle Form Submission
        if (input.form) {
            const form = input.form;

            // Listen for submit event on the form
            form.addEventListener('submit', function () {
                updateHiddenInput(); // Ensure latest value is set

                // Optional: Overwrite original input with E.164 value
                if (config.overwriteInput && iti.isValidNumber()) {
                    input.value = iti.getNumber();
                }
            });
        }
    }



    // Initialize on DOM Ready
    $(document).ready(function () {
        // Initial scan of the entire document
        scanForInputs(document.body);

        // Use MutationObserver for dynamic forms (Elementor popups, AJAX loaded content)
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) { // Element node
                        // If the added node is itself an input, check it
                        if (node.tagName === 'INPUT') {
                            initPhoneInput(node);
                        }
                        // Also search for inputs inside the added node
                        scanForInputs(node);
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });

    /**
     * Scan a specific container (or document) for inputs to initialize.
     * @param {HTMLElement} container 
     */
    function scanForInputs(container) {
        // Find inputs within the container
        const inputs = container.querySelectorAll('input');
        inputs.forEach(input => {
            initPhoneInput(input);
        });
    }

})(jQuery);
