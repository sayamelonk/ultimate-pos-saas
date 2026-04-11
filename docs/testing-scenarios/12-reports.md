# 12 - Reports & Analytics Testing

## Sales Reports

### RPT-001: Daily sales report
- **URL**: `/reports/daily`
- **Input**: Date selection
- **Expected**: Shows sales for selected date
- **Test File**: `tests/Feature/Api/V2/ReportsApiTest.php::test_can_get_sales_report`

### RPT-002: Sales by payment method
- **Expected**: Breakdown by cash, card, e-wallet

### RPT-003: Sales by category
- **Expected**: Shows revenue per product category

### RPT-004: Sales by product
- **Expected**: Top selling products

### RPT-005: Hourly sales trend
- **Expected**: Sales distribution by hour

---

## Transaction Reports

### TXN-RPT-001: Transaction list report
- **URL**: `/reports/transactions`
- **Input**: Date range, outlet filter
- **Expected**: List of transactions

### TXN-RPT-002: Transaction summary
- **Expected**:
  - Total transactions
  - Total revenue
  - Average transaction value

### TXN-RPT-003: Void/refund report
- **Expected**: All voided and refunded transactions

---

## Cash Drawer Reports

### CASH-RPT-001: Cash drawer summary
- **URL**: `/reports/cash-drawer`
- **Expected**: Opening balance, cash in, cash out, closing balance

### CASH-RPT-002: Cash variance report
- **Setup**: Expected vs actual differs
- **Expected**: Shows variance

### CASH-RPT-003: Cash movement history
- **Expected**: All cash transactions logged

---

## Product Reports

### PROD-RPT-001: Product sales report
- **URL**: `/reports/products`
- **Expected**: Units sold, revenue per product

### PROD-RPT-002: Product mix analysis
- **Expected**: Percentage of total sales per product

### PROD-RPT-003: Slow-moving products
- **Setup**: Products with low sales
- **Expected**: List of slow-moving items

### PROD-RPT-004: Fast-moving products
- **Expected**: Top selling products

---

## Customer Reports

### CUST-RPT-001: Customer transaction history
- **URL**: `/reports/customers`
- **Expected**: Transaction history per customer

### CUST-RPT-002: Customer loyalty report
- **Expected**: Points earned/redeemed per customer

### CUST-RPT-003: New vs returning customers
- **Expected**: Breakdown of customer types

---

## Employee Reports

### EMP-RPT-001: Sales by employee
- **Expected**: Revenue attributed to each cashier

### EMP-RPT-002: Employee performance
- **Expected**: Transaction count, average value

---

## Inventory Reports

### INV-RPT-001: Stock level report
- **URL**: `/inventory/reports/stock-valuation`
- **Expected**: Current stock levels and values

### INV-RPT-002: Stock movement report
- **Expected**: All stock in/out movements

### INV-RPT-003: Low stock alert report
- **Expected**: Items below minimum stock

### INV-RPT-004: Expiring stock report
- **Expected**: Stock expiring within 30/60/90 days

### INV-RPT-005: Inventory valuation (FIFO/Average)
- **Expected**: Stock value calculated correctly

---

## Kitchen Reports

### KITCH-RPT-001: Kitchen performance
- **Expected**: Average cooking time per order

### KITCH-RPT-002: Rush hour analysis
- **Expected**: Peak kitchen hours

---

## Financial Reports

### FIN-RPT-001: Revenue breakdown
- **Expected**: Gross revenue, discounts, net revenue

### FIN-RPT-002: Tax report
- **Expected**: Tax collected breakdown

### FIN-RPT-003: Service charge report
- **Expected**: Service charges collected

---

## Report Export

### EXP-001: Export to PDF
- **Action**: Export report to PDF
- **Expected**: PDF file generated

### EXP-002: Export to Excel
- **Action**: Export report to Excel
- **Expected**: Excel file generated

### EXP-003: Email report
- **Action**: Schedule daily/weekly report
- **Expected**: Report emailed automatically

---

## Existing Test Files

| Test File | Line Reference |
|-----------|----------------|
| `tests/Feature/Api/V2/ReportsApiTest.php` | All reports API tests |
