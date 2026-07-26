# OmniPOS Complete User Manual

**Version:** July 2026  
**Audience:** Shop owners, administrators, managers and cashiers

## 1. What OmniPOS does
OmniPOS is a retail operations system that combines checkout, inventory, purchasing, customers, credit, expenses, reporting, staff administration and multi-branch controls. It can be used as a desktop application and, when hosted securely, installed from a compatible browser as a web app.

## 2. First-time setup and sign-in
1. Open the login screen. When no shop exists, choose **Set Up Your Shop**.
2. Enter the business name, contact details, currency and currency symbol. A Main Branch is created automatically.
3. Create the owner/admin account and sign in.
4. Set your business information, tax rates, products, opening stock, staff and branches before trading.
5. Use a strong password. Login attempts are limited to five per minute to protect accounts.

### Install OmniPOS as a web app
On a compatible desktop or Android browser, choose **Install OmniPOS App** on the login screen when it is offered. On an iPhone or iPad, open the site in Safari, use **Share**, then choose **Add to Home Screen**. Production web installations require HTTPS. HTTPS is also required for camera scanning.

## 3. Dashboard
The dashboard is the operational overview. Review today's sales, orders, total products, expenses, recent sales, low-stock items and top-selling products. Use it at opening and close of business to spot exceptions and decide which stock needs attention.

## 4. Point of Sale (POS)
### Create a sale
1. Open **Point of Sale**.
2. Find an item by name or barcode, select a category, or add it using the + button.
3. Adjust cart quantities; remove an item if needed. For products authorised for price override, enter the approved selling price.
4. Select a saved customer or keep **Walk-in Customer**.
5. Enter a discount if permitted. Active branch tax rates are calculated on the taxable total.
6. Choose **Cash**, **MoMo**, or **Card**. Add a reference for non-cash payments where required.
7. Confirm the amount paid and select **Collect Payment**.
8. Print a receipt or invoice after the sale. Where customer contact details are available, receipts can be shared by email or WhatsApp.

### Scan a barcode or QR code
Select **Scan** beside product search. Permit camera access, place the code in the camera frame and OmniPOS adds the matching product to the cart. The same screen accepts a manual barcode when a camera is unavailable. USB barcode scanners are supported as keyboard input: scan while the POS is open and the matching item is added or searched. A code must match the barcode saved on the product.

### Customer credit and refunds
A selected customer may pay less than the invoice total; the unpaid value becomes their outstanding balance. Walk-in customers must pay the full total. Managers, admins and owners can open a completed sale from **Sales** and use the refund workflow. Review the sale before completing a refund.

## 5. Sales history
Open **Sales** to review completed transactions. Use a sale detail page to inspect items, payments, customer, totals and receipt data. From a completed sale you can print/retrieve receipt information and, with the required role, begin a refund. Email receipts require a customer email address and configured branch email.

## 6. Products, services and inventory
Managers and above can manage products.

### Add or edit a product
Open **Products** and choose **Add Product**. Enter the name, type (product or service), category, unit, cost, selling price, barcode, image and whether price override is permitted. Assign inventory products to the required branches, set opening quantities and low-stock alert levels. Services can be sold without physical stock.

### Import products
Use **Products > Import** to upload a prepared spreadsheet. Download the import template first and keep its headings and expected values. Check imported prices, costs, barcodes and opening stock before going live.

### Maintain stock
- **Restock:** increase a product's branch quantity.
- **Adjust stock:** correct a count, loss, damage or stocktake difference and retain the movement record.
- **Stock logs:** review recorded movements for a product.
- **Transfer:** move stock from one branch to another.
- **Remove branch:** remove a product from a branch only after confirming its local stock position.

POS sales reduce physical item stock. Receiving purchase-order items increases branch stock. Use low-stock thresholds to focus ordering activity.

## 7. Purchase orders
Purchase orders support planned replenishment.
1. Open **Purchase Orders** and select **Create**.
2. Add supplier/order details, products, quantities and costs.
3. Save the order for review. Admins/owners can approve or reject where applicable.
4. When goods arrive, open the order and use **Receive Item** for partial deliveries or **Receive All** for a complete delivery.
5. Confirm received quantities carefully: receipt updates stock. Print the purchase order when a supplier or receiving document is needed.

## 8. Customers and debt repayment
Open **Customers** to add, search and view customers. Store useful contact details and a credit limit where your policy requires one. Customer pages show outstanding balance and related activity. To record repayment, open the customer, choose repayment, enter the amount and payment details, then save. Use the outstanding-balance view during credit approval and follow-up.

## 9. Expenses
Open **Expenses** to record business costs. Create expense categories first if needed. Add the date, category, description, amount and receipt attachment. Edit incorrect entries, download attached receipts, and remove entries only in accordance with your audit procedure. Filter the list to review a period or category. The expense report supports management review; CSV/XLSX export endpoints are available for spreadsheet use when linked in the deployed interface.

## 10. Reports
Managers and above can access reports.

### Sales report
Choose a date range and review revenue, cost of goods, expenses and calculated profit. Use the revenue trend, top products and transaction list to assess performance. Profit is only as accurate as your product costs and recorded expenses.

### Stock report
Review stock by current branch. Search products or filter for low/out-of-stock items. Review stock value and projected stock profit value to guide purchasing and stock counts.

### Expense report
Use the expense report to review recorded costs by period and category, then compare them with sales performance.

## 11. Branches and staff
### Branches
Admins and owners can create and edit branches with name, address and phone details. Use branch switching to work in the correct location. Products, stock, tax settings, sales and operational reports are branch-aware.

### Staff and roles
Admins and owners can create staff accounts, assign a branch and role, update role/branch/status, reset passwords and deactivate users.
- **Owner:** full business control, including licensing and administration.
- **Admin:** manages staff, branches, settings and major operational controls.
- **Manager:** manages products, inventory, reports, purchase-order receiving and approved operational tasks.
- **Cashier:** operates POS, customers and permitted expense workflows.

Always use named accounts; deactivate a departing staff member instead of sharing credentials.

## 12. Settings, taxes and email
### General settings
Set business name, contact information, address, currency and currency symbol. These details appear across the app and on business documents.

### Tax rates
Create, edit, activate/deactivate and order tax rates. Active rates for the current branch are applied at POS checkout. Confirm rates with your accountant or tax adviser before using them in production.

### Branch email and notifications
For each branch, configure a Gmail address, Google app password, sender name and enablement status. Use **Send Test** before relying on email. Notification preferences support low-stock and operational emails. Daily summary and weekly debt summary commands can send configured reports when the Laravel scheduler is running and shop/branch email is set.

### License and offline controls
Use the License screen to activate or refresh the license. Offline-license settings provide a protected configuration path. Follow the agreed license/support process when moving devices or changing business details.

## 13. Local peer synchronisation
The Peer settings screen can hold active devices/peers for local-network synchronisation. The supplied sync commands can exchange supported changes across reachable peers or use a configured shared folder. This is an advanced deployment feature: configure a stable local network, test on non-production data first, and ensure each device is correctly identified. Do not rely on sync until it has been validated for your specific deployment.

## 14. Security and reliability
- Login is throttled to five attempts per minute.
- Checkout is throttled to 30 requests per minute to reduce accidental rapid duplicate submissions.
- Role checks restrict sensitive screens and actions.
- Sales, payments, stock movements and expenses should be reviewed regularly.
- Use HTTPS for web deployments, camera permissions and PWA installation.
- Keep backups of the production database and product/stock data according to your business policy.

## 15. Recommended daily routine
**Opening:** Confirm the branch, network/device, receipt printer, scanner and low-stock list.  
**During trading:** Attach customers correctly, use the right payment method/reference, and record stock adjustments with a reason.  
**Receiving:** Receive only goods physically counted.  
**Close:** Review sales, cash/payment references, expenses, refunds and exceptions; then review dashboard and reports.  
**Weekly:** Review top sellers, low-stock products, customer debt and purchase orders.

## 16. Troubleshooting
- **Camera scanner unavailable:** allow camera permission; use HTTPS; try manual entry or a USB scanner.
- **Code does not add a product:** check the saved product barcode and product/branch availability.
- **Install button not shown:** use a supported browser over HTTPS; browsers decide when to show the install prompt.
- **Emails fail:** confirm branch Gmail address, Google app password, enabled status, recipient address and Send Test result.
- **Stock is incorrect:** check purchase receipts, completed sales, transfers, adjustments and product stock logs before correcting it.
- **Cannot see a feature:** confirm the user role, active license and current branch.

## 17. Go-live checklist
- Configure business identity, currency, branches and tax rates.
- Create staff accounts and test correct role access.
- Import/create products and verify barcode, cost, selling price, branch stock and alert threshold.
- Test sale, cash/MoMo/card payment, receipt, customer credit, refund permissions and expense entry.
- Test scanner, printer and email using real device settings.
- Confirm HTTPS before PWA/camera use.
- Confirm daily/weekly reporting and backup responsibilities.
