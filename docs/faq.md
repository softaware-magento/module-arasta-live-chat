# Arasta Live Chat for Magento 2: FAQ

**What does the module do?**
It adds the Arasta chat widget to your storefront, lets the widget recognise signed-in customers with a signed
token, gives Arasta access to your invoice PDFs through the REST API, and links orders placed after a chat to that
conversation.

**Does it cost anything?**
The module is free. You need an Arasta account for the chat itself.

**Do I still need to paste the Arasta embed code into my theme?**
No. Enter the Store ID from the embed code in Stores > Configuration > Softaware > Arasta Live Chat and remove any
embed code you added by hand, otherwise the widget loads twice.

**Does it work with Hyvä?**
Yes. A Hyvä template without RequireJS is included and picked automatically. The Hyvä checkout uses the Luma
fallback, where the Luma template is used.

**Is the widget shown on the checkout?**
Yes, on every storefront page including the cart and checkout. `app.arasta.io` is whitelisted in the Content
Security Policy, so the widget also loads where Magento enforces CSP.

**Does it slow down my store or break full-page caching?**
The loader is added with `async` and does not block the page. The customer token and cart ID are loaded separately
as private customer data, so cached pages stay the same for everyone and never contain personal data.

**How are signed-in customers recognised?**
For signed-in customers the store creates a short-lived token signed with your Arasta signing secret, with the
customer ID and email address. Arasta checks the signature, so the chat cannot be opened in someone else's name.

**What happens without a signing secret?**
The widget works and customers chat as guests. Orders are not linked to conversations.

**Can I use different Arasta stores for different store views?**
Yes. All settings can be set per website and store view.

**Which permission does the Arasta integration need?**
Softaware > Arasta Live Chat > Arasta Live Chat API (`Softaware_ArastaLiveChat::api`), next to the resources Arasta
asks for. Reauthorise the integration after changing its permissions.

**Can a checkout fail because Arasta is unavailable?**
No. The order link is sent with a 3-second timeout and any error is only logged.

**I used platform/module-connector before. What changes?**
Remove the old package and install this one. `setup:upgrade` copies your settings and gives the Arasta integration
the new API permission. The endpoints and the data exchanged with Arasta are unchanged.

**Which data is sent to Arasta?**
The signed-in customer's ID and email address (in the token), and for orders placed after a chat the order number,
total, currency and conversation ID. List Arasta as a processor in your privacy notice.
