/**
 * Loads the widget with the signed identity token of the logged-in customer (R-MOD-02, spec 9.4).
 * The token comes from the private `platform-identity` customer-data section, so cached pages never contain it.
 * Phase 2 (R-PD-02): the section also carries the guest's masked quote id as `data-cart-id`, which the loader's
 * storefront helper sends to `POST /rest/V1/platform/quote/attribute`.
 */
define(['Magento_Customer/js/customer-data'], function (customerData) {
    'use strict';

    return function (config) {
        var identity = customerData.get('platform-identity');
        var script = document.createElement('script');

        script.src = config.loaderUrl;
        script.async = true;
        script.id = 'platform-widget-loader';
        script.setAttribute('data-store-key', config.storeKey);

        function apply(data) {
            if (data && data.token) {
                script.setAttribute('data-customer-token', data.token);
            } else {
                script.removeAttribute('data-customer-token');
            }
            if (data && data.cartId) {
                script.setAttribute('data-cart-id', data.cartId);
            } else {
                script.removeAttribute('data-cart-id');
            }
            window.dispatchEvent(new CustomEvent('platform:identity', { detail: { token: (data && data.token) || null } }));
        }

        apply(identity());
        identity.subscribe(apply);
        document.body.appendChild(script);
    };
});
