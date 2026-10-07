# Loan Management System Documentation and Client Quotation Guide

## 1. Project Overview

The Loan Management System is a Laravel-based web application for businesses that sell products by installment or manage customer loans. It helps staff control customers, loan contracts, payment schedules, collections, reports, quotations, public customer registration, customer self-service, chat, GPS tracking, and integration with Ultimate POS.

Main system URLs:

- Admin dashboard: `/loan-management/dashboard`
- Public website: `/`
- Customer login: `/customer/login`
- Customer dashboard: `/customer/dashboard`
- Loan quotation module: `/loan-management/quotations`

The system is designed for three user groups:

- Admin: controls the whole system, users, roles, settings, reports, and business configuration.
- Staff/collector: creates loans, collects payments, follows overdue customers, communicates with customers, and records field visits.
- Customer: registers, logs in, views loans, checks payment schedules, uploads payment proof, and chats with staff.

## 2. Main System Functions

### 2.1 Dashboard

The dashboard gives management and staff a quick view of daily operations.

Functions:

- Total loans summary.
- Customer summary.
- Due today summary.
- Overdue loan summary.
- Collection amount summary.
- Recent payments.
- Quick search for customer, phone, loan number, or invoice number.
- Operational cards for daily work.

### 2.2 Customer Management

This module stores and manages customer information.

Functions:

- Add new customer.
- Edit customer information.
- View customer profile and loan history.
- Delete customer where allowed.
- Clone customer from Ultimate POS contact.
- Sync customer data from POS.
- Upload customer documents.
- Store Khmer and English names.
- Store phone, address, ID card, work, family, and location details.
- Enable or disable customer app login.
- Reset customer password.
- Enable or disable GPS tracking.
- Blacklist high-risk customers.
- Generate Telegram/customer chat connection.

### 2.3 Loan Management

This module manages installment sales and loan contracts.

Functions:

- Create standalone loan.
- Create loan from POS sale.
- Select or create customer.
- Add product/item details.
- Add down payment.
- Define loan amount.
- Define interest/profit rate.
- Support daily, weekly, and monthly payment frequency.
- Preview repayment schedule before saving.
- Save loan as draft, pending, or active.
- Edit loan information.
- Edit items, schedules, and workflow.
- Print invoice.
- Print loan contract.
- Process settlement/payoff.
- Reschedule loan.
- Log promise-to-pay.
- Change loan status.

Common loan statuses:

- Draft
- Pending
- Active
- Partial
- Closed
- Bad Debt
- Cancelled

### 2.4 Quotation Management

The quotation module prepares a price and installment proposal before creating a real loan.

Functions:

- Create quotation.
- Select customer.
- Add product items.
- Add total amount, discount, and down payment.
- Calculate loan amount.
- Set interest/profit rate.
- Set payment frequency.
- Preview payment schedule.
- Print quotation.
- View quotation details.
- Edit quotation.
- Duplicate quotation.
- Change quotation status.
- Convert accepted quotation to loan.
- Delete quotation where allowed.

Common quotation statuses:

- Draft
- Sent
- Accepted
- Rejected
- Expired
- Converted
- Cancelled

Recommended quotation workflow:

1. Staff opens `/loan-management/quotations/create`.
2. Staff selects or enters customer information.
3. Staff adds product or service items.
4. Staff enters down payment, interest/profit rate, duration, and payment frequency.
5. System previews the installment schedule.
6. Staff saves the quotation.
7. Staff prints or sends the quotation to the customer.
8. Customer accepts or rejects the offer.
9. If accepted, staff converts quotation to loan.
10. System creates the loan and payment schedule.

### 2.5 Payment Management

This module records customer payments.

Functions:

- Add payment from loan detail page.
- Add quick payment.
- View payment history.
- Edit payment.
- Delete payment where allowed.
- Print receipt.
- Support payment method.
- Support currency and exchange rate.
- Support reference number and note.
- Support payment proof upload.
- Update schedule paid amount and balance.

Payment types:

- Collection payment
- Deposit payment
- Down payment
- Schedule payment
- Payoff payment

### 2.6 Loan Schedule

This module shows installment schedule records.

Functions:

- View all schedules.
- Filter by date range.
- Filter by status.
- Filter by loan status.
- Filter by location.
- Filter by collector.
- Search by loan number, invoice, customer, or phone.
- View due today, paid, open, overdue, amount due, paid amount, and balance summaries.
- Calendar view for installment schedules.

### 2.7 Collection Operations

This module helps collectors and managers follow daily collection activity.

Functions:

- New loans.
- Active loans.
- Due today.
- Today collection.
- Partial payments.
- Closed accounts.
- Overdue accounts.
- Promise to pay.
- Broken promise.
- Field visit required.
- Skip customers.
- Delinquent accounts.
- Recovery management.
- Debt collection.
- Collection visit records.

### 2.8 Reports

The system includes operational and financial reports.

Reports:

- Installment report.
- Daily loan summary.
- Monthly loan summary.
- Yearly loan summary.
- Payment summary by type.
- Payment summary by method.
- Portfolio at risk.
- CBC export.
- Dashboard reports.
- Blacklist report.

Report functions:

- Date range filter.
- Location filter.
- Customer/search filter.
- Summary cards.
- Data table.
- Export CSV.
- Export Excel where available.
- Print.
- Column visibility where available.

### 2.9 Public Website and Customer Portal

The system includes a public website and customer self-service area.

Public functions:

- Home page.
- Customer registration.
- Customer loan request.
- Customer login.
- Customer logout.
- CMS enable/disable.
- Public logo, stamp, and home content.

Customer dashboard functions:

- View profile.
- View loan summary.
- View active loans.
- View payment schedules.
- View payment history.
- Upload payment proof.
- Chat with staff.
- View GPS tracking status.

### 2.10 Chat and Telegram Communication

The system supports customer communication.

Functions:

- Chat inbox.
- Chat detail.
- Staff/customer message threads.
- Send text message.
- Send image.
- Send document.
- Send location.
- Send voice message.
- Assign chat to staff.
- Mark message as read.
- Typing status.
- Pin, mute, close, and reopen chat.
- Send invoice image to customer.

### 2.11 GPS and Location Tracking

GPS tracking helps staff monitor customer or collection activity when enabled.

Functions:

- Enable or disable GPS per customer.
- Customer location update API.
- Latest customer location.
- Customer location history.
- Staff mobile location update.
- Collection visit location record.
- Admin customer tracking screen.

### 2.12 Import and Export

The system includes tools for data migration and bulk operations.

Functions:

- Import loan data.
- Import monthly payment data.
- Download import templates.
- Start import batch.
- Process import batch.
- View import progress.
- View invalid rows.
- Export loan data.
- Export users, roles, locations, and blacklist where available.

### 2.13 Settings

Admin can configure business and system behavior.

Business settings:

- Business name.
- System name.
- System subtitle.
- Start date.
- Default profit percent.
- Currency code and symbol.
- Currency symbol placement.
- Time zone.
- Financial year start month.
- Stock accounting method.
- Transaction edit days.
- Date format.
- Time format.
- Currency precision.
- Quantity precision.
- Theme color.
- Logo.
- Stamp.
- Login background.
- CMS content.
- Invoice message template.

Other settings:

- Payment methods.
- Locations/branches.
- Users.
- Roles.
- Permissions.
- System health/status.

## 3. Full System Workflow

### 3.1 Customer Registration Workflow

1. Customer visits public website.
2. Customer registers or submits loan request.
3. Staff reviews customer information.
4. Staff verifies identity and documents.
5. Staff approves customer for quotation or loan.

### 3.2 Quotation Workflow

1. Staff creates quotation.
2. Staff enters product, price, down payment, duration, and interest/profit.
3. System calculates installment schedule.
4. Staff previews and prints quotation.
5. Quotation is sent to customer.
6. Customer accepts, rejects, or requests changes.
7. Accepted quotation is converted to loan.

### 3.3 Loan Creation Workflow

1. Staff opens New Loan.
2. Staff selects existing customer or creates new customer.
3. Staff adds product/item information.
4. Staff enters loan terms.
5. System generates schedule preview.
6. Staff saves loan as draft, pending, or active.
7. System stores loan, items, and schedules.
8. Staff prints contract or invoice.

### 3.4 Payment Workflow

1. Customer pays by cash, bank, ABA, or another method.
2. Staff opens loan or payment screen.
3. Staff records payment amount, method, reference, and note.
4. System updates paid amount and schedule balance.
5. Receipt can be printed.
6. Invoice/receipt can be sent through chat.

### 3.5 Collection Workflow

1. Staff checks Due Today or Overdue list.
2. Staff contacts customer by phone/chat/Telegram.
3. Staff records payment, promise-to-pay, or field visit.
4. System updates collection status.
5. Manager reviews collection reports.

### 3.6 Reporting Workflow

1. Manager opens reports.
2. Selects date range and filters.
3. Reviews summary cards and detail table.
4. Exports data if needed.
5. Uses report data for accounting, management, and collection planning.

## 4. Advantages of This System

### 4.1 Business Advantages

- Reduces manual Excel work.
- Centralizes customer, loan, schedule, and payment data.
- Improves payment tracking.
- Helps reduce missed collections.
- Gives management daily, monthly, and yearly visibility.
- Supports branch/location-based control.
- Helps identify overdue and risky customers.
- Speeds up quotation and loan approval.
- Improves customer service through portal and chat.

### 4.2 Operational Advantages

- Staff can search loans and customers quickly.
- Collectors can focus on due and overdue accounts.
- Payment receipts and contracts are easier to print.
- Customer history is available in one place.
- GPS and visit records support field collection control.
- Import/export helps move existing data into the system.

### 4.3 Technical Advantages

- Built with Laravel.
- Uses structured controllers, services, models, migrations, and views.
- Supports separate loan database connection.
- Supports API for staff mobile app and customer app.
- Supports role and permission control.
- Supports public website and admin system in one project.
- Can integrate with POS data.

## 5. How to Use the System

### 5.1 Admin Daily Use

1. Log in to admin system.
2. Open dashboard.
3. Review total loans, due today, overdue, and payments.
4. Check reports.
5. Manage users, roles, branches, and settings.

### 5.2 Staff Daily Use

1. Open customer list or create new customer.
2. Create quotation for interested customer.
3. Convert accepted quotation to loan.
4. Print contract or invoice.
5. Record customer payments.
6. Follow due today and overdue accounts.
7. Chat with customers when needed.

### 5.3 Collector Daily Use

1. Open Due Today or Overdue page.
2. Filter by assigned customer/location.
3. Contact customer.
4. Record collection result.
5. Add payment, promise-to-pay, or field visit.
6. Submit collection updates.

### 5.4 Customer Use

1. Register or log in.
2. View loan summary.
3. Check payment schedule.
4. Upload payment proof.
5. Chat with staff.
6. Track payment history.

## 6. Quotation and Cost Estimation for Client

Use this section to estimate how much to charge a client for implementation, customization, training, and support.

### 6.1 Cost Estimation Formula

Basic formula:

```text
Total Project Price =
Development Cost
+ Customization Cost
+ Data Migration Cost
+ Deployment Cost
+ Training Cost
+ Support/Maintenance Cost
+ Risk/Contingency
```

### 6.2 Suggested Quotation Items

| No. | Item | Description | Suggested Pricing Method |
| --- | --- | --- | --- |
| 1 | System setup | Install Laravel app, database, environment, and base configuration | Fixed price |
| 2 | Business settings | Configure logo, currency, branch, payment methods, date format, and CMS | Fixed price |
| 3 | Customer management | Customer fields, document upload, blacklist, login setup | Fixed price |
| 4 | Loan management | Loan creation, product items, schedule calculation, contract/invoice printing | Fixed price |
| 5 | Quotation module | Create, print, duplicate, status update, convert quotation to loan | Fixed price |
| 6 | Payment module | Payment recording, receipt, payment method, reference, proof upload | Fixed price |
| 7 | Collection module | Due today, overdue, promise-to-pay, field visit, recovery pages | Fixed price |
| 8 | Reports | Daily, monthly, yearly, payment, PAR, CBC, export reports | Fixed price |
| 9 | Customer portal | Customer login, dashboard, loan schedule, payment proof, chat | Fixed price |
| 10 | Chat/Telegram | Customer chat, files, voice, invoice sending, Telegram setup if required | Fixed or add-on |
| 11 | GPS tracking | Customer and staff location tracking | Add-on |
| 12 | POS integration | Sync customer/sales/product/payment data with Ultimate POS | Add-on |
| 13 | Import/export | Import existing customers, loans, payments, templates, validation | Based on data size |
| 14 | Deployment | Hosting/server setup, domain, SSL, backup setup | Fixed or actual cost |
| 15 | Training | Admin/staff training sessions | Per session/day |
| 16 | Support | Bug fix, monitoring, small changes after delivery | Monthly or yearly |

### 6.3 Example Price Packages

These are example price structures. Adjust based on client requirements, local market, and project complexity.

#### Package A: Basic Loan System

Scope:

- Customer management.
- Loan management.
- Payment schedule.
- Payment recording.
- Basic reports.
- Business settings.
- Admin users and roles.

Estimated effort: 15 to 25 working days.

Suggested price range: USD 1,500 to USD 3,500.

#### Package B: Standard Loan and Collection System

Scope:

- Everything in Basic.
- Quotation module.
- Collection operations.
- Overdue tracking.
- Customer portal.
- Import/export.
- Advanced reports.
- Receipt/contract printing.

Estimated effort: 25 to 45 working days.

Suggested price range: USD 3,500 to USD 7,500.

#### Package C: Full Loan Business Platform

Scope:

- Everything in Standard.
- POS integration.
- Chat/Telegram integration.
- GPS tracking.
- Staff mobile API.
- Customer app API.
- ABA/payment gateway setup.
- Advanced custom reports.
- Data migration.
- Training and support.

Estimated effort: 45 to 90 working days.

Suggested price range: USD 7,500 to USD 18,000+.

### 6.4 Monthly Support Estimate

| Support Plan | Scope | Suggested Monthly Price |
| --- | --- | --- |
| Basic | Bug fixes, backup check, minor support | USD 100 to USD 250/month |
| Standard | Bug fixes, small changes, monitoring, user support | USD 250 to USD 600/month |
| Premium | Priority support, feature changes, reports, integration checks | USD 600 to USD 1,500+/month |

### 6.5 Hosting and External Cost Estimate

These costs are usually separate from development.

| Item | Estimated Cost |
| --- | --- |
| Domain | USD 10 to USD 20/year |
| Basic VPS hosting | USD 10 to USD 40/month |
| Medium VPS hosting | USD 40 to USD 120/month |
| SSL certificate | Free to USD 100/year |
| Backup storage | USD 5 to USD 50/month |
| SMS/Telegram/API fees | Depends on provider |
| Payment gateway fees | Depends on bank/provider |

### 6.6 Information Needed Before Giving Final Price

Ask the client these questions:

1. How many branches will use the system?
2. How many admin/staff users are needed?
3. How many customers and loans already exist?
4. Do they need migration from Excel or another system?
5. Do they need POS integration?
6. Do they need customer portal only, or also mobile app?
7. Do they need Telegram/chat integration?
8. Do they need GPS tracking?
9. Do they need ABA/payment gateway integration?
10. What reports are required?
11. What contract/invoice format is required?
12. What language is required: Khmer, English, or both?
13. What is the delivery deadline?
14. Do they need training?
15. Do they need monthly support?

### 6.7 Client Quotation Template

```text
Quotation Title: Loan Management System Implementation

Client Name:
Company Name:
Quotation Date:
Valid Until:

Project Scope:
- Customer Management
- Loan Management
- Quotation Management
- Payment Management
- Collection Management
- Reports
- Customer Portal
- Settings and User Permissions

Optional Add-ons:
- POS Integration
- Telegram/Chat Integration
- GPS Tracking
- Payment Gateway
- Data Migration
- Staff Mobile App/API
- Customer Mobile App/API

Timeline:
- Requirement confirmation: ___ days
- Setup and configuration: ___ days
- Customization: ___ days
- Testing: ___ days
- Training and launch: ___ days

Price:
- System implementation: USD ______
- Customization: USD ______
- Data migration: USD ______
- Deployment: USD ______
- Training: USD ______
- Support: USD ______ / month

Total: USD ______

Payment Terms:
- 40% deposit before project start
- 40% after main system demo
- 20% after final delivery

Warranty:
- ___ days bug-fix warranty after launch

Notes:
- New features outside this scope will be quoted separately.
- Hosting, domain, SMS, payment gateway, and third-party fees are not included unless stated.
```

## 7. Recommended Delivery Plan

### Phase 1: Requirement and Setup

- Confirm client requirements.
- Confirm modules and reports.
- Prepare server and database.
- Configure business settings.

### Phase 2: Core System

- Customer management.
- Loan management.
- Payment schedule.
- Payment recording.
- Reports.

### Phase 3: Quotation and Collection

- Quotation workflow.
- Convert quotation to loan.
- Collection tracking.
- Overdue management.

### Phase 4: Portal, Chat, and Integration

- Customer portal.
- Chat/Telegram.
- POS integration if required.
- Payment gateway if required.
- GPS tracking if required.

### Phase 5: Testing and Training

- Test all workflows.
- Import client sample data.
- Train admin and staff.
- Fix issues from user acceptance testing.

### Phase 6: Launch and Support

- Deploy to production.
- Configure backup.
- Monitor first use.
- Provide support and maintenance.

## 8. Summary

This Loan Management System is suitable for companies that sell products by installment or manage customer loans. It provides a complete workflow from quotation to loan creation, payment collection, overdue control, customer communication, reporting, and customer self-service. The system can be quoted as a basic, standard, or full platform depending on the client scope.
