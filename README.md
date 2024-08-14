# woo-delayed-orders
## V2
* Display a list of orders in "processing" status, in two categories: "Delayed Orders", and "Significantly Delayed Orders".
* **Order table columns: Order Id (linked to order detail page), Order Date, Days Since Order, Shipping Method, Shipping Charge. // Remove customer name column.
* Settings tab - configure # of days from date order was placed to define the two above categories.
* Settings tab - configure email addresses to send email report to, and what time to send the report.
* Settings tab - configure respective email subject lines, and body message (email by default contains the same tables) (2 emails are sent, if there are orders in both categories)
* Move "Manually Trigger Delayed Orders Report" button to top right.
* Show Woo success/fail notification when email report is triggered. 

## V3
* Introduce Klaviyo integration
* Settings tab - Klaviyo api key
* Settings tab - Klaviyo configure events to send 1) send event daily "Order Delayed" 2) send events on x days "Order Delayed 3 Days", "Order Delayed 5 Days", "Order Delayed Over 10 Days"
* Klaviyo event (ref. our klaviyo sync plugin for how events are sent to Klaviyo) - should contain order information (order id, order created date, items in order (array with subtotal), shipping method, order total etc (ref. klaviyo sync plugin).

## V4
* Introduce Brevo, Omnisend, Sendlane integration
* Same settings as above
* Add "Issue Full Refund" and "Refund Shipping Fee" buttons for each order. On click, process refund through gateway. If success, show woo notice refund(s) were successful. 
