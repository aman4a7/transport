# context.md

## System Name
Integrated Fleet, Passenger, Driver Compliance, Contract, Fuel, and Garage Management System for Ethiopian Defence University

## Purpose
This system manages university transportation operations for Ethiopian Defence University.
It supports:
- defence-plated university vehicles,
- contracted private vehicles,
- drivers,
- private vehicle owners,
- passengers and staff transport users,
- trip scheduling,
- compliance and document workflows,
- fuel management,
- garage and maintenance management,
- reporting and dashboards.

## Vehicle Categories
### 1. Defence-plated vehicles
- Owned or controlled by the university
- Eligible for internal fuel services
- Eligible for internal garage services
- Fully managed inside vehicle, fuel, and maintenance workflows

### 2. Contracted private vehicles
- Owned by personal vehicle owners
- Managed through contract, route assignment, payment, and document workflows
- Not eligible for university fuel services
- Not eligible for university garage services

## Main Actors
- System Administrator
- Transport Manager
- Compliance Officer
- Fuel Attendant
- Garage Officer
- Finance Officer
- Driver
- Contractor / Vehicle Owner
- Passenger / Staff Transport User
- Auditor

## Main Modules
1. Authentication and RBAC
2. Vehicle Registry
3. Driver Management
4. Owner / Contractor Management
5. Passenger Management
6. Route and Trip Management
7. Fuel Management
8. Garage and Maintenance
9. Compliance and Document Workflow
10. Contracts and Payments
11. Reporting and Analytics
12. Notifications and Audit Logs

## Key Business Rules
- Only defence-plated vehicles may access fuel issuance.
- Only defence-plated vehicles may access garage services.
- Contracted private vehicles may be scheduled for transport operations.
- Drivers must have valid approval, license, and compliance status before assignment.
- Vehicles must have valid operational and document status before assignment.
- Contracted vehicles must have valid contract, insurance, and inspection before assignment.
- Passengers must be assigned according to route capacity and approval.

## Website Structure
### Public
- Home
- About
- Contact
- Login
- Forgot Password

### Private App
- Dashboards by role
- Vehicles
- Drivers
- Contractors
- Passengers
- Routes & Trips
- Fuel
- Garage
- Compliance
- Contracts
- Reports
- Administration

## Dashboard Types
- Admin Dashboard
- Transport Dashboard
- Compliance Dashboard
- Fuel Dashboard
- Garage Dashboard
- Finance Dashboard
- Driver Dashboard
- Passenger Dashboard

## Core Data Entities
- users
- roles
- permissions
- user_roles
- vehicles
- vehicle_owners
- vehicle_contracts
- vehicle_documents
- drivers
- driver_documents
- driver_violations
- passengers
- routes
- route_stops
- trips
- trip_assignments
- fuel_transactions
- fuel_stock
- maintenance_requests
- garage_jobs
- contracts
- notifications
- audit_logs

## Architectural Direction
- Modular monolith
- React frontend
- Laravel backend
- PostgreSQL database
- REST API between frontend and backend
- Secure file upload handling
- Role-based access plus policy enforcement

## Page Families
- Dashboard pages
- List pages
- Create/Edit form pages
- Detail pages with tabs
- Report pages
- Approval/review pages

## Special Notes
- Passenger users are first-class actors in the system, not just secondary records.
- Contracted private vehicle owners need their own limited portal for contract and document updates.
- Fuel and garage modules must visibly and technically block ineligible vehicle categories.
