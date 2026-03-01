(function ($) {
    'use strict';

    var Optivac = {

        _checkedEmails: {},

        init: function () {
            this.bindEmailFields();
            this.checkAutofill();
            this.observeDynamicFields();
        },
        bindEmailFields: function () {
            $('input[type="email"], input[name*="email"]').each(function () {
                Optivac.attachBlurListener(this);
                var email = $(this).val().trim();
                if (Optivac.isValidEmail(email) && !Optivac._checkedEmails[email]) {
                    Optivac._checkedEmails[email] = true;
                    Optivac.checkConsent(email);
                }
            });
        },
        checkAutofill: function () {
            var attempts = 0;
            var maxAttempts = 10;
            var interval = setInterval(function () {
                attempts++;
                $('input[type="email"], input[name*="email"]').each(function () {
                    var email = $(this).val().trim();
                    if (Optivac.isValidEmail(email) && !Optivac._checkedEmails[email]) {
                        Optivac._checkedEmails[email] = true;
                        Optivac.checkConsent(email);
                    }
                });

                if (attempts >= maxAttempts) {
                    clearInterval(interval);
                }
            }, 300);
        },

        observeDynamicFields: function () {
            if (!window.MutationObserver) {
                return;
            }

            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType !== 1) {
                            return;
                        }

                        if (Optivac.isEmailField(node)) {
                            Optivac.attachBlurListener(node);
                        }

                        $(node).find('input[type="email"], input[name*="email"]').each(function () {
                            Optivac.attachBlurListener(this);
                        });
                    });
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree:   true,
            });
        },

        isEmailField: function (el) {
            return el.tagName === 'INPUT' && (
                el.type === 'email' ||
                (el.name && el.name.toLowerCase().indexOf('email') !== -1)
            );
        },

        attachBlurListener: function (field) {
            if ($(field).data('optivac-bound')) {
                return;
            }

            $(field).data('optivac-bound', true);

            $(field).on('blur.optivac', function () {
                var email = $(this).val().trim();

                // Champ ignoré
                if ($(this).is('[readonly], [disabled]')) {
                    return;
                }

                if (!Optivac.isValidEmail(email)) {
                    return;
                }

                if (Optivac._checkedEmails[email]) {
                    return;
                }

                if ($('#optivac-modal').length) {
                    return;
                }

                Optivac._checkedEmails[email] = true;
                Optivac.checkConsent(email);
            });
        },

        isValidEmail: function (email) {
            if (!email || email.length > 254) {
                return false;
            }

            var regex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$/;

            if (!regex.test(email)) {
                return false;
            }

            var parts = email.split('@');

            if (parts[0].length > 64) {
                return false;
            }

            if (parts[0].indexOf('..') !== -1) {
                return false;
            }

            if (parts[0].charAt(0) === '.' || parts[0].charAt(parts[0].length - 1) === '.') {
                return false;
            }

            return true;
        },

        checkConsent: function (email) {
            $.post(optivacConfig.ajaxUrl, {
                action: 'optivac_status',
                nonce:  optivacConfig.nonce,
                email:  email,
            }, function (response) {
                if (!response.success) {
                    return;
                }

                var data = response.data;

                if (!data.needsAnyConsent) {
                    return;
                }

                Optivac.showModal(email, data);
            });
        },

        showModal: function (email, data) {
            if ($('#optivac-modal').length) {
                return;
            }

            var newsletter = data.newsletter || {};
            var offers     = data.offers || {};

            if (!newsletter.needsConsent && !offers.needsConsent) {
                return;
            }

            var html = '<div id="optivac-modal" class="optivac-overlay">'
                + '<div class="optivac-modal">'
                + '<p class="optivac-modal__title">Vos préférences de communication</p>';

            if (newsletter.needsConsent) {
                html += '<label class="optivac-consent-block">'
                    + '<input type="checkbox" id="optivac-newsletter" checked />'
                    + "<span class=\"optivac-consent-block__text\">J'accepte de recevoir la newsletter par e-mail</span>" 
                    + '</label>';
            }

            if (offers.needsConsent) {
                html += '<label class="optivac-consent-block">'
                    + '<input type="checkbox" id="optivac-offers" checked />'
                    + "<span class=\"optivac-consent-block__text\">J'accepte de recevoir des offres et communications par e-mail</span>" 
                    + '</label>';
            }

            html += '<p class="optivac-legal">'
                + "Vos données personnelles seront utilisées pour vous accompagner au cours de votre visite du site web, "
                + "gérer l'accès à votre compte, et pour d'autres raisons décrites dans notre " 
                + '<a href="/politique-de-confidentialite" target="_blank">politique de confidentialit&#233;</a>.'
                + '</p>'
                + '<div class="optivac-actions">'
                + '<button id="optivac-save" class="optivac-btn optivac-btn-primary">Confirmer mes choix</button>'
                + '<button id="optivac-decline" class="optivac-btn optivac-btn-secondary">Non merci, je refuse</button>'
                + '</div></div></div>';

            $('body').append(html);

            $('#optivac-save').on('click', function () {
                Optivac.saveConsent(email, data);
            });

            $('#optivac-decline').on('click', function () {
                $('#optivac-modal').fadeOut(300, function () {
                    $(this).remove();
                });
            });
        },

        saveConsent: function (email, data) {
            var newsletter = data.newsletter || {};
            var offers     = data.offers || {};

            var payload = {
                action:        'optivac_validate',
                nonce:         optivacConfig.nonce,
                email:         email,
                newsletter:    newsletter.needsConsent ? $('#optivac-newsletter').is(':checked') : false,
                offers:        offers.needsConsent ? $('#optivac-offers').is(':checked') : false,
                policyVersion: 'v1',
            };

            $.post(optivacConfig.ajaxUrl, payload, function () {
                $('#optivac-modal').fadeOut(300, function () {
                    $(this).remove();
                });
            });
        },
    };

    $(document).ready(function () {
        Optivac.init();
    });

}(jQuery));